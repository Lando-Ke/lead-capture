<?php
// app/Repositories/PlatformRepositoryInterface.php
namespace App\Repositories;

use App\Enums\WebsiteType;
use Illuminate\Database\Eloquent\Collection;

interface PlatformRepositoryInterface
{
    public function getByWebsiteType(WebsiteType $websiteType): Collection;
    public function getAllActive(): Collection;
    public function findBySlug(string $slug): ?\App\Models\Platform;
} 