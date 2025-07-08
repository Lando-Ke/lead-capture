<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\WebsiteType;

/**
 * Platform Data Transfer Object
 * 
 * Lightweight immutable value object for platform data.
 * Designed for read operations and seeding.
 */
final class PlatformDTO
{
    /**
     * @param string $name Platform display name
     * @param string $slug URL-friendly identifier
     * @param string|null $description Platform description
     * @param string|null $logo Path to platform logo
     * @param array<string> $websiteTypes Supported website types
     * @param int $sortOrder Display order
     */
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly ?string $logo,
        public readonly array $websiteTypes,
        public readonly int $sortOrder = 0
    ) {}

    /**
     * Create DTO from array data
     * 
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'] ?? null,
            logo: $data['logo'] ?? null,
            websiteTypes: $data['website_types'] ?? [],
            sortOrder: (int) ($data['sort_order'] ?? 0)
        );
    }

    /**
     * Convert to array for database operations
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'logo' => $this->logo,
            'website_types' => $this->websiteTypes,
            'is_active' => true, // Default for seeding
            'sort_order' => $this->sortOrder,
        ];
    }

    /**
     * Check if platform supports specific website type
     */
    public function supportsWebsiteType(WebsiteType $websiteType): bool
    {
        return in_array($websiteType->value, $this->websiteTypes, true);
    }
} 