<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\NotificationResultDTO;

/**
 * Interface for OneSignal notification service.
 *
 * Defines contract for sending push notifications via OneSignal API
 * with proper error handling and service management capabilities.
 */
interface OneSignalServiceInterface
{
    /**
     * Send a lead submission notification to all users.
     */
    public function sendLeadSubmissionNotification(): NotificationResultDTO;

    /**
     * Send a custom notification message to all users.
     */
    public function sendNotification(string $title, string $message, array $additionalData = []): NotificationResultDTO;

    /**
     * Check if OneSignal service is enabled and configured.
     */
    public function isEnabled(): bool;

    /**
     * Test connection to OneSignal API.
     */
    public function testConnection(): NotificationResultDTO;

    /**
     * Get OneSignal app information.
     */
    public function getAppInfo(): array;

    /**
     * Get current service configuration.
     */
    public function getConfiguration(): array;

    /**
     * Check if the service is properly configured.
     */
    public function isConfigured(): bool;
}
