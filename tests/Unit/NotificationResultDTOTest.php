<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\NotificationResultDTO;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Comprehensive unit tests for NotificationResultDTO.
 * 
 * Tests all factory methods, properties, validation,
 * retryability logic, and edge cases.
 */
final class NotificationResultDTOTest extends TestCase
{
    #[Test]
    public function it_creates_successful_result_correctly(): void
    {
        $recipients = ['total' => 10, 'successful' => 10, 'failed' => 0];
        $rawResponse = ['id' => 'test-123', 'recipients' => $recipients];
        
        $result = NotificationResultDTO::success(
            notificationId: 'test-123',
            recipients: $recipients,
            responseTime: 250.5,
            rawResponse: $rawResponse
        );

        $this->assertTrue($result->success);
        $this->assertEquals('test-123', $result->notificationId);
        $this->assertEquals($recipients, $result->recipients);
        $this->assertEquals(250.5, $result->responseTime);
        $this->assertEquals($rawResponse, $result->rawResponse);
        $this->assertNull($result->errorCode);
        $this->assertNull($result->message);
        $this->assertEmpty($result->errorDetails);
        $this->assertFalse($result->isRetryable());
    }

    #[Test]
    public function it_creates_failure_result_correctly(): void
    {
        $errorDetails = ['api_error' => 'Invalid API key'];
        
        $result = NotificationResultDTO::failure(
            errorCode: '401',
            message: 'Authentication failed',
            responseTime: 125.0,
            errorDetails: $errorDetails,
            rawResponse: ['error' => 'Unauthorized']
        );

        $this->assertFalse($result->success);
        $this->assertEquals('401', $result->errorCode);
        $this->assertEquals('Authentication failed', $result->message);
        $this->assertEquals(125.0, $result->responseTime);
        $this->assertEquals($errorDetails, $result->errorDetails);
        $this->assertEquals(['error' => 'Unauthorized'], $result->rawResponse);
        $this->assertNull($result->notificationId);
        $this->assertEmpty($result->recipients);
        $this->assertFalse($result->isRetryable()); // 401 is not retryable
    }

    #[Test]
    public function it_creates_disabled_result_correctly(): void
    {
        $result = NotificationResultDTO::disabled();

        $this->assertFalse($result->success);
        $this->assertEquals('disabled', $result->errorCode);
        $this->assertEquals('OneSignal service is disabled', $result->message);
        $this->assertNull($result->notificationId);
        $this->assertEmpty($result->recipients);
        $this->assertNull($result->responseTime);
        $this->assertNull($result->rawResponse);
        $this->assertEmpty($result->errorDetails);
        $this->assertFalse($result->isRetryable());
    }

    #[Test]
    public function it_creates_configuration_error_result_correctly(): void
    {
        $result = NotificationResultDTO::configurationError('Missing API key');

        $this->assertFalse($result->success);
        $this->assertEquals('configuration_error', $result->errorCode);
        $this->assertEquals('Missing API key', $result->message);
        $this->assertNull($result->notificationId);
        $this->assertEmpty($result->recipients);
        $this->assertNull($result->responseTime);
        $this->assertNull($result->rawResponse);
        $this->assertEmpty($result->errorDetails);
        $this->assertFalse($result->isRetryable());
    }

    #[Test]
    public function it_creates_connection_error_result_correctly(): void
    {
        $errorDetails = ['exception' => 'Connection timeout after 30s'];
        
        $result = NotificationResultDTO::connectionError(
            'Failed to connect to OneSignal',
            $errorDetails
        );

        $this->assertFalse($result->success);
        $this->assertEquals('connection_error', $result->errorCode);
        $this->assertEquals('Failed to connect to OneSignal', $result->message);
        $this->assertEquals($errorDetails, $result->errorDetails);
        $this->assertNull($result->notificationId);
        $this->assertEmpty($result->recipients);
        $this->assertNull($result->responseTime);
        $this->assertNull($result->rawResponse);
        $this->assertTrue($result->isRetryable()); // Connection errors are retryable
    }

    #[Test]
    public function it_determines_retryability_correctly(): void
    {
        // Retryable error codes (5xx server errors, connection issues)
        $retryableCodes = ['500', '502', '503', '504', 'connection_error', 'timeout', '429'];
        
        foreach ($retryableCodes as $code) {
            $result = NotificationResultDTO::failure($code, 'Test error');
            $this->assertTrue($result->isRetryable(), "Error code {$code} should be retryable");
        }
        
        // Non-retryable error codes (4xx client errors, configuration issues)
        $nonRetryableCodes = ['400', '401', '403', '404', 'disabled', 'configuration_error', 'validation_error'];
        
        foreach ($nonRetryableCodes as $code) {
            $result = NotificationResultDTO::failure($code, 'Test error');
            $this->assertFalse($result->isRetryable(), "Error code {$code} should not be retryable");
        }
    }

