<?php
// app/Repositories/PlatformRepository.php
namespace App\Repositories;

use App\Enums\WebsiteType;
use App\Models\Platform;
use Illuminate\Database\Eloquent\Collection;

class PlatformRepository implements PlatformRepositoryInterface
{
    public function getByWebsiteType(WebsiteType $websiteType): Collection
    {
        return Platform::active()
            ->forWebsiteType($websiteType->value)
            ->ordered()
            ->get();
    }

    public function getAllActive(): Collection
    {
        return Platform::active()
            ->ordered()
            ->get();
    }

    public function findBySlug(string $slug): ?Platform
    {
        return Platform::where('slug', $slug)
            ->active()
            ->first();
    }
} 