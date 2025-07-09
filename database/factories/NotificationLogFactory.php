<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Lead;
use App\Models\NotificationLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating NotificationLog test instances.
 * 
 * Generates realistic test data for comprehensive notification log testing
 * including various statuses, error scenarios, and performance metrics.
 */
final class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    public function definition(): array
    {
        $attemptedAt = Carbon::now()->subMinutes($this->faker->numberBetween(5, 1440));
        $isCompleted = $this->faker->boolean(80); // 80% chance of being completed

        return [
            'lead_id' => Lead::factory(),
            'lead_email' => $this->faker->email(),
            'notification_id' => $this->faker->uuid(),
            'notification_type' => $this->faker->randomElement([
                'lead_submission', 'admin_test', 'marketing', 'system_alert'
            ]),
            'title' => $this->faker->randomElement([
                'New Lead Submission',
                'Test Notification',
                'Marketing Campaign Alert',
                'System Update',
            ]),
            'message' => $this->faker->sentence(10),
            'status' => $this->faker->randomElement([
                NotificationLog::STATUS_SENT,
                NotificationLog::STATUS_FAILED,
                NotificationLog::STATUS_PENDING,
                NotificationLog::STATUS_SKIPPED,
            ]),
            'attempt_number' => $this->faker->numberBetween(1, 3),
            'recipients' => [
                'total' => $total = $this->faker->numberBetween(1, 100),
                'successful' => $successful = $this->faker->numberBetween(0, $total),
                'failed' => $total - $successful,
                'clicked' => $this->faker->numberBetween(0, $successful),
                'converted' => $this->faker->numberBetween(0, $successful),
            ],
            'response_time_ms' => $this->faker->randomFloat(2, 100, 5000),
            'processing_time_ms' => $this->faker->randomFloat(2, 50, 2000),
            'error_code' => $this->faker->optional(0.3)->randomElement([
                'timeout', 'invalid_app_id', 'rate_limit', 'network_error', 'unauthorized'
            ]),
            'error_message' => $this->faker->optional(0.3)->sentence(),
            'error_details' => $this->faker->optional(0.2)->randomElement([
                ['code' => 400, 'field' => 'app_id'],
                ['timeout_duration' => 30000, 'retry_count' => 3],
                ['rate_limit' => ['limit' => 1000, 'remaining' => 0]],
            ]),
            'additional_data' => [
                'user_agent' => $this->faker->userAgent(),
                'ip_address' => $this->faker->ipv4(),
                'source' => $this->faker->randomElement(['web', 'api', 'admin']),
                'campaign_id' => $this->faker->optional()->uuid(),
            ],
            'metadata' => [
                'platform' => $this->faker->randomElement(['web', 'mobile', 'desktop']),
                'version' => $this->faker->semver(),
                'locale' => $this->faker->locale(),
            ],
            'raw_response' => [
                'id' => $this->faker->uuid(),
                'external_id' => $this->faker->optional()->uuid(),
                'queued' => $this->faker->boolean(),
                'send_after' => $this->faker->optional(0.3, null)->passthrough($this->faker->dateTime()->format('c')),
            ],
            'user_agent' => $this->faker->userAgent(),
            'ip_address' => $this->faker->ipv4(),
            'attempted_at' => $attemptedAt,
            'completed_at' => $isCompleted ? 
                $attemptedAt->copy()->addMilliseconds($this->faker->numberBetween(100, 5000)) : 
                null,
        ];
    }

    /**
     * Create a successful notification log.
     */
    public function successful(): self
    {
        return $this->state([
            'status' => NotificationLog::STATUS_SENT,
            'notification_id' => $this->faker->uuid(),
            'error_code' => null,
            'error_message' => null,
            'error_details' => null,
            'completed_at' => Carbon::now()->subMinutes($this->faker->numberBetween(1, 60)),
        ]);
    }

    /**
     * Create a failed notification log.
     */
    public function failed(): self
    {
        return $this->state([
            'status' => NotificationLog::STATUS_FAILED,
            'notification_id' => null,
            'error_code' => $this->faker->randomElement([
                'timeout', 'invalid_app_id', 'network_error', 'rate_limit'
            ]),
            'error_message' => $this->faker->randomElement([
                'Request timeout after 30 seconds',
                'Invalid app ID provided',
                'Network connection failed',
                'API rate limit exceeded',
            ]),
            'completed_at' => Carbon::now()->subMinutes($this->faker->numberBetween(1, 60)),
        ]);
    }

    /**
     * Create a pending notification log.
     */
    public function pending(): self
    {
        return $this->state([
            'status' => NotificationLog::STATUS_PENDING,
            'notification_id' => null,
            'error_code' => null,
            'error_message' => null,
            'error_details' => null,
            'completed_at' => null,
            'attempted_at' => Carbon::now(),
        ]);
    }

    /**
     * Create a skipped notification log.
     */
    public function skipped(): self
    {
        return $this->state([
            'status' => NotificationLog::STATUS_SKIPPED,
            'notification_id' => null,
            'error_code' => 'test_email',
            'error_message' => 'Skipped test email notification',
            'recipients' => null,
            'completed_at' => Carbon::now()->subMinutes($this->faker->numberBetween(1, 60)),
        ]);
    }

    /**
     * Create a high-performance notification log.
     */
    public function highPerformance(): self
    {
        return $this->successful()->state([
            'response_time_ms' => $this->faker->randomFloat(2, 50, 200),
            'processing_time_ms' => $this->faker->randomFloat(2, 25, 100),
            'recipients' => [
                'total' => $total = $this->faker->numberBetween(50, 200),
                'successful' => $total,
                'failed' => 0,
                'clicked' => $this->faker->numberBetween(10, $total),
                'converted' => $this->faker->numberBetween(5, 25),
            ],
        ]);
    }

    /**
     * Create a low-performance notification log.
     */
    public function lowPerformance(): self
    {
        return $this->failed()->state([
            'response_time_ms' => $this->faker->randomFloat(2, 3000, 10000),
            'processing_time_ms' => $this->faker->randomFloat(2, 2000, 8000),
            'error_code' => 'timeout',
            'error_message' => 'Request timeout - performance degraded',
        ]);
    }

    /**
     * Create a notification log for a specific email.
     */
    public function forEmail(string $email): self
    {
        return $this->state([
            'lead_email' => $email,
        ]);
    }

    /**
     * Create a notification log for a specific lead.
     */
    public function forLead(Lead $lead): self
    {
        return $this->state([
            'lead_id' => $lead->id,
            'lead_email' => $lead->email,
        ]);
    }

    /**
     * Create a notification log with multiple retry attempts.
     */
    public function withRetries(int $attemptNumber = 3): self
    {
        return $this->state([
            'attempt_number' => $attemptNumber,
            'error_code' => $attemptNumber > 1 ? 'timeout' : null,
            'error_message' => $attemptNumber > 1 ? "Failed after {$attemptNumber} attempts" : null,
        ]);
    }

    /**
     * Create a notification log from a specific time period.
     */
    public function fromPeriod(Carbon $startDate, Carbon $endDate): self
    {
        $attemptedAt = $this->faker->dateTimeBetween($startDate, $endDate);
        
        return $this->state([
            'attempted_at' => $attemptedAt,
            'completed_at' => $this->faker->boolean(80) ? 
                Carbon::instance($attemptedAt)->addSeconds($this->faker->numberBetween(1, 300)) : 
                null,
        ]);
    }

    /**
     * Create a notification log with specific notification type.
     */
    public function ofType(string $type): self
    {
        $titles = [
            'lead_submission' => 'New Lead Submission',
            'admin_test' => 'Admin Test Notification',
            'marketing' => 'Marketing Campaign Alert',
            'system_alert' => 'System Alert',
        ];

        return $this->state([
            'notification_type' => $type,
            'title' => $titles[$type] ?? 'Generic Notification',
        ]);
    }

    /**
     * Create a notification log with large recipient count.
     */
    public function withLargeAudience(): self
    {
        return $this->state([
            'recipients' => [
                'total' => $total = $this->faker->numberBetween(1000, 10000),
                'successful' => $successful = $this->faker->numberBetween((int)($total * 0.85), $total),
                'failed' => $total - $successful,
                'clicked' => $this->faker->numberBetween((int)($successful * 0.1), (int)($successful * 0.3)),
                'converted' => $this->faker->numberBetween(0, (int)($successful * 0.05)),
            ],
        ]);
    }

    /**
     * Create a notification log with detailed error information.
     */
    public function withDetailedError(): self
    {
        return $this->failed()->state([
            'error_details' => [
                'http_code' => 400,
                'api_error' => 'INVALID_REQUEST',
                'field_errors' => [
                    'app_id' => ['App ID is required'],
                    'contents' => ['At least one content language is required'],
                ],
                'request_id' => $this->faker->uuid(),
                'timestamp' => Carbon::now()->toISOString(),
            ],
        ]);
    }
}
