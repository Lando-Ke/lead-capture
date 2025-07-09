<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\OneSignalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Simple unit tests for OneSignalService focusing on core functionality.
 */
final class OneSignalServiceTest extends TestCase
{
    use RefreshDatabase;

    private OneSignalService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure OneSignal for testing
        Config::set('services.onesignal', [
            'enabled' => true,
            'app_id' => 'test-app-id',
            'rest_api_key' => 'test-api-key',
            'guzzle_client_timeout' => 30,
        ]);

        $this->service = new OneSignalService();
    }

    #[Test]
    public function itSendsLeadSubmissionNotificationSuccessfully(): void
    {
        Http::fake([
            'onesignal.com/api/v1/notifications' => Http::response([
                'id' => 'notification-123',
                'recipients' => 5,
            ], 200),
        ]);

        $result = $this->service->sendLeadSubmissionNotification();

        $this->assertTrue($result->success);
        $this->assertEquals('notification-123', $result->notificationId);
        $this->assertNotNull($result->responseTime);
    }

    #[Test]
    public function itSendsCustomNotificationSuccessfully(): void
    {
        Http::fake([
            'onesignal.com/api/v1/notifications' => Http::response([
                'id' => 'custom-123',
                'recipients' => 3,
            ], 200),
        ]);

        $result = $this->service->sendNotification('Test Title', 'Test Message');

        $this->assertTrue($result->success);
        $this->assertEquals('custom-123', $result->notificationId);
    }

    #[Test]
    public function itReturnsDisabledResultWhenServiceIsDisabled(): void
    {
        Config::set('services.onesignal.enabled', false);
        $service = new OneSignalService();

        $result = $service->sendLeadSubmissionNotification();

        $this->assertFalse($result->success);
        $this->assertStringContainsString('disabled', $result->message);
    }

    #[Test]
    public function itHandlesApiErrorResponses(): void
    {
        Http::fake([
            'onesignal.com/api/v1/notifications' => Http::response([
                'errors' => ['invalid_app_id'],
            ], 400),
        ]);

        $result = $this->service->sendLeadSubmissionNotification();

        $this->assertFalse($result->success);
        $this->assertNotNull($result->responseTime);
    }

    #[Test]
    public function itTestsConnectionSuccessfully(): void
    {
        Http::fake([
            'onesignal.com/api/v1/apps/test-app-id' => Http::response([
                'id' => 'test-app-id',
                'name' => 'Test App',
            ], 200),
        ]);

        $result = $this->service->testConnection();

        $this->assertTrue($result->success);
        $this->assertNotNull($result->responseTime);
    }

    #[Test]
    public function itChecksIfServiceIsEnabled(): void
    {
        $this->assertTrue($this->service->isEnabled());

        Config::set('services.onesignal.enabled', false);
        $disabledService = new OneSignalService();
        $this->assertFalse($disabledService->isEnabled());
    }

    #[Test]
    public function itGetsConfiguration(): void
    {
        $config = $this->service->getConfiguration();

        $this->assertTrue($config['enabled']);
        $this->assertTrue($config['configured']);
        $this->assertEquals(30, $config['timeout']);
    }

    #[Test]
    public function itGetsAppInfo(): void
    {
        Http::fake([
            'onesignal.com/api/v1/apps/test-app-id' => Http::response([
                'id' => 'test-app-id',
                'name' => 'Test App',
                'players' => 100,
            ], 200),
        ]);

        $appInfo = $this->service->getAppInfo();

        $this->assertNotEmpty($appInfo);
        $this->assertEquals('test-app-id', $appInfo['id']);
        $this->assertEquals('Test App', $appInfo['name']);
    }
}
