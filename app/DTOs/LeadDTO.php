<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\WebsiteType;

/**
 * Lead Data Transfer Object.
 *
 * Immutable value object representing lead submission data.
 * Handles data validation and transformation between request/database layers.
 */
final class LeadDTO
{
    /**
     * @param string      $name        Full name of the lead
     * @param string      $email       Email address of the lead
     * @param string|null $company     Company name (optional)
     * @param string|null $websiteUrl  Website URL (optional)
     * @param WebsiteType $websiteType Type of website (enum)
     * @param int|null    $platform    Platform ID for e-commerce sites (foreign key)
     */
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $company,
        public readonly ?string $websiteUrl,
        public readonly WebsiteType $websiteType,
        public readonly ?int $platform = null
    ) {
        $this->validateEmail($this->email);
        $this->validateWebsiteUrl($this->websiteUrl);
        $this->validatePlatformRequirement();
    }

    /**
     * Create DTO instance from array data (typically from request).
     *
     * @param array<string, mixed> $data
     *
     * @throws \InvalidArgumentException
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: self::validateRequired($data, 'name'),
            email: self::validateRequired($data, 'email'),
            company: $data['company'] ?? null,
            websiteUrl: $data['website_url'] ?? null,
            websiteType: WebsiteType::from($data['website_type'] ?? throw new \InvalidArgumentException('website_type is required')),
            platform: isset($data['platform_id']) ? (int) $data['platform_id'] : null
        );
    }

    /**
     * Convert DTO to array for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'company' => $this->company,
            'website_url' => $this->websiteUrl,
            'website_type' => $this->websiteType->value,
            'platform_id' => $this->platform,
        ];
    }

    /**
     * Convert DTO to array for API responses.
     *
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'company' => $this->company,
            'website_url' => $this->websiteUrl,
            'website_type' => [
                'value' => $this->websiteType->value,
                'label' => $this->websiteType->label(),
            ],
            'platform_id' => $this->platform,
        ];
    }

    /**
     * Check if this lead requires a platform selection.
     */
    public function requiresPlatform(): bool
    {
        return true;
    }

    /**
     * Validate required field exists and is not empty.
     *
     * @param array<string, mixed> $data
     *
     * @throws \InvalidArgumentException
     */
    private static function validateRequired(array $data, string $field): string
    {
        $value = $data[$field] ?? null;

        if (empty($value) || !is_string($value)) {
            throw new \InvalidArgumentException("Field '{$field}' is required and must be a non-empty string");
        }

        return trim($value);
    }

    /**
     * Validate email format.
     *
     * @throws \InvalidArgumentException
     */
    private function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email format: {$email}");
        }
    }

    /**
     * Validate website URL format if provided.
     *
     * @throws \InvalidArgumentException
     */
    private function validateWebsiteUrl(?string $url): void
    {
        if ($url !== null && !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Invalid website URL format: {$url}");
        }
    }

    /**
     * Validate platform requirement based on website type.
     *
     * @throws \InvalidArgumentException
     */
    private function validatePlatformRequirement(): void
    {
        if ($this->platform === null) {
            throw new \InvalidArgumentException('Platform selection is required');
        }
    }
}