    #[Test]
    public function it_handles_null_notification_id_in_success(): void
    {
        $result = NotificationResultDTO::success(
            notificationId: null,
            recipients: ['total' => 5],
            responseTime: 100.0
        );

        $this->assertTrue($result->success);
        $this->assertNull($result->notificationId);
        $this->assertNotEmpty($result->recipients);
    }

    #[Test]
    public function it_handles_empty_recipients_array(): void
    {
        $result = NotificationResultDTO::success(
            notificationId: 'test-empty',
            recipients: [],
            responseTime: 50.0
        );

        $this->assertTrue($result->success);
        $this->assertEmpty($result->recipients);
    }

    #[Test]
    public function it_handles_zero_response_time(): void
    {
        $result = NotificationResultDTO::success(
            notificationId: 'test-zero-time',
            recipients: ['total' => 1],
            responseTime: 0.0
        );

        $this->assertTrue($result->success);
        $this->assertEquals(0.0, $result->responseTime);
    }

    #[Test]
    public function it_handles_null_response_time(): void
    {
        $result = NotificationResultDTO::failure('500', 'Server error', null);

        $this->assertFalse($result->success);
        $this->assertNull($result->responseTime);
    }

    #[Test]
    public function it_handles_empty_error_details(): void
    {
        $result = NotificationResultDTO::failure('400', 'Bad request', 100.0, []);

        $this->assertFalse($result->success);
        $this->assertEmpty($result->errorDetails);
    }

    #[Test]
    public function it_handles_null_error_details(): void
    {
        $result = NotificationResultDTO::failure('500', 'Internal error', 200.0, null);

        $this->assertFalse($result->success);
        $this->assertNull($result->errorDetails);
    }

    #[Test]
    public function it_handles_complex_recipients_data(): void
    {
        $complexRecipients = [
            'total' => 100,
            'successful' => 95,
            'failed' => 5,
            'converted' => 85,
            'error_count' => [
                'invalid_player_id' => 3,
                'invalid_email' => 2
            ]
        ];

        $result = NotificationResultDTO::success(
            notificationId: 'complex-test',
            recipients: $complexRecipients,
            responseTime: 300.0
        );

        $this->assertTrue($result->success);
        $this->assertEquals($complexRecipients, $result->recipients);
        $this->assertEquals(100, $result->recipients['total']);
        $this->assertEquals(95, $result->recipients['successful']);
        $this->assertArrayHasKey('error_count', $result->recipients);
    }

    #[Test]
    public function it_handles_complex_raw_response_data(): void
    {
        $complexResponse = [
            'id' => 'notification-complex',
            'recipients' => ['total' => 50],
            'external_id_count' => 45,
            'errors' => [],
            'url' => 'https://onesignal.com/api/v1/notifications/notification-complex',
            'platform_delivery_stats' => [
                'ios' => ['successful' => 25, 'failed' => 0],
                'android' => ['successful' => 25, 'failed' => 0]
            ]
        ];

        $result = NotificationResultDTO::success(
            notificationId: 'notification-complex',
            recipients: ['total' => 50],
            responseTime: 450.0,
            rawResponse: $complexResponse
        );

        $this->assertTrue($result->success);
        $this->assertEquals($complexResponse, $result->rawResponse);
        $this->assertArrayHasKey('platform_delivery_stats', $result->rawResponse);
        $this->assertArrayHasKey('url', $result->rawResponse);
    }

    #[Test]
    public function it_handles_complex_error_details(): void
    {
        $complexErrorDetails = [
            'exception' => 'GuzzleHttp\Exception\ConnectException',
            'message' => 'cURL error 28: Connection timed out after 30001 milliseconds',
            'code' => 28,
            'file' => '/vendor/guzzlehttp/guzzle/src/Handler/CurlHandler.php',
            'line' => 389,
            'trace' => [
                'GuzzleHttp\Handler\CurlHandler->request()',
                'GuzzleHttp\Handler\Proxy->handle()',
                'GuzzleHttp\PrepareBodyMiddleware->handle()'
            ],
            'context' => [
                'url' => 'https://onesignal.com/api/v1/notifications',
                'method' => 'POST',
                'timeout' => 30
            ]
        ];

        $result = NotificationResultDTO::connectionError(
            'Connection timeout occurred',
            $complexErrorDetails
        );

        $this->assertFalse($result->success);
        $this->assertEquals('connection_error', $result->errorCode);
        $this->assertEquals($complexErrorDetails, $result->errorDetails);
        $this->assertEquals(28, $result->errorDetails['code']);
        $this->assertArrayHasKey('trace', $result->errorDetails);
        $this->assertArrayHasKey('context', $result->errorDetails);
        $this->assertTrue($result->isRetryable());
    }

