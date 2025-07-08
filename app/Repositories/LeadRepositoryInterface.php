<?php
// app/Repositories/LeadRepositoryInterface.php
namespace App\Repositories;

use App\DTOs\LeadDTO;
use App\Models\Lead;
use Illuminate\Pagination\LengthAwarePaginator;

interface LeadRepositoryInterface
{
    public function create(LeadDTO $leadDTO): Lead;
    public function findByEmail(string $email): ?Lead;
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator;
    public function getRecentLeads(int $days = 30): \Illuminate\Database\Eloquent\Collection;
} 