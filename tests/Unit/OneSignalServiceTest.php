<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\OneSignalServiceInterface;
use App\DTOs\NotificationResultDTO;
use App\Services\OneSignalService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Comprehensive unit tests for OneSignalService.
 * 
 * Tests all service methods, configuration handling, error scenarios,
 * and edge cases with proper mocking and isolation.
 */
final class OneSignalServiceTest extends TestCase
{
    use RefreshDatabase;

    private OneSignalService $service;
    private array $defaultConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultConfig = [
            'onesignal' => [
                'enabled' => true,
                'app_id' => 'test-app-id-12345',
                'rest_api_key' => 'test-api-key-67890',
                'timeout' => 30,
            ],
        ];

        Config::set($this->defaultConfig);
        $this->service = new OneSignalService();
    }

    protected function tearDown(): void
    {
        Http::preventStrayRequests(false);
        parent::tearDown();
    }

    #[Test]
    public function it_initializes_with_correct_configuration(): void
    {
        $config = $this->service->getConfiguration();

        $this->assertTrue($config['enabled']);
        $this->assertTrue($config['configured']);
        $this->assertEquals('test-app-...', $config['app_id']);
        $this->assertTrue($config['has_api_key']);
        $this->assertEquals(30, $config['timeout']);
    }

    #[Test]
    public function it_detects_when_service_is_disabled(): void
    {
        Config::set('onesignal.enabled', false);
        $service = new OneSignalService();

        $this->assertFalse($service->isEnabled());

        $config = $service->getConfiguration();
        $this->assertFalse($config['enabled']);
    }

    #[Test]
    public function it_detects_when_service_is_not_configured(): void
    {
        Config::set('onesignal.app_id', null);
        $service = new OneSignalService();

        $this->assertFalse($service->isConfigured());
        $this->assertFalse($service->isEnabled());
    }

    #[Test]
    public function it_sends_lead_submission_notification_successfully(): void
    {
        Http::fake([
            'onesignal.com/api/v1/notifications' => Http::response([
                'id' => 'notification-123',
                'recipients' => ['total' => 5, 'successful' => 5, 'failed' => 0],
            ], 200),
        ]);

        Log::shouldReceive('info')->twice(); // Sending and success messages

        $result = $this->service->sendLeadSubmissionNotification();

        $this->assertTrue($result->success);
        $this->assertEquals('notification-123', $result->notificationId);
        $this->assertEquals(['total' => 5, 'successful' => 5, 'failed' => 0], $result->recipients);
        $this->assertNotNull($result->responseTime);
        $this->assertNull($result->errorCode);
    }

    #[Test]
    public function it_sends_custom_notification_successfully(): void
    {
        Http::fake([
            'onesignal.com/api/v1/notifications' => Http::response([
                'id' => 'custom-notification-456',
                'recipients' => ['total' => 3, 'successful' => 3, 'failed' => 0],
            ], 200),
        ]);

        Log::shouldReceive('info')->twice();

        $result = $this->service->sendNotification(
            'Custom Title',
            'Custom Message',
            ['custom_key' => 'custom_value']
        );

        $this->assertTrue($result->success);
        $this->assertEquals('custom-notification-456', $result->notificationId);
        $this->assertArrayHasKey('total', $result->recipients);
    }

    #[Test]
    public function it_returns_disabled_result_when_service_is_disabled(): void
    {
        Config::set('onesignal.enabled', false);
        $service = new OneSignalService();

        Log::shouldReceive('info')->once()->with('OneSignal notification skipped - service disabled');

        $result = $service->sendLeadSubmissionNotification();

        $this->assertFalse($result->success);
        $this->assertEquals('disabled', $result->errorCode);
        $this->assertStringContains('disabled', $result->message);
    }

    #[Test]
    public function it_returns_configuration_error_when_not_configured(): void
    {
        Config::set('onesignal.app_id', null);
        $service = new OneSignalService();

        Log::shouldReceive('error')->once();

        $result = $service->sendNotification('Test', 'Test Message');

        $this->assertFalse($result->success);
        $this->assertEquals('configuration_error', $result->errorCode);
        $this->assertStringContains('not properly configured', $result->message);
    }

    #[Test]
    public function it_handles_api_error_responses(): void
    {
        Http::fake([
            'onesignal.com/api/v1/notifications' => Http::response([
                'errors' => [
                    'invalid_app_id' => 'App ID is invalid',
                ],
            ], 400),
        ]);

        Log::shouldReceive('info')->once(); // Sending message
        Log::shouldReceive('warning')->once(); // Failure message

        $result = $this->service->sendLeadSubmissionNotification();

        $this->assertFalse($result->success);
        $this->assertEquals('400', $result->errorCode);
        $this->assertNotNull($result->responseTime);
        $this->assertNotNull($result->rawResponse);
    }

    #[Test]
    public function it_handles_connection_exceptions(): void
    {
        Http::fake([
            'onesignal.com/api/v1/notifications' => function () {
                throw new ConnectionException('Connection timeout');
            },
        ]);

        Log::shouldReceive('info')->once(); // Sending message
        Log::shouldReceive('error')->once(); // Connection error

        $result = $this->service->sendLeadSubmissionNotification();

        $this->assertFalse($result->success);
        $this->assertEquals('connection_error', $result->errorCode);
        $this->assertStringContains('Failed to connect', $result->message);
        $this->assertArrayHasKey('exception', $result->errorDetails);
    }

    #[Test]
    public function it_handles_request_exceptions(): void
    {
        Http::fake([
            'onesignal.com/api/v1/notifications' => function () {
                throw new RequestException(response: Http::response('Unauthorized', 401));
            },
        ]);

        Log::shouldReceive('info')->once(); // Sending message
        Log::shouldReceive('error')->once(); // Request error

        $result = $this->service->sendLeadSubmissionNotification();

        $this->assertFalse($result->success);
        $this->assertEquals('401', $result->errorCode);
        $this->assertStringContains('API request failed', $result->message);
    }

    #[Test]
    public function it_handles_unexpected_exceptions(): void
    {
        Http::fake([
            'onesignal.com/api/v1/notifications' => function () {
                throw new Exception('Unexpected error occurred');
            },
        ]);

        Log::shouldReceive('info')->once(); // Sending message
        Log::shouldReceive('error')->once(); // Unexpected error

        $result = $this->service->sendLeadSubmissionNotification();

        $this->assertFalse($result->success);
        $this->assertEquals('unexpected_error', $result->errorCode);
        $this->assertStringContains('Unexpected error', $result->message);
    }

    #[Test]
    public function it_tests_connection_successfully(): void
    {
        Http::fake([
            'onesignal.com/api/v1/apps/test-app-id-12345' => Http::response([
                'id' => 'test-app-id-12345',
                'name' => 'Test App',
                'players' => 100,
            ], 200),
        ]);

        $result = $this->service->testConnection();

        $this->assertTrue($result->success);
        $this->assertNotNull($result->responseTime);
        $this->assertArrayHasKey('id', $result->rawResponse);
    }

    #[Test]
    public function it_handles_connection_test_failure(): void
    {
        Http::fake([
            'onesignal.com/api/v1/apps/test-app-id-12345' => Http::response([
                'errors' => ['App not found'],
            ], 404),
        ]);

        $result = $this->service->testConnection();

        $this->assertFalse($result->success);
        $this->assertEquals('404', $result->errorCode);
        $this->assertStringContains('Connection test failed', $result->message);
    }

    #[Test]
    public function it_handles_connection_test_exception(): void
    {
        Http::fake([
            'onesignal.com/api/v1/apps/test-app-id-12345' => function () {
                throw new Exception('Network error');
            },
        ]);

        $result = $this->service->testConnection();

        $this->assertFalse($result->success);
        $this->assertEquals('connection_test_exception', $result->errorCode);
        $this->assertStringContains('Network error', $result->message);
    }

    #[Test]
    public function it_gets_app_info_successfully(): void
    {
        Http::fake([
            'onesignal.com/api/v1/apps/test-app-id-12345' => Http::response([
                'id' => 'test-app-id-12345',
                'name' => 'Test App',
                'players' => 150,
                'messageable_players' => 140,
            ], 200),
        ]);

        $appInfo = $this->service->getAppInfo();

        $this->assertNotEmpty($appInfo);
        $this->assertEquals('test-app-id-12345', $appInfo['id']);
        $this->assertEquals('Test App', $appInfo['name']);
        $this->assertEquals(150, $appInfo['players']);
    }

    #[Test]
    public function it_returns_empty_array_for_app_info_when_not_configured(): void
    {
        Config::set('onesignal.app_id', null);
        $service = new OneSignalService();

        $appInfo = $service->getAppInfo();

        $this->assertEmpty($appInfo);
    }

    #[Test]
    public function it_handles_app_info_request_failure(): void
    {
        Http::fake([
            'onesignal.com/api/v1/apps/test-app-id-12345' => function () {
                throw new Exception('API error');
            },
        ]);

        Log::shouldReceive('warning')->once();

        $appInfo = $this->service->getAppInfo();

        $this->assertEmpty($appInfo);
    }

    #[Test]
    public function it_builds_correct_notification_payload(): void
    {
        Http::fake([
            'onesignal.com/api/v1/notifications' => function ($request) {
                $payload = json_decode($request->body(), true);

                // Verify payload structure
                $this->assertEquals('test-app-id-12345', $payload['app_id']);
                $this->assertEquals('Test Title', $payload['headings']['en']);
                $this->assertEquals('Test Message', $payload['contents']['en']);
                $this->assertEquals(['included_segments' => ['All']], $payload);
                $this->assertArrayHasKey('data', $payload);

                return Http::response(['id' => 'test-123'], 200);
            },
        ]);

        Log::shouldReceive('info')->twice();

        $result = $this->service->sendNotification(
            'Test Title',
            'Test Message',
            ['custom_data' => 'test_value']
        );

        $this->assertTrue($result->success);
    }

    #[Test]
    public function it_extracts_recipient_info_correctly(): void
    {
        Http::fake([
            'onesignal.com/api/v1/notifications' => Http::response([
                'id' => 'notification-789',
                'recipients' => 25,
                'external_id_count' => 20,
            ], 200),
        ]);

        Log::shouldReceive('info')->twice();

        $result = $this->service->sendLeadSubmissionNotification();

        $this->assertTrue($result->success);
        $this->assertArrayHasKey('total', $result->recipients);
        $this->assertEquals(25, $result->recipients['total']);
    }

    #[Test]
    public function it_handles_missing_api_key_configuration(): void
    {
        Config::set('onesignal.rest_api_key', '');
        $service = new OneSignalService();

        $this->assertFalse($service->isConfigured());
        $this->assertFalse($service->isEnabled());
    }

    #[Test]
    public function it_handles_missing_app_id_configuration(): void
    {
        Config::set('onesignal.app_id', '');
        $service = new OneSignalService();

        $this->assertFalse($service->isConfigured());
        $this->assertFalse($service->isEnabled());
    }

    #[Test]
    public function it_includes_proper_headers_in_api_requests(): void
    {
        Http::fake([
            'onesignal.com/api/v1/notifications' => function ($request) {
                $this->assertEquals('application/json; charset=utf-8', $request->header('Content-Type')[0]);
                $this->assertEquals('Basic test-api-key-67890', $request->header('Authorization')[0]);

                return Http::response(['id' => 'test-456'], 200);
            },
        ]);

        Log::shouldReceive('info')->twice();

        $result = $this->service->sendLeadSubmissionNotification();
        $this->assertTrue($result->success);
    }

    #[Test]
    public function it_respects_timeout_configuration(): void
    {
        Config::set('onesignal.timeout', 60);
        $service = new OneSignalService();

        $config = $service->getConfiguration();
        $this->assertEquals(60, $config['timeout']);
    }

    #[Test]
    public function it_measures_response_time_accurately(): void
    {
        Http::fake([
            'onesignal.com/api/v1/notifications' => function () {
                usleep(100000); // 100ms delay
                return Http::response(['id' => 'test-timing'], 200);
            },
        ]);

        Log::shouldReceive('info')->twice();

        $result = $this->service->sendLeadSubmissionNotification();

        $this->assertTrue($result->success);
        $this->assertGreaterThan(95, $result->responseTime); // Should be around 100ms
        $this->assertLessThan(200, $result->responseTime); // But not too much more
    }

    #[Test]
    public function it_implements_onesignal_service_interface(): void
    {
        $this->assertInstanceOf(OneSignalServiceInterface::class, $this->service);
    }

    #[Test]
    public function it_logs_notification_attempts_correctly(): void
    {
        Http::fake([
            'onesignal.com/api/v1/notifications' => Http::response(['id' => 'log-test'], 200),
        ]);

        Log::shouldReceive('info')->once()->with('Sending OneSignal notification', [
            'title' => 'Test Log Title',
            'message' => 'Test log message',
            'additional_data' => ['test' => 'data'],
        ]);

        Log::shouldReceive('info')->once()->with('OneSignal notification sent successfully', \Mockery::any());

        $this->service->sendNotification('Test Log Title', 'Test log message', ['test' => 'data']);
    }

    #[Test]
    public function it_handles_malformed_api_responses(): void
    {
        Http::fake([
            'onesignal.com/api/v1/notifications' => Http::response('Not JSON', 200),
        ]);

        Log::shouldReceive('info')->once(); // Sending message
        Log::shouldReceive('error')->once(); // JSON parsing error

        $result = $this->service->sendLeadSubmissionNotification();

        // Should handle malformed response gracefully
        $this->assertFalse($result->success);
    }
}
