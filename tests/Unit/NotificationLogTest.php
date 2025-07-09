<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\NotificationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Simple unit tests for NotificationLog model.
 */
final class NotificationLogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_notification_log(): void
    {
        $log = NotificationLog::factory()->create([
            'notification_id' => 'test-123',
            'status' => 'sent',
            'response_time_ms' => 250.5,
        ]);

        $this->assertDatabaseHas('notification_logs', [
            'notification_id' => 'test-123',
            'status' => 'sent',
            'response_time_ms' => 250.5,
        ]);

        $this->assertEquals('test-123', $log->notification_id);
        $this->assertEquals('sent', $log->status);
        $this->assertEquals(250.5, $log->response_time_ms);
    }

    #[Test]
    public function it_casts_json_attributes_correctly(): void
    {
        $log = NotificationLog::factory()->create([
            'recipients' => ['total' => 10, 'successful' => 8],
            'error_details' => ['code' => 400, 'message' => 'Bad request'],
            'additional_data' => ['type' => 'lead_submission'],
        ]);

        $this->assertIsArray($log->recipients);
        $this->assertIsArray($log->error_details);
        $this->assertIsArray($log->additional_data);

        $this->assertEquals(['total' => 10, 'successful' => 8], $log->recipients);
        $this->assertEquals(['code' => 400, 'message' => 'Bad request'], $log->error_details);
    }

    #[Test]
    public function it_scopes_by_status(): void
    {
        NotificationLog::factory()->create(['status' => 'sent']);
        NotificationLog::factory()->create(['status' => 'failed']);
        NotificationLog::factory()->create(['status' => 'sent']);

        $sent = NotificationLog::where('status', 'sent')->get();
        $failed = NotificationLog::where('status', 'failed')->get();

        $this->assertCount(2, $sent);
        $this->assertCount(1, $failed);
    }

    #[Test]
    public function it_stores_lead_reference(): void
    {
        $log = NotificationLog::factory()->create([
            'lead_email' => 'test@example.com',
            'notification_type' => 'lead_submission',
        ]);

        $this->assertEquals('test@example.com', $log->lead_email);
        $this->assertEquals('lead_submission', $log->notification_type);
    }

    #[Test]
    public function it_handles_nullable_fields(): void
    {
        $log = NotificationLog::factory()->create([
            'notification_id' => null,
            'error_code' => null,
            'error_message' => null,
        ]);

        $this->assertNull($log->notification_id);
        $this->assertNull($log->error_code);
        $this->assertNull($log->error_message);
    }
}
