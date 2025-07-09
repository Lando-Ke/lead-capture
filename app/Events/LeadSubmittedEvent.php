<?php

declare(strict_types=1);

namespace App\Events;

use App\DTOs\LeadDTO;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a new lead is submitted.
 * 
 * This event triggers asynchronous notification processing
 * to avoid blocking the user's form submission experience.
 */
class LeadSubmittedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param LeadDTO $leadData
     * @param array $additionalMetadata
     */
    public function __construct(
        public readonly LeadDTO $leadData,
        public readonly array $additionalMetadata = []
    ) {}

    /**
     * Get the lead's email for notification purposes.
     *
     * @return string
     */
    public function getLeadEmail(): string
    {
        return $this->leadData->email;
    }

    /**
     * Get formatted lead information for notifications.
     *
     * @return array
     */
    public function getLeadInfo(): array
    {
        return [
            'email' => $this->leadData->email,
            'name' => $this->leadData->name,
            'platform' => $this->leadData->platform,
            'website_type' => $this->leadData->websiteType?->value,
            'submitted_at' => now()->toISOString(),
        ];
    }

    /**
     * Get event metadata for logging and debugging.
     *
     * @return array
     */
    public function getEventMetadata(): array
    {
        return array_merge([
            'event_type' => 'lead_submitted',
            'timestamp' => now()->toISOString(),
            'lead_email' => $this->leadData->email,
            'platform' => $this->leadData->platform,
        ], $this->additionalMetadata);
    }

    /**
     * Check if this event should trigger notifications.
     *
     * @return bool
     */
    public function shouldTriggerNotifications(): bool
    {
        // Add any business logic here to determine if notifications should be sent
        // For example, exclude test emails, check platform settings, etc.
        
        // Skip notifications for test/demo emails
        $testEmailPatterns = [
            'test@',
            'demo@',
            'example@',
            '+test',
            '@test.',
        ];
        
        $email = strtolower($this->leadData->email);
        foreach ($testEmailPatterns as $pattern) {
            if (str_contains($email, $pattern)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get notification data for OneSignal.
     *
     * @return array
     */
    public function getNotificationData(): array
    {
        return [
            'type' => 'lead_submission',
            'lead_email' => $this->leadData->email,
            'lead_name' => $this->leadData->name,
            'platform' => $this->leadData->platform,
            'website_type' => $this->leadData->websiteType?->value,
            'timestamp' => now()->toISOString(),
            'metadata' => $this->additionalMetadata,
        ];
    }
} 