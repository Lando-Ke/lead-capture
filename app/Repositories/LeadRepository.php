<?php
// app/Repositories/LeadRepository.php
namespace App\Repositories;

use App\Contracts\LeadRepositoryInterface;
use App\DTOs\LeadDTO;
use App\Models\Lead;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Lead Repository Implementation
 * 
 * Handles lead data persistence operations.
 */
final class LeadRepository implements LeadRepositoryInterface
{
    public function create(LeadDTO $leadDTO): Lead
    {
        return Lead::create([
            'name' => $leadDTO->name,
            'email' => $leadDTO->email,
            'company' => $leadDTO->company,
            'website_url' => $leadDTO->websiteUrl,
            'website_type' => $leadDTO->websiteType->value,
            'platform_id' => $leadDTO->platform,
            'submitted_at' => now(),
        ]);
    }

    public function findByEmail(string $email): ?Lead
    {
        return Lead::where('email', $email)->first();
    }

    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Lead::orderBy('submitted_at', 'desc')->paginate($perPage);
    }

    public function getRecentLeads(int $days = 30): Collection
    {
        return Lead::where('submitted_at', '>=', now()->subDays($days))
            ->orderBy('submitted_at', 'desc')
            ->get();
    }
} 