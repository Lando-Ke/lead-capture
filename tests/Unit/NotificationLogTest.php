<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\NotificationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for NotificationLog model.
 */
final class NotificationLogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_notification_log(): void
    {
        $log = NotificationLog::factory()->create([
            'notification_id' => 'test-123',
            'status' => 'success',
            'response_time' => 250.5,
        ]);

        $this->assertDatabaseHas('notification_logs', [
            'notification_id' => 'test-123',
            'status' => 'success',
            'response_time' => 250.5,
        ]);

        $this->assertEquals('test-123', $log->notification_id);
        $this->assertEquals('success', $log->status);
        $this->assertEquals(250.5, $log->response_time);
    }

    #[Test]
    public function it_casts_attributes_correctly(): void
    {
        $log = NotificationLog::factory()->create([
            'recipients' => ['total' => 10, 'successful' => 8],
            'error_details' => ['code' => 400, 'message' => 'Bad request'],
            'raw_response' => ['id' => 'notification-123'],
            'response_time' => 150.0,
        ]);

        $this->assertIsArray($log->recipients);
        $this->assertIsArray($log->error_details);
        $this->assertIsArray($log->raw_response);
        $this->assertIsFloat($log->response_time);

        $this->assertEquals(['total' => 10, 'successful' => 8], $log->recipients);
        $this->assertEquals(['code' => 400, 'message' => 'Bad request'], $log->error_details);
        $this->assertEquals(['id' => 'notification-123'], $log->raw_response);
    }

    #[Test]
    public function it_scopes_successful_notifications(): void
    {
        NotificationLog::factory()->create(['status' => 'success']);
        NotificationLog::factory()->create(['status' => 'failed']);
        NotificationLog::factory()->create(['status' => 'success']);

        $successful = NotificationLog::successful()->get();

        $this->assertCount(2, $successful);
        $successful->each(function ($log) {
            $this->assertEquals('success', $log->status);
        });
    }

    #[Test]
    public function it_scopes_failed_notifications(): void
    {
        NotificationLog::factory()->create(['status' => 'success']);
        NotificationLog::factory()->create(['status' => 'failed']);
        NotificationLog::factory()->create(['status' => 'failed']);

        $failed = NotificationLog::failed()->get();

        $this->assertCount(2, $failed);
        $failed->each(function ($log) {
            $this->assertEquals('failed', $log->status);
        });
    }

    #[Test]
    public function it_scopes_recent_notifications(): void
    {
        // Create old notification
        NotificationLog::factory()->create([
            'created_at' => now()->subDays(10)
        ]);

        // Create recent notifications
        NotificationLog::factory()->create([
            'created_at' => now()->subHours(2)
        ]);
        NotificationLog::factory()->create([
            'created_at' => now()->subMinutes(30)
        ]);

        $recent = NotificationLog::recent()->get();

        $this->assertCount(2, $recent);
    }

    #[Test]
    public function it_provides_success_rate_calculation(): void
    {
        // Create 7 successful and 3 failed notifications
        NotificationLog::factory()->count(7)->create(['status' => 'success']);
        NotificationLog::factory()->count(3)->create(['status' => 'failed']);

        $successRate = NotificationLog::successRate();

        $this->assertEquals(70.0, $successRate);
    }

    #[Test]
    public function it_handles_zero_notifications_for_success_rate(): void
    {
        $successRate = NotificationLog::successRate();

        $this->assertEquals(0.0, $successRate);
    }

    #[Test]
    public function it_provides_average_response_time(): void
    {
        NotificationLog::factory()->create(['response_time' => 100.0]);
        NotificationLog::factory()->create(['response_time' => 200.0]);
        NotificationLog::factory()->create(['response_time' => 300.0]);

        $averageTime = NotificationLog::averageResponseTime();

        $this->assertEquals(200.0, $averageTime);
    }

    #[Test]
    public function it_handles_null_response_times_for_average(): void
    {
        NotificationLog::factory()->create(['response_time' => 100.0]);
        NotificationLog::factory()->create(['response_time' => null]);
        NotificationLog::factory()->create(['response_time' => 200.0]);

        $averageTime = NotificationLog::averageResponseTime();

        $this->assertEquals(150.0, $averageTime);
    }

    #[Test]
    public function it_provides_total_recipients_count(): void
    {
        NotificationLog::factory()->create([
            'recipients' => ['total' => 10, 'successful' => 8]
        ]);
        NotificationLog::factory()->create([
            'recipients' => ['total' => 5, 'successful' => 5]
        ]);

        $totalRecipients = NotificationLog::totalRecipients();

        $this->assertEquals(15, $totalRecipients);
    }

    #[Test]
    public function it_handles_missing_recipients_data(): void
    {
        NotificationLog::factory()->create(['recipients' => null]);
        NotificationLog::factory()->create(['recipients' => []]);
        NotificationLog::factory()->create([
            'recipients' => ['total' => 5]
        ]);

        $totalRecipients = NotificationLog::totalRecipients();

        $this->assertEquals(5, $totalRecipients);
    }

    #[Test]
    public function it_formats_response_time_accessor(): void
    {
        $log = NotificationLog::factory()->create([
            'response_time' => 1234.5678
        ]);

        $this->assertEquals('1234.57ms', $log->formatted_response_time);
    }

    #[Test]
    public function it_handles_null_response_time_accessor(): void
    {
        $log = NotificationLog::factory()->create([
            'response_time' => null
        ]);

        $this->assertEquals('N/A', $log->formatted_response_time);
    }

    #[Test]
    public function it_determines_success_status(): void
    {
        $successLog = NotificationLog::factory()->create(['status' => 'success']);
        $failedLog = NotificationLog::factory()->create(['status' => 'failed']);

        $this->assertTrue($successLog->is_successful);
        $this->assertFalse($failedLog->is_successful);
    }

    #[Test]
    public function it_counts_successful_recipients(): void
    {
        $log = NotificationLog::factory()->create([
            'recipients' => ['total' => 10, 'successful' => 7, 'failed' => 3]
        ]);

        $this->assertEquals(7, $log->successful_recipients_count);
    }

    #[Test]
    public function it_handles_missing_successful_recipients_data(): void
    {
        $log = NotificationLog::factory()->create([
            'recipients' => ['total' => 10, 'failed' => 3]
        ]);

        $this->assertEquals(0, $log->successful_recipients_count);
    }

    #[Test]
    public function it_uses_fillable_attributes(): void
    {
        $attributes = [
            'notification_id' => 'test-fillable',
            'status' => 'success',
            'message' => 'Test message',
            'recipients' => ['total' => 5],
            'response_time' => 150.0,
            'error_code' => null,
            'error_details' => null,
            'raw_response' => ['id' => 'test-fillable'],
        ];

        $log = new NotificationLog($attributes);

        $this->assertEquals('test-fillable', $log->notification_id);
        $this->assertEquals('success', $log->status);
        $this->assertEquals('Test message', $log->message);
        $this->assertEquals(['total' => 5], $log->recipients);
        $this->assertEquals(150.0, $log->response_time);
    }

    #[Test]
    public function it_excludes_non_fillable_attributes(): void
    {
        $log = new NotificationLog([
            'id' => 999, // This should be ignored
            'notification_id' => 'test-exclude',
            'created_at' => '2023-01-01', // This should be ignored
            'updated_at' => '2023-01-01', // This should be ignored
        ]);

        // ID should not be set from mass assignment
        $this->assertNull($log->id);
        $this->assertEquals('test-exclude', $log->notification_id);
    }
}
