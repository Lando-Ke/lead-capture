<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Contracts\OneSignalServiceInterface;
use App\Events\LeadSubmittedEvent;
use App\Models\NotificationLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener for handling lead submission notifications.
 *
 * Processes LeadSubmittedEvent asynchronously via queue to send
 * OneSignal push notifications without blocking form submissions.
 *
 * Enhanced with comprehensive notification logging and tracking.
 */
class SendNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the queued listener may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 60;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [30, 60, 120];

    /**
     * Create the event listener.
     */
    public function __construct(
        private readonly OneSignalServiceInterface $oneSignalService
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(LeadSubmittedEvent $event): void
    {
        $startTime = microtime(true);
        $notificationLog = null;

        Log::info('Processing lead notification', [
            'event_id' => class_basename($event),
            'lead_email' => $event->getLeadEmail(),
            'metadata' => $event->getEventMetadata(),
        ]);

        try {
            // Create notification log entry
            $notificationLog = NotificationLog::createForAttempt(
                leadId: $this->getLeadId($event),
                leadEmail: $event->getLeadEmail(),
                title: 'New Lead Submitted',
                message: 'A new user has submitted the registration form',
                additionalData: $event->getNotificationData(),
                metadata: $event->getEventMetadata()
            );

            // Update attempt number for retries
            if ($this->attempts() > 1) {
                $notificationLog->incrementAttempt();
            }

            // Check if notifications should be sent for this lead
            if (!$event->shouldTriggerNotifications()) {
                $reason = 'Test email pattern detected';

                Log::info('Skipping notification for test/demo lead', [
                    'lead_email' => $event->getLeadEmail(),
                    'reason' => $reason,
                    'log_id' => $notificationLog->id,
                ]);

                $notificationLog->markAsSkipped($reason, [
                    'skip_reason' => 'test_email_pattern',
                    'patterns_checked' => ['test@', 'demo@', 'example@', '+test', '@test.'],
                ]);

                return;
            }

            // Check if OneSignal service is enabled
            if (!$this->oneSignalService->isEnabled()) {
                $reason = 'OneSignal service is disabled';

                Log::info('OneSignal notifications disabled, skipping', [
                    'lead_email' => $event->getLeadEmail(),
                    'log_id' => $notificationLog->id,
                ]);

                $notificationLog->markAsSkipped($reason, [
                    'skip_reason' => 'service_disabled',
                    'service_config' => $this->oneSignalService->getConfiguration(),
                ]);

                return;
            }

            // Send the notification
            $result = $this->oneSignalService->sendLeadSubmissionNotification();
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($result->success) {
                Log::info('Lead notification sent successfully', [
                    'lead_email' => $event->getLeadEmail(),
                    'notification_id' => $result->notificationId,
                    'recipients' => $result->recipients,
                    'processing_time_ms' => $processingTime,
                    'api_response_time_ms' => $result->responseTime,
                    'log_id' => $notificationLog->id,
                ]);

                // Mark as sent in the log
                $notificationLog->markAsSent(
                    notificationId: $result->notificationId ?? 'unknown',
                    recipients: $result->recipients,
                    responseTime: $result->responseTime,
                    processingTime: $processingTime,
                    rawResponse: $result->rawResponse
                );

                // Record success metrics if needed
                $this->recordSuccessMetrics($event, $result, $notificationLog);
            } else {
                Log::warning('Lead notification failed', [
                    'lead_email' => $event->getLeadEmail(),
                    'error_message' => $result->message,
                    'error_code' => $result->errorCode,
                    'processing_time_ms' => $processingTime,
                    'is_retryable' => $result->isRetryable(),
                    'attempt' => $this->attempts(),
                    'log_id' => $notificationLog->id,
                ]);

                // Mark as failed in the log
                $notificationLog->markAsFailed(
                    errorCode: $result->errorCode ?? 'unknown_error',
                    errorMessage: $result->message ?? 'Unknown error occurred',
                    errorDetails: $result->errorDetails,
                    responseTime: $result->responseTime,
                    processingTime: $processingTime,
                    rawResponse: $result->rawResponse
                );

                // Determine if we should retry
                if ($result->isRetryable() && $this->attempts() < $this->tries) {
                    $this->release($this->getRetryDelay());

                    return;
                }

                // Record failure metrics
                $this->recordFailureMetrics($event, $result, $notificationLog);
            }
        } catch (\Exception $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Unexpected error processing lead notification', [
                'lead_email' => $event->getLeadEmail(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts(),
                'processing_time_ms' => $processingTime,
                'log_id' => $notificationLog?->id,
            ]);

            // Mark as failed in the log if we have one
            if ($notificationLog) {
                $notificationLog->markAsFailed(
                    errorCode: 'exception',
                    errorMessage: $e->getMessage(),
                    errorDetails: [
                        'exception_class' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ],
                    processingTime: $processingTime
                );
            }

            // Retry on unexpected errors
            if ($this->attempts() < $this->tries) {
                $this->release($this->getRetryDelay());

                return;
            }

            // Record critical failure
            $this->recordCriticalFailure($event, $e, $notificationLog);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(LeadSubmittedEvent $event, \Exception $exception): void
    {
        Log::critical('Lead notification listener failed permanently', [
            'lead_email' => $event->getLeadEmail(),
            'error' => $exception->getMessage(),
            'attempts' => $this->tries,
            'event_data' => $event->getEventMetadata(),
        ]);

        // Try to find and update the notification log
        $notificationLog = NotificationLog::forEmail($event->getLeadEmail())
            ->pending()
            ->latest()
            ->first();

        if ($notificationLog) {
            $notificationLog->markAsFailed(
                errorCode: 'permanent_failure',
                errorMessage: 'Job failed after maximum attempts: ' . $exception->getMessage(),
                errorDetails: [
                    'max_attempts' => $this->tries,
                    'exception_class' => get_class($exception),
                    'final_error' => $exception->getMessage(),
                ]
            );
        }

        $this->recordCriticalFailure($event, $exception, $notificationLog);
    }

    /**
     * Get the lead ID from the database.
     */
    private function getLeadId(LeadSubmittedEvent $event): ?int
    {
        // Try to find the lead by email to get the ID
        $lead = \App\Models\Lead::where('email', $event->getLeadEmail())->first();

        return $lead?->id;
    }

    /**
     * Get the retry delay for the current attempt.
     */
    private function getRetryDelay(): int
    {
        $attempt = $this->attempts();

        return $this->backoff[$attempt - 1] ?? end($this->backoff);
    }

    /**
     * Record success metrics.
     *
     * @param \App\DTOs\NotificationResultDTO $result
     */
    private function recordSuccessMetrics(LeadSubmittedEvent $event, $result, NotificationLog $notificationLog): void
    {
        Log::debug('Recording success metrics', [
            'lead_email' => $event->getLeadEmail(),
            'notification_id' => $result->notificationId,
            'response_time' => $result->responseTime,
            'log_id' => $notificationLog->id,
        ]);

        // TODO: Implement additional metrics recording (e.g., to metrics service, dashboard, etc.)
    }

    /**
     * Record failure metrics.
     *
     * @param \App\DTOs\NotificationResultDTO $result
     */
    private function recordFailureMetrics(LeadSubmittedEvent $event, $result, NotificationLog $notificationLog): void
    {
        Log::debug('Recording failure metrics', [
            'lead_email' => $event->getLeadEmail(),
            'error_code' => $result->errorCode,
            'error_message' => $result->message,
            'log_id' => $notificationLog->id,
        ]);

        // TODO: Implement failure metrics recording
    }

    /**
     * Record critical failure.
     */
    private function recordCriticalFailure(LeadSubmittedEvent $event, \Exception $exception, ?NotificationLog $notificationLog): void
    {
        Log::debug('Recording critical failure', [
            'lead_email' => $event->getLeadEmail(),
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'log_id' => $notificationLog?->id,
        ]);

        // TODO: Implement critical failure handling
        // This might include:
        // - Admin notifications
        // - Failed notification storage for manual retry
        // - Error rate monitoring
    }

    /**
     * Determine the queue that should handle the job.
     */
    public function viaQueue(): string
    {
        return 'notifications';
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        // Add rate limiting, throttling, or other middleware as needed
        return [];
    }
}
