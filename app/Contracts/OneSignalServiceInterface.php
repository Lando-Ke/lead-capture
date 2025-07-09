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
     *
     * @return NotificationResultDTO
     */
    public function sendLeadSubmissionNotification(): NotificationResultDTO;

    /**
     * Send a custom notification message to all users.
     *
     * @param string $title
     * @param string $message
     * @param array $additionalData
     * @return NotificationResultDTO
     */
    public function sendNotification(string $title, string $message, array $additionalData = []): NotificationResultDTO;

    /**
     * Check if OneSignal service is enabled and configured.
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Test connection to OneSignal API.
     *
     * @return NotificationResultDTO
     */
    public function testConnection(): NotificationResultDTO;

    /**
     * Get OneSignal app information.
     *
     * @return array
     */
    public function getAppInfo(): array;

    /**
     * Get current service configuration.
     *
     * @return array
     */
    public function getConfiguration(): array;

    /**
     * Check if the service is properly configured.
     *
     * @return bool
     */
    public function isConfigured(): bool;
} 