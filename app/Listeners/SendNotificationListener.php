<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Contracts\OneSignalServiceInterface;
use App\Events\LeadSubmittedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Listener for handling lead submission notifications.
 * 
 * Processes LeadSubmittedEvent asynchronously via queue to send
 * OneSignal push notifications without blocking form submissions.
 */
class SendNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the queued listener may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
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
     *
     * @param OneSignalServiceInterface $oneSignalService
     */
    public function __construct(
        private readonly OneSignalServiceInterface $oneSignalService
    ) {}

    /**
     * Handle the event.
     *
     * @param LeadSubmittedEvent $event
     * @return void
     */
    public function handle(LeadSubmittedEvent $event): void
    {
        Log::info('Processing lead notification', [
            'event_id' => class_basename($event),
            'lead_email' => $event->getLeadEmail(),
            'metadata' => $event->getEventMetadata(),
        ]);

        // Check if notifications should be sent for this lead
        if (!$event->shouldTriggerNotifications()) {
            Log::info('Skipping notification for test/demo lead', [
                'lead_email' => $event->getLeadEmail(),
                'reason' => 'Test email pattern detected',
            ]);
            return;
        }

        // Check if OneSignal service is enabled
        if (!$this->oneSignalService->isEnabled()) {
            Log::info('OneSignal notifications disabled, skipping', [
                'lead_email' => $event->getLeadEmail(),
            ]);
            return;
        }

        try {
            $startTime = microtime(true);

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
                ]);

                // Record success metrics if needed
                $this->recordSuccessMetrics($event, $result);

            } else {
                Log::warning('Lead notification failed', [
                    'lead_email' => $event->getLeadEmail(),
                    'error_message' => $result->message,
                    'error_code' => $result->errorCode,
                    'processing_time_ms' => $processingTime,
                    'is_retryable' => $result->isRetryable(),
                ]);

                // Determine if we should retry
                if ($result->isRetryable() && $this->attempts() < $this->tries) {
                    $this->release($this->getRetryDelay());
                    return;
                }

                // Record failure metrics
                $this->recordFailureMetrics($event, $result);
            }

        } catch (Exception $e) {
            Log::error('Unexpected error processing lead notification', [
                'lead_email' => $event->getLeadEmail(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts(),
            ]);

            // Retry on unexpected errors
            if ($this->attempts() < $this->tries) {
                $this->release($this->getRetryDelay());
                return;
            }

            // Record critical failure
            $this->recordCriticalFailure($event, $e);
        }
    }

    /**
     * Handle a job failure.
     *
     * @param LeadSubmittedEvent $event
     * @param Exception $exception
     * @return void
     */
    public function failed(LeadSubmittedEvent $event, Exception $exception): void
    {
        Log::critical('Lead notification listener failed permanently', [
            'lead_email' => $event->getLeadEmail(),
            'error' => $exception->getMessage(),
            'attempts' => $this->tries,
            'event_data' => $event->getEventMetadata(),
        ]);

        // TODO: Consider sending admin alert, storing failed notification, etc.
        $this->recordCriticalFailure($event, $exception);
    }

    /**
     * Get the retry delay for the current attempt.
     *
     * @return int
     */
    private function getRetryDelay(): int
    {
        $attempt = $this->attempts();
        return $this->backoff[$attempt - 1] ?? end($this->backoff);
    }

    /**
     * Record success metrics.
     *
     * @param LeadSubmittedEvent $event
     * @param \App\DTOs\NotificationResultDTO $result
     * @return void
     */
    private function recordSuccessMetrics(LeadSubmittedEvent $event, $result): void
    {
        // TODO: Implement metrics recording (e.g., to metrics service, database, etc.)
        // This could include:
        // - Notification success rate
        // - Response times
        // - Lead conversion tracking
        Log::debug('Recording success metrics', [
            'lead_email' => $event->getLeadEmail(),
            'notification_id' => $result->notificationId,
            'response_time' => $result->responseTime,
        ]);
    }

    /**
     * Record failure metrics.
     *
     * @param LeadSubmittedEvent $event
     * @param \App\DTOs\NotificationResultDTO $result
     * @return void
     */
    private function recordFailureMetrics(LeadSubmittedEvent $event, $result): void
    {
        // TODO: Implement failure metrics recording
        Log::debug('Recording failure metrics', [
            'lead_email' => $event->getLeadEmail(),
            'error_code' => $result->errorCode,
            'error_message' => $result->message,
        ]);
    }

    /**
     * Record critical failure.
     *
     * @param LeadSubmittedEvent $event
     * @param Exception $exception
     * @return void
     */
    private function recordCriticalFailure(LeadSubmittedEvent $event, Exception $exception): void
    {
        // TODO: Implement critical failure handling
        // This might include:
        // - Admin notifications
        // - Failed notification storage for manual retry
        // - Error rate monitoring
        Log::debug('Recording critical failure', [
            'lead_email' => $event->getLeadEmail(),
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
        ]);
    }

    /**
     * Determine the queue that should handle the job.
     *
     * @return string
     */
    public function viaQueue(): string
    {
        return 'notifications';
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        // Add rate limiting, throttling, or other middleware as needed
        return [];
    }
} 