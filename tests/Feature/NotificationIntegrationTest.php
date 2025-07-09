<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\LeadSubmittedEvent;
use App\Listeners\SendNotificationListener;
use App\Models\Lead;
use App\Models\NotificationLog;
use App\Models\Platform;
use App\Services\OneSignalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration tests for the complete notification flow.
 * 
 * Tests the entire pipeline from lead submission through
 * OneSignal notification to logging and analytics.
 */
final class NotificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Platform $platform;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test platform
        $this->platform = Platform::factory()->create([
            'name' => 'Test Platform',
            'website_type' => 'business',
            'is_active' => true,
        ]);

        // Configure OneSignal for testing
        config([
            'onesignal.enabled' => true,
            'onesignal.app_id' => 'test-app-id',
            'onesignal.rest_api_key' => 'test-api-key',
        ]);
    }

    #[Test]
    public function it_completes_full_notification_flow_on_lead_submission(): void
    {
        // Mock OneSignal API
        Http::fake([
            'onesignal.com/api/v1/notifications' => Http::response([
                'id' => 'notification-integration-test',
                'recipients' => ['total' => 5, 'successful' => 5, 'failed' => 0],
            ], 200),
        ]);

        // Create lead via API
        $response = $this->postJson('/api/v1/leads', [
            'platform_id' => $this->platform->id,
            'name' => 'Integration Test User',
            'email' => 'integration@test.com',
            'phone' => '+1234567890',
            'company' => 'Test Company',
            'message' => 'Integration test message',
        ]);

        $response->assertStatus(201);

        // Verify lead was created
        $this->assertDatabaseHas('leads', [
            'email' => 'integration@test.com',
            'name' => 'Integration Test User',
        ]);

        // Verify notification log was created
        $this->assertDatabaseHas('notification_logs', [
            'notification_id' => 'notification-integration-test',
            'status' => 'success',
        ]);

        // Verify HTTP call was made
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'onesignal.com/api/v1/notifications') &&
                   $request->method() === 'POST';
        });
    }

    #[Test]
    public function it_handles_notification_failure_gracefully(): void
    {
        // Mock OneSignal API failure
        Http::fake([
            'onesignal.com/api/v1/notifications' => Http::response([
                'errors' => ['App ID is invalid'],
            ], 400),
        ]);

        // Create lead
        $response = $this->postJson('/api/v1/leads', [
            'platform_id' => $this->platform->id,
            'name' => 'Failure Test User',
            'email' => 'failure@test.com',
            'phone' => '+1234567890',
        ]);

        // Lead creation should still succeed
        $response->assertStatus(201);

        // Verify lead was created despite notification failure
        $this->assertDatabaseHas('leads', [
            'email' => 'failure@test.com',
            'name' => 'Failure Test User',
        ]);

        // Verify failure was logged
        $this->assertDatabaseHas('notification_logs', [
            'status' => 'failed',
            'error_code' => '400',
        ]);
    }

    #[Test]
    public function it_logs_comprehensive_notification_data(): void
    {
        // Mock detailed OneSignal response
        Http::fake([
            'onesignal.com/api/v1/notifications' => Http::response([
                'id' => 'detailed-test-notification',
                'recipients' => [
                    'total' => 10,
                    'successful' => 8,
                    'failed' => 2,
                    'converted' => 6
                ],
                'external_id_count' => 8,
                'platform_delivery_stats' => [
                    'ios' => ['successful' => 4, 'failed' => 1],
                    'android' => ['successful' => 4, 'failed' => 1]
                ]
            ], 200),
        ]);

        // Create lead
        $this->postJson('/api/v1/leads', [
            'platform_id' => $this->platform->id,
            'name' => 'Detailed Test User',
            'email' => 'detailed@test.com',
            'phone' => '+1234567890',
        ]);

        // Verify comprehensive logging
        $log = NotificationLog::where('notification_id', 'detailed-test-notification')->first();
        
        $this->assertNotNull($log);
        $this->assertEquals('success', $log->status);
        $this->assertEquals(['total' => 10, 'successful' => 8, 'failed' => 2, 'converted' => 6], $log->recipients);
        $this->assertNotNull($log->response_time);
        $this->assertArrayHasKey('platform_delivery_stats', $log->raw_response);
    }

    #[Test]
    public function it_handles_event_driven_architecture(): void
    {
        Event::fake();
        
        // Create lead
        $lead = Lead::factory()->create([
            'platform_id' => $this->platform->id,
            'email' => 'event@test.com',
        ]);

        // Dispatch event manually
        Event::dispatch(new LeadSubmittedEvent($lead));

        // Verify event was dispatched
        Event::assertDispatched(LeadSubmittedEvent::class, function ($event) use ($lead) {
            return $event->lead->id === $lead->id;
        });
    }

    #[Test]
    public function it_processes_notifications_via_queue(): void
    {
        Queue::fake();
        
        // Mock OneSignal
        Http::fake([
            'onesignal.com/api/v1/notifications' => Http::response([
                'id' => 'queued-notification',
                'recipients' => ['total' => 3],
            ], 200),
        ]);

        // Create lead
        $lead = Lead::factory()->create([
            'platform_id' => $this->platform->id,
        ]);

        // Dispatch event
        Event::dispatch(new LeadSubmittedEvent($lead));

        // Process queue if sync driver is used
        if (config('queue.default') === 'sync') {
            // Notification should be processed immediately in sync mode
            $this->assertDatabaseHas('notification_logs', [
                'notification_id' => 'queued-notification',
            ]);
        }
    }

    #[Test]
    public function it_provides_notification_analytics_endpoint(): void
    {
        // Create sample notification logs
        NotificationLog::factory()->create([
            'status' => 'success',
            'response_time' => 150.0,
            'recipients' => ['total' => 10, 'successful' => 10],
        ]);
        
        NotificationLog::factory()->create([
            'status' => 'failed',
            'response_time' => 50.0,
            'error_code' => '400',
        ]);

        $response = $this->getJson('/api/v1/notifications/analytics');

        $response->assertOk()
                ->assertJsonStructure([
                    'success_rate',
                    'total_notifications',
                    'average_response_time',
                    'total_recipients',
                    'recent_activity' => [
                        '*' => ['status', 'created_at', 'response_time']
                    ],
                    'error_distribution',
                    'performance_trends'
                ]);

        $data = $response->json();
        $this->assertEquals(50.0, $data['success_rate']); // 1 success, 1 failure
        $this->assertEquals(2, $data['total_notifications']);
    }

    #[Test]
    public function it_provides_notification_status_endpoint(): void
    {
        $response = $this->getJson('/api/v1/notifications/status');

        $response->assertOk()
                ->assertJsonStructure([
                    'service' => [
                        'enabled',
                        'configured',
                        'app_id',
                        'has_api_key'
                    ],
                    'queue' => [
                        'driver',
                        'connection'
                    ],
                    'statistics' => [
                        'total_sent',
                        'success_rate',
                        'average_response_time'
                    ],
                    'recent_activity'
                ]);

        $data = $response->json();
        $this->assertTrue($data['service']['enabled']);
        $this->assertTrue($data['service']['configured']);
    }

    #[Test]
    public function it_provides_health_check_endpoint(): void
    {
        // Mock OneSignal app info endpoint
        Http::fake([
            'onesignal.com/api/v1/apps/test-app-id' => Http::response([
                'id' => 'test-app-id',
                'name' => 'Test App',
                'players' => 100,
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/notifications/health');

        $response->assertOk()
                ->assertJsonStructure([
                    'status',
                    'timestamp',
                    'checks' => [
                        'onesignal_connectivity',
                        'configuration',
                        'queue_status'
                    ],
                    'response_time',
                    'app_info'
                ]);

        $data = $response->json();
        $this->assertEquals('healthy', $data['status']);
        $this->assertTrue($data['checks']['onesignal_connectivity']);
        $this->assertTrue($data['checks']['configuration']);
    }

    #[Test]
    public function it_handles_retry_functionality(): void
    {
        // Create a failed notification log
        $failedLog = NotificationLog::factory()->create([
            'notification_id' => null,
            'status' => 'failed',
            'error_code' => '500',
            'message' => 'Server error - retryable',
        ]);

        // Mock successful retry
        Http::fake([
            'onesignal.com/api/v1/notifications' => Http::response([
                'id' => 'retry-success-notification',
                'recipients' => ['total' => 5, 'successful' => 5],
            ], 200),
        ]);

        $response = $this->postJson("/api/v1/notifications/retry/{$failedLog->id}");

        $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => 'Notification retry successful'
                ]);

        // Verify new log was created for retry
        $this->assertDatabaseHas('notification_logs', [
            'notification_id' => 'retry-success-notification',
            'status' => 'success',
        ]);
    }

    #[Test]
    public function it_handles_concurrent_lead_submissions(): void
    {
        // Mock OneSignal for multiple requests
        Http::fake([
            'onesignal.com/api/v1/notifications' => Http::sequence()
                ->push(['id' => 'concurrent-1', 'recipients' => ['total' => 5]], 200)
                ->push(['id' => 'concurrent-2', 'recipients' => ['total' => 3]], 200)
                ->push(['id' => 'concurrent-3', 'recipients' => ['total' => 7]], 200),
        ]);

        // Submit multiple leads concurrently (simulated)
        $responses = [];
        for ($i = 1; $i <= 3; $i++) {
            $responses[] = $this->postJson('/api/v1/leads', [
                'platform_id' => $this->platform->id,
                'name' => "Concurrent User {$i}",
                'email' => "concurrent{$i}@test.com",
                'phone' => '+1234567890',
            ]);
        }

        // Verify all requests succeeded
        foreach ($responses as $response) {
            $response->assertStatus(201);
        }

        // Verify all leads were created
        $this->assertEquals(3, Lead::count());

        // Verify all notifications were logged
        $this->assertEquals(3, NotificationLog::count());
        
        // Verify unique notification IDs
        $notificationIds = NotificationLog::pluck('notification_id')->toArray();
        $this->assertCount(3, array_unique($notificationIds));
    }

    #[Test]
    public function it_provides_notification_logs_filtering(): void
    {
        // Create diverse notification logs
        NotificationLog::factory()->create([
            'status' => 'success',
            'created_at' => now()->subDays(1),
        ]);
        
        NotificationLog::factory()->create([
            'status' => 'failed',
            'error_code' => '400',
            'created_at' => now()->subHours(2),
        ]);
        
        NotificationLog::factory()->create([
            'status' => 'success',
            'created_at' => now()->subMinutes(30),
        ]);

        // Test status filtering
        $response = $this->getJson('/api/v1/notifications/logs?status=success');
        $response->assertOk();
        $this->assertCount(2, $response->json('data'));

        // Test time filtering
        $response = $this->getJson('/api/v1/notifications/logs?hours=1');
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));

        // Test pagination
        $response = $this->getJson('/api/v1/notifications/logs?per_page=2');
        $response->assertOk()
                ->assertJsonStructure([
                    'data',
                    'current_page',
                    'per_page',
                    'total'
                ]);
        $this->assertCount(2, $response->json('data'));
    }

    #[Test]
    public function it_tracks_performance_metrics(): void
    {
        // Create notifications with varying response times
        NotificationLog::factory()->create(['response_time' => 100.0, 'status' => 'success']);
        NotificationLog::factory()->create(['response_time' => 200.0, 'status' => 'success']);
        NotificationLog::factory()->create(['response_time' => 300.0, 'status' => 'failed']);

        $response = $this->getJson('/api/v1/notifications/analytics');

        $data = $response->json();
        
        $this->assertEquals(200.0, $data['average_response_time']);
        $this->assertArrayHasKey('performance_trends', $data);
        $this->assertEquals(66.67, round($data['success_rate'], 2)); // 2 success out of 3
    }

    #[Test]
    public function it_handles_malformed_lead_data_gracefully(): void
    {
        // Test with invalid email
        $response = $this->postJson('/api/v1/leads', [
            'platform_id' => $this->platform->id,
            'name' => 'Test User',
            'email' => 'invalid-email',
            'phone' => '+1234567890',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);

        // Verify no lead was created
        $this->assertEquals(0, Lead::count());
        
        // Verify no notification was attempted
        $this->assertEquals(0, NotificationLog::count());
    }

    #[Test]
    public function it_provides_error_reporting_capabilities(): void
    {
        // Create failed notifications with different error codes
        NotificationLog::factory()->create(['status' => 'failed', 'error_code' => '400']);
        NotificationLog::factory()->create(['status' => 'failed', 'error_code' => '401']);
        NotificationLog::factory()->create(['status' => 'failed', 'error_code' => '400']);
        NotificationLog::factory()->create(['status' => 'failed', 'error_code' => '500']);

        $response = $this->getJson('/api/v1/notifications/analytics');

        $data = $response->json();
        
        $this->assertArrayHasKey('error_distribution', $data);
        $errorDistribution = $data['error_distribution'];
        
        $this->assertEquals(2, $errorDistribution['400']); // Two 400 errors
        $this->assertEquals(1, $errorDistribution['401']); // One 401 error  
        $this->assertEquals(1, $errorDistribution['500']); // One 500 error
    }
}