    #[Test]
    public function it_handles_edge_case_error_codes(): void
    {
        // Test some edge case error codes
        $edgeCases = [
            ['', false], // Empty string
            ['999', false], // Unknown code
            ['xxx', false], // Non-numeric
            ['429', true], // Rate limiting (retryable)
            ['522', true], // CloudFlare timeout
            ['524', true], // CloudFlare timeout
        ];

        foreach ($edgeCases as [$code, $shouldBeRetryable]) {
            $result = NotificationResultDTO::failure($code, 'Test message');
            $this->assertEquals(
                $shouldBeRetryable,
                $result->isRetryable(),
                "Error code '{$code}' retryability expectation failed"
            );
        }
    }

    #[Test]
    public function it_handles_very_long_response_times(): void
    {
        $longResponseTime = 99999.999;
        
        $result = NotificationResultDTO::success(
            notificationId: 'slow-test',
            recipients: ['total' => 1],
            responseTime: $longResponseTime
        );

        $this->assertTrue($result->success);
        $this->assertEquals($longResponseTime, $result->responseTime);
    }

    #[Test]
    public function it_handles_negative_response_times(): void
    {
        // This shouldn't happen in practice, but test graceful handling
        $result = NotificationResultDTO::failure('500', 'Test', -1.0);

        $this->assertFalse($result->success);
        $this->assertEquals(-1.0, $result->responseTime);
    }

    #[Test]
    public function it_handles_very_long_error_messages(): void
    {
        $longMessage = str_repeat('This is a very long error message. ', 100);
        
        $result = NotificationResultDTO::failure('400', $longMessage);

        $this->assertFalse($result->success);
        $this->assertEquals($longMessage, $result->message);
        $this->assertGreaterThan(1000, strlen($result->message));
    }

    #[Test]
    public function it_handles_special_characters_in_notification_id(): void
    {
        $specialId = 'notification-123_test@domain.com#section?query=value&param=test';
        
        $result = NotificationResultDTO::success(
            notificationId: $specialId,
            recipients: ['total' => 1],
            responseTime: 100.0
        );

        $this->assertTrue($result->success);
        $this->assertEquals($specialId, $result->notificationId);
    }

    #[Test]
    public function it_handles_unicode_characters_in_messages(): void
    {
        $unicodeMessage = 'Test message with émojis 🚀 and special chars: αβγ δεζ 中文 العربية';
        
        $result = NotificationResultDTO::failure('400', $unicodeMessage);

        $this->assertFalse($result->success);
        $this->assertEquals($unicodeMessage, $result->message);
        $this->assertStringContains('🚀', $result->message);
        $this->assertStringContains('中文', $result->message);
    }

    #[Test]
    public function it_handles_null_values_in_complex_arrays(): void
    {
        $recipientsWithNulls = [
            'total' => 10,
            'successful' => null,
            'failed' => 0,
            'invalid' => null
        ];

        $result = NotificationResultDTO::success(
            notificationId: 'null-test',
            recipients: $recipientsWithNulls,
            responseTime: 150.0
        );

        $this->assertTrue($result->success);
        $this->assertEquals($recipientsWithNulls, $result->recipients);
        $this->assertNull($result->recipients['successful']);
        $this->assertNull($result->recipients['invalid']);
    }

    #[Test]
    public function it_preserves_data_types_in_raw_response(): void
    {
        $typedResponse = [
            'id' => 'type-test',
            'recipients' => 25, // integer
            'converted' => 22.5, // float
            'sent' => true, // boolean
            'errors' => [], // empty array
            'metadata' => null, // null
            'timestamp' => '2023-12-01T10:30:00Z' // string
        ];

        $result = NotificationResultDTO::success(
            notificationId: 'type-test',
            recipients: ['total' => 25],
            responseTime: 200.0,
            rawResponse: $typedResponse
        );

        $this->assertTrue($result->success);
        $this->assertIsInt($result->rawResponse['recipients']);
        $this->assertIsFloat($result->rawResponse['converted']);
        $this->assertIsBool($result->rawResponse['sent']);
        $this->assertIsArray($result->rawResponse['errors']);
        $this->assertNull($result->rawResponse['metadata']);
        $this->assertIsString($result->rawResponse['timestamp']);
    }

    #[Test]
    public function it_handles_deeply_nested_error_details(): void
    {
        $deeplyNested = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => [
                            'error' => 'Deep error',
                            'code' => 12345,
                            'details' => ['key' => 'value']
                        ]
                    ]
                ]
            ]
        ];

        $result = NotificationResultDTO::failure('500', 'Nested error', 100.0, $deeplyNested);

        $this->assertFalse($result->success);
        $this->assertEquals('Deep error', $result->errorDetails['level1']['level2']['level3']['level4']['error']);
        $this->assertEquals(12345, $result->errorDetails['level1']['level2']['level3']['level4']['code']);
    }
}
