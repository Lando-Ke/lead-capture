<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\LeadDTO;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Lead Repository Contract
 * 
 * Defines the interface for lead data operations.
 */
interface LeadRepositoryInterface
{
    /**
     * Create a new lead from DTO
     */
    public function create(LeadDTO $leadDTO): Lead;

    /**
     * Find lead by email address
     */
    public function findByEmail(string $email): ?Lead;

    /**
     * Get paginated leads
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get recent leads within specified days
     */
    public function getRecentLeads(int $days = 30): Collection;
} 