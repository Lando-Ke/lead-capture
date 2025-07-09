<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\OneSignalServiceInterface;
use App\DTOs\NotificationResultDTO;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OneSignal notification service implementation.
 *
 * Handles push notification sending via OneSignal API with
 * comprehensive error handling and logging capabilities.
 */
class OneSignalService implements OneSignalServiceInterface
{
    private const ONESIGNAL_API_URL = 'https://onesignal.com/api/v1';
    private const DEFAULT_TIMEOUT = 30;

    private readonly string $appId;

    private readonly string $restApiKey;

    private readonly bool $enabled;

    private readonly int $timeout;

    public function __construct()
    {
        $config = Config::get('services.onesignal', []);

        $this->appId = $config['app_id'] ?? '';
        $this->restApiKey = $config['rest_api_key'] ?? '';
        $this->enabled = (bool) ($config['enabled'] ?? false);
        $this->timeout = (int) ($config['guzzle_client_timeout'] ?? self::DEFAULT_TIMEOUT);
    }

    /**
     * Send a lead submission notification to all users.
     */
    public function sendLeadSubmissionNotification(): NotificationResultDTO
    {
        return $this->sendNotification(
            title: 'New Lead Submitted',
            message: 'A new user has submitted the registration form',
            additionalData: [
                'type' => 'lead_submission',
                'timestamp' => now()->toISOString(),
            ]
        );
    }

    /**
     * Send a custom notification message to all users.
     */
    public function sendNotification(string $title, string $message, array $additionalData = []): NotificationResultDTO
    {
        $startTime = microtime(true);

        // Check if service is enabled and configured
        if (!$this->isEnabled()) {
            Log::info('OneSignal notification skipped - service disabled');

            return NotificationResultDTO::disabled();
        }

        if (!$this->isConfigured()) {
            Log::error('OneSignal notification failed - service not configured');

            return NotificationResultDTO::failure(
                message: 'OneSignal service is not properly configured',
                errorCode: 'configuration_error'
            );
        }

        try {
            Log::info('Sending OneSignal notification', [
                'title' => $title,
                'message' => $message,
                'additional_data' => $additionalData,
            ]);

            $payload = $this->buildNotificationPayload($title, $message, $additionalData);
            $response = $this->makeApiRequest('notifications', $payload);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($response['success']) {
                Log::info('OneSignal notification sent successfully', [
                    'notification_id' => $response['data']['id'] ?? null,
                    'recipients' => $response['data']['recipients'] ?? null,
                    'response_time_ms' => $responseTime,
                ]);

                return NotificationResultDTO::success(
                    notificationId: $response['data']['id'] ?? null,
                    recipients: $this->extractRecipientInfo($response['data']),
                    responseTime: $responseTime,
                    rawResponse: $response['data']
                );
            }

            Log::warning('OneSignal notification failed', [
                'error' => $response['error'],
                'response_time_ms' => $responseTime,
            ]);

            return NotificationResultDTO::failure(
                message: $response['error']['message'] ?? 'Notification failed',
                errorCode: $response['error']['code'] ?? 'api_error',
                errorDetails: $response['error']['details'] ?? null,
                responseTime: $responseTime,
                rawResponse: $response['error']
            );
        } catch (ConnectionException $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            Log::error('OneSignal connection failed', [
                'error' => $e->getMessage(),
                'response_time_ms' => $responseTime,
            ]);

            return NotificationResultDTO::failure(
                message: 'Failed to connect to OneSignal API',
                errorCode: 'connection_error',
                errorDetails: ['exception' => $e->getMessage()],
                responseTime: $responseTime
            );
        } catch (RequestException $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            Log::error('OneSignal request failed', [
                'error' => $e->getMessage(),
                'status_code' => $e->response?->status(),
                'response_time_ms' => $responseTime,
            ]);

            return NotificationResultDTO::failure(
                message: 'OneSignal API request failed',
                errorCode: (string) ($e->response?->status() ?? 'request_error'),
                errorDetails: ['exception' => $e->getMessage()],
                responseTime: $responseTime
            );
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            Log::error('OneSignal unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'response_time_ms' => $responseTime,
            ]);

            return NotificationResultDTO::failure(
                message: 'Unexpected error occurred',
                errorCode: 'unexpected_error',
                errorDetails: ['exception' => $e->getMessage()],
                responseTime: $responseTime
            );
        }
    }

    /**
     * Check if OneSignal service is enabled and configured.
     */
    public function isEnabled(): bool
    {
        return $this->enabled && $this->isConfigured();
    }

    /**
     * Test connection to OneSignal API.
     */
    public function testConnection(): NotificationResultDTO
    {
        $startTime = microtime(true);

        if (!$this->isConfigured()) {
            return NotificationResultDTO::failure(
                message: 'OneSignal service is not configured',
                errorCode: 'configuration_error'
            );
        }

        try {
            $response = $this->makeApiRequest("apps/{$this->appId}", method: 'GET');
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($response['success']) {
                return NotificationResultDTO::success(
                    responseTime: $responseTime,
                    rawResponse: $response['data']
                );
            }

            return NotificationResultDTO::failure(
                message: 'Connection test failed',
                errorCode: $response['error']['code'] ?? 'connection_test_failed',
                responseTime: $responseTime
            );
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return NotificationResultDTO::failure(
                message: 'Connection test failed: ' . $e->getMessage(),
                errorCode: 'connection_test_exception',
                responseTime: $responseTime
            );
        }
    }

    /**
     * Get OneSignal app information.
     */
    public function getAppInfo(): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        try {
            $response = $this->makeApiRequest("apps/{$this->appId}", method: 'GET');

            return $response['success'] ? $response['data'] : [];
        } catch (\Exception $e) {
            Log::warning('Failed to fetch OneSignal app info', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Get current service configuration.
     */
    public function getConfiguration(): array
    {
        return [
            'enabled' => $this->enabled,
            'configured' => $this->isConfigured(),
            'app_id' => $this->appId ? substr($this->appId, 0, 8) . '...' : null,
            'has_api_key' => !empty($this->restApiKey),
            'timeout' => $this->timeout,
        ];
    }

    /**
     * Check if the service is properly configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->appId) && !empty($this->restApiKey);
    }

    /**
     * Build notification payload for OneSignal API.
     */
    private function buildNotificationPayload(string $title, string $message, array $additionalData = []): array
    {
        return [
            'app_id' => $this->appId,
            'included_segments' => ['All'],
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
            'data' => array_merge([
                'sent_at' => now()->toISOString(),
            ], $additionalData),
        ];
    }

    /**
     * Make API request to OneSignal.
     */
    private function makeApiRequest(string $endpoint, array $data = [], string $method = 'POST'): array
    {
        $url = self::ONESIGNAL_API_URL . '/' . ltrim($endpoint, '/');

        $httpClient = Http::timeout($this->timeout)
            ->withHeaders([
                'Authorization' => 'Basic ' . $this->restApiKey,
                'Content-Type' => 'application/json',
            ]);

        $response = match (strtoupper($method)) {
            'GET' => $httpClient->get($url),
            'POST' => $httpClient->post($url, $data),
            default => throw new \Exception("Unsupported HTTP method: {$method}")
        };

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => [
                'code' => (string) $response->status(),
                'message' => $response->json('errors.0') ?? $response->body(),
                'details' => $response->json(),
            ],
        ];
    }

    /**
     * Extract recipient information from API response.
     */
    private function extractRecipientInfo(array $responseData): ?array
    {
        return [
            'total' => $responseData['recipients'] ?? 0,
            'successful' => $responseData['recipients'] ?? 0,
            'failed' => 0,
        ];
    }
}
