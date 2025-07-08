<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PlatformRepositoryInterface;
use App\Contracts\PlatformServiceInterface;
use App\Enums\WebsiteType;
use App\Models\Platform;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Service for handling platform business logic.
 * 
 * Manages platform retrieval, caching, and website type filtering
 * with proper cache invalidation strategies.
 */
final class PlatformService implements PlatformServiceInterface
{
    private const CACHE_TTL = 24 * 60 * 60; // 24 hours in seconds
    private const CACHE_KEY_PREFIX = 'platforms';

    public function __construct(
        private readonly PlatformRepositoryInterface $platformRepository
    ) {}

    /**
     * Get platforms filtered by website type with caching.
     * 
     * @param WebsiteType $websiteType The website type to filter by
     * @return Collection<int, Platform> Collection of platforms
     */
    public function getPlatformsForWebsiteType(WebsiteType $websiteType): Collection
    {
        $cacheKey = self::CACHE_KEY_PREFIX . ":website_type:{$websiteType->value}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($websiteType) {
            return $this->platformRepository->getByWebsiteType($websiteType);
        });
    }

    /**
     * Get all active platforms with caching.
     * 
     * @return Collection<int, Platform> Collection of active platforms
     */
    public function getAllActivePlatforms(): Collection
    {
        $cacheKey = self::CACHE_KEY_PREFIX . ':all_active';
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return $this->platformRepository->getAllActive();
        });
    }

    /**
     * Find a platform by slug.
     * 
     * @param string $slug The platform slug to search for
     * @return Platform|null The found platform or null if not found
     */
    public function findPlatformBySlug(string $slug): ?Platform
    {
        $cacheKey = self::CACHE_KEY_PREFIX . ":slug:{$slug}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($slug) {
            return $this->platformRepository->findBySlug($slug);
        });
    }

    /**
     * Clear cached platform data.
     * 
     * @return void
     */
    public function clearPlatformCache(): void
    {
        // Clear all platform-related cache keys
        Cache::forget(self::CACHE_KEY_PREFIX . ':all_active');
        
        // Clear website type specific caches
        foreach (WebsiteType::cases() as $websiteType) {
            Cache::forget(self::CACHE_KEY_PREFIX . ":website_type:{$websiteType->value}");
        }
        
        // Note: Individual slug caches would need to be cleared when platforms are modified
        // This could be improved with cache tags if using Redis
    }
} 