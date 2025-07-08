<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\WebsiteType;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface for platform service operations.
 * 
 * Defines the contract for platform business logic including retrieval,
 * caching, and website type filtering operations.
 */
interface PlatformServiceInterface
{
    /**
     * Get platforms filtered by website type with caching.
     * 
     * @param WebsiteType $websiteType The website type to filter by
     * @return Collection<int, \App\Models\Platform> Collection of platforms
     */
    public function getPlatformsForWebsiteType(WebsiteType $websiteType): Collection;

    /**
     * Get all active platforms with caching.
     * 
     * @return Collection<int, \App\Models\Platform> Collection of active platforms
     */
    public function getAllActivePlatforms(): Collection;

    /**
     * Find a platform by slug.
     * 
     * @param string $slug The platform slug to search for
     * @return \App\Models\Platform|null The found platform or null if not found
     */
    public function findPlatformBySlug(string $slug): ?\App\Models\Platform;

    /**
     * Clear cached platform data.
     * 
     * @return void
     */
    public function clearPlatformCache(): void;
} 