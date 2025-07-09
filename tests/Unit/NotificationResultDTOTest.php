<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\NotificationResultDTO;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Simple unit tests for NotificationResultDTO focusing on core functionality.
 */
final class NotificationResultDTOTest extends TestCase
{
    #[Test]
    public function it_creates_successful_result(): void
    {
        $result = NotificationResultDTO::success(
            notificationId: 'test-123',
            recipients: ['total' => 10],
            responseTime: 250.5
        );

        $this->assertTrue($result->success);
        $this->assertEquals('test-123', $result->notificationId);
        $this->assertEquals(['total' => 10], $result->recipients);
        $this->assertEquals(250.5, $result->responseTime);
        $this->assertEquals('Notification sent successfully', $result->message);
    }

    #[Test]
    public function it_creates_failure_result(): void
    {
        $result = NotificationResultDTO::failure(
            message: 'Authentication failed',
            errorCode: '401',
            responseTime: 125.0
        );

        $this->assertFalse($result->success);
        $this->assertEquals('401', $result->errorCode);
        $this->assertEquals('Authentication failed', $result->message);
        $this->assertEquals(125.0, $result->responseTime);
    }

    #[Test]
    public function it_creates_disabled_result(): void
    {
        $result = NotificationResultDTO::disabled();

        $this->assertFalse($result->success);
        $this->assertEquals('OneSignal notifications are disabled', $result->message);
        $this->assertNull($result->notificationId);
        $this->assertNull($result->responseTime);
    }

    #[Test]
    public function it_determines_retryability_correctly(): void
    {
        // Retryable errors
        $retryable = NotificationResultDTO::failure('Server error', '500');
        $this->assertTrue($retryable->isRetryable());

        $rateLimited = NotificationResultDTO::failure('Rate limit', '429');
        $this->assertTrue($rateLimited->isRetryable());

        // Non-retryable errors
        $authError = NotificationResultDTO::failure('Auth failed', '401');
        $this->assertFalse($authError->isRetryable());

        $success = NotificationResultDTO::success();
        $this->assertFalse($success->isRetryable());
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $result = NotificationResultDTO::success(
            notificationId: 'test-456',
            recipients: ['total' => 5]
        );

        $array = $result->toArray();

        $this->assertTrue($array['success']);
        $this->assertEquals('test-456', $array['notification_id']);
        $this->assertEquals(['total' => 5], $array['recipients']);
    }

    #[Test]
    public function it_provides_status_message(): void
    {
        $success = NotificationResultDTO::success(notificationId: 'test-123');
        $this->assertEquals('Notification sent successfully (ID: test-123)', $success->getStatusMessage());

        $failure = NotificationResultDTO::failure('API error');
        $this->assertEquals('API error', $failure->getStatusMessage());
    }
}
