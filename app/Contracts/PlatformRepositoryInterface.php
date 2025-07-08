<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\WebsiteType;
use App\Models\Platform;
use Illuminate\Database\Eloquent\Collection;

/**
 * Platform Repository Contract
 * 
 * Defines the interface for platform data operations.
 */
interface PlatformRepositoryInterface
{
    /**
     * Get platforms supporting specific website type
     */
    public function getByWebsiteType(WebsiteType $websiteType): Collection;

    /**
     * Get all active platforms
     */
    public function getAllActive(): Collection;

    /**
     * Find platform by slug
     */
    public function findBySlug(string $slug): ?Platform;
} 