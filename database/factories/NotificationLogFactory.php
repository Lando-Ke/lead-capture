<?php

namespace Database\Factories;

use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationLog>
 */
class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => null, // Will be set by relationships
            'lead_email' => $this->faker->email(),
            'notification_type' => 'lead_submission',
            'title' => 'New Lead Submission',
            'message' => 'A new user has submitted the registration form',
            'additional_data' => [
                'source' => 'web',
                'user_agent' => $this->faker->userAgent(),
            ],
            'status' => $this->faker->randomElement(['pending', 'sent', 'failed', 'skipped']),
            'notification_id' => $this->faker->uuid(),
            'recipients' => [
                'total' => $this->faker->numberBetween(1, 100),
                'successful' => $this->faker->numberBetween(0, 50),
                'failed' => $this->faker->numberBetween(0, 10),
            ],
            'error_code' => null,
            'error_message' => null,
            'error_details' => null,
            'response_time_ms' => $this->faker->randomFloat(2, 50, 2000),
            'processing_time_ms' => $this->faker->randomFloat(2, 10, 500),
            'attempt_number' => 1,
            'user_agent' => $this->faker->userAgent(),
            'ip_address' => $this->faker->ipv4(),
            'metadata' => [
                'version' => '1.0',
                'platform' => 'web',
            ],
            'raw_response' => [
                'id' => $this->faker->uuid(),
                'external_id' => null,
                'queued' => false,
            ],
            'attempted_at' => now(),
            'completed_at' => now(),
        ];
    }

    /**
     * Configure for successful notification.
     */
    public function successful(): static
    {
        return $this->state([
            'status' => 'sent',
            'notification_id' => $this->faker->uuid(),
            'error_code' => null,
            'error_message' => null,
            'error_details' => null,
            'completed_at' => now(),
        ]);
    }

    /**
     * Configure for failed notification.
     */
    public function failed(): static
    {
        return $this->state([
            'status' => 'failed',
            'notification_id' => null,
            'error_code' => $this->faker->randomElement(['400', '401', '500', 'timeout']),
            'error_message' => 'Notification failed',
            'error_details' => [
                'reason' => 'API error',
                'details' => 'Invalid request',
            ],
            'completed_at' => now(),
        ]);
    }

    /**
     * Configure for pending notification.
     */
    public function pending(): static
    {
        return $this->state([
            'status' => 'pending',
            'notification_id' => null,
            'error_code' => null,
            'error_message' => null,
            'error_details' => null,
            'completed_at' => null,
        ]);
    }
}
