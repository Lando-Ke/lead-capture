<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Data Transfer Object for OneSignal notification results.
 * 
 * Provides consistent response handling for notification operations
 * with proper success/error state management and debugging information.
 */
class NotificationResultDTO
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $message = null,
        public readonly ?string $notificationId = null,
        public readonly ?array $recipients = null,
        public readonly ?string $errorCode = null,
        public readonly ?array $errorDetails = null,
        public readonly ?float $responseTime = null,
        public readonly ?array $rawResponse = null
    ) {}

    /**
     * Create a successful notification result.
     *
     * @param string|null $notificationId
     * @param array|null $recipients
     * @param float|null $responseTime
     * @param array|null $rawResponse
     * @return self
     */
    public static function success(
        ?string $notificationId = null,
        ?array $recipients = null,
        ?float $responseTime = null,
        ?array $rawResponse = null
    ): self {
        return new self(
            success: true,
            message: 'Notification sent successfully',
            notificationId: $notificationId,
            recipients: $recipients,
            responseTime: $responseTime,
            rawResponse: $rawResponse
        );
    }

    /**
     * Create a failed notification result.
     *
     * @param string $message
     * @param string|null $errorCode
     * @param array|null $errorDetails
     * @param float|null $responseTime
     * @param array|null $rawResponse
     * @return self
     */
    public static function failure(
        string $message,
        ?string $errorCode = null,
        ?array $errorDetails = null,
        ?float $responseTime = null,
        ?array $rawResponse = null
    ): self {
        return new self(
            success: false,
            message: $message,
            errorCode: $errorCode,
            errorDetails: $errorDetails,
            responseTime: $responseTime,
            rawResponse: $rawResponse
        );
    }

    /**
     * Create a disabled service result.
     *
     * @return self
     */
    public static function disabled(): self
    {
        return new self(
            success: false,
            message: 'OneSignal notifications are disabled'
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'notification_id' => $this->notificationId,
            'recipients' => $this->recipients,
            'error_code' => $this->errorCode,
            'error_details' => $this->errorDetails,
            'response_time' => $this->responseTime,
            'raw_response' => $this->rawResponse,
        ];
    }

    /**
     * Get user-friendly status message.
     *
     * @return string
     */
    public function getStatusMessage(): string
    {
        if ($this->success) {
            return $this->notificationId 
                ? "Notification sent successfully (ID: {$this->notificationId})"
                : "Notification queued successfully";
        }

        return $this->message ?? 'Notification failed';
    }

    /**
     * Check if this is a temporary failure that should be retried.
     *
     * @return bool
     */
    public function isRetryable(): bool
    {
        if ($this->success) {
            return false;
        }

        // Common retryable error codes
        $retryableErrors = [
            'timeout',
            'network_error',
            'rate_limit',
            'server_error',
            '429', // Too Many Requests
            '500', // Internal Server Error
            '502', // Bad Gateway
            '503', // Service Unavailable
            '504', // Gateway Timeout
        ];

        return $this->errorCode && in_array($this->errorCode, $retryableErrors, true);
    }
} 