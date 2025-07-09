<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Simple integration tests for notification flow.
 */
final class NotificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Platform $platform;

    protected function setUp(): void
    {
        parent::setUp();

        $this->platform = Platform::factory()->create([
            'name' => 'Test Platform',
            'website_types' => ['business'],
            'is_active' => true,
        ]);

        // Configure OneSignal for testing
        config([
            'services.onesignal.enabled' => true,
            'services.onesignal.app_id' => 'test-app-id',
            'services.onesignal.rest_api_key' => 'test-api-key',
        ]);
    }

    #[Test]
    public function itCompletesLeadSubmissionWithNotification(): void
    {
        Http::fake([
            'onesignal.com/api/v1/notifications' => Http::response([
                'id' => 'notification-123',
                'recipients' => 5,
            ], 200),
        ]);

        // Use a real email that won't be filtered as test email
        $response = $this->postJson('/api/v1/leads', [
            'platform_id' => $this->platform->id,
            'name' => 'Integration User',
            'email' => 'user@integration.com', // Avoid test/demo patterns
            'company' => 'Integration Company', // Required field
            'website_type' => 'business',
        ]);

        $response->assertStatus(201);

        // Verify lead was created
        $this->assertDatabaseHas('leads', [
            'email' => 'user@integration.com',
            'name' => 'Integration User',
        ]);

        // Give the queue a moment to process if using sync driver
        if (config('queue.default') === 'sync') {
            // Check for notification log creation
            $this->assertDatabaseHas('notification_logs', [
                'lead_email' => 'user@integration.com',
                'notification_type' => 'lead_submission',
            ]);
        }
    }

    #[Test]
    public function itHandlesNotificationFailureGracefully(): void
    {
        Http::fake([
            'onesignal.com/api/v1/notifications' => Http::response([
                'errors' => ['App ID is invalid'],
            ], 400),
        ]);

        $response = $this->postJson('/api/v1/leads', [
            'platform_id' => $this->platform->id,
            'name' => 'Failure User',
            'email' => 'user@failure.com',
            'company' => 'Failure Company', // Required field
            'website_type' => 'business',
        ]);

        // Lead creation should still succeed
        $response->assertStatus(201);

        // Verify lead was created despite notification failure
        $this->assertDatabaseHas('leads', [
            'email' => 'user@failure.com',
        ]);
    }

    #[Test]
    public function itSkipsNotificationsForTestEmails(): void
    {
        Http::fake(); // No HTTP calls should be made

        $response = $this->postJson('/api/v1/leads', [
            'platform_id' => $this->platform->id,
            'name' => 'Test User',
            'email' => 'test@example.com', // This should be skipped
            'company' => 'Test Company',
            'website_type' => 'business',
        ]);

        $response->assertStatus(201);

        // Verify lead was created
        $this->assertDatabaseHas('leads', [
            'email' => 'test@example.com',
        ]);

        // Verify notification was skipped if using sync queue
        if (config('queue.default') === 'sync') {
            $this->assertDatabaseHas('notification_logs', [
                'lead_email' => 'test@example.com',
                'status' => 'skipped',
            ]);
        }

        // Verify no HTTP calls were made to OneSignal
        Http::assertNothingSent();
    }

    #[Test]
    public function itHandlesDisabledService(): void
    {
        // Disable OneSignal service
        config(['services.onesignal.enabled' => false]);

        $response = $this->postJson('/api/v1/leads', [
            'platform_id' => $this->platform->id,
            'name' => 'Disabled User',
            'email' => 'user@disabled.com',
            'company' => 'Disabled Company',
            'website_type' => 'business',
        ]);

        $response->assertStatus(201);

        // Verify lead was created
        $this->assertDatabaseHas('leads', [
            'email' => 'user@disabled.com',
        ]);

        // Verify notification was skipped due to disabled service
        if (config('queue.default') === 'sync') {
            $this->assertDatabaseHas('notification_logs', [
                'lead_email' => 'user@disabled.com',
                'status' => 'skipped',
            ]);
        }
    }

    #[Test]
    public function itValidatesRequiredFields(): void
    {
        $response = $this->postJson('/api/v1/leads', [
            'platform_id' => $this->platform->id,
            'name' => 'Test User',
            'email' => 'test@validation.com',
            // Missing required 'company' field
            'website_type' => 'business',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company']);

        // Verify no lead was created
        $this->assertDatabaseMissing('leads', [
            'email' => 'test@validation.com',
        ]);
    }
}
