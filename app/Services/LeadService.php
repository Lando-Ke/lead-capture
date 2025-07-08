<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LeadRepositoryInterface;
use App\Contracts\LeadServiceInterface;
use App\DTOs\LeadDTO;
use App\Exceptions\LeadAlreadyExistsException;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling lead business logic.
 * 
 * Manages lead creation, validation, and retrieval operations
 * with proper logging and error handling.
 */
final class LeadService implements LeadServiceInterface
{
    public function __construct(
        private readonly LeadRepositoryInterface $leadRepository
    ) {}

    /**
     * Create a new lead with business logic validation.
     * 
     * @param LeadDTO $leadDTO The lead data transfer object
     * @return Lead The created lead model
     * @throws LeadAlreadyExistsException If lead with email already exists
     */
    public function createLead(LeadDTO $leadDTO): Lead
    {
        // Check if lead already exists
        $existingLead = $this->leadRepository->findByEmail($leadDTO->email);
        
        if ($existingLead) {
            Log::info('Duplicate lead submission attempt', [
                'email' => $leadDTO->email,
                'existing_lead_id' => $existingLead->id,
                'attempted_at' => now()->toDateTimeString(),
            ]);
            
            throw new LeadAlreadyExistsException($leadDTO->email, $existingLead->id);        }

        // Create the lead
        $lead = $this->leadRepository->create($leadDTO);

        Log::info('New lead created successfully', [
            'lead_id' => $lead->id,
            'email' => $lead->email,
            'website_type' => $lead->website_type->value,
            'platform_id' => $lead->platform_id,
            'created_at' => $lead->created_at ? $lead->created_at->toDateTimeString() : now()->toDateTimeString(),
        ]);

        return $lead;
    }

    /**
     * Retrieve a lead by email address.
     * 
     * @param string $email The email address to search for
     * @return Lead|null The found lead or null if not found
     */
    public function getLeadByEmail(string $email): ?Lead
    {
        return $this->leadRepository->findByEmail($email);
    }

    /**
     * Check if a lead exists with the given email.
     * 
     * @param string $email The email address to check
     * @return bool True if lead exists, false otherwise
     */
    public function leadExists(string $email): bool
    {
        return $this->getLeadByEmail($email) !== null;
    }
} 