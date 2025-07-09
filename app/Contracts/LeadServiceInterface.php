<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\LeadDTO;
use App\Models\Lead;

/**
 * Interface for lead service operations.
 *
 * Defines the contract for lead business logic including creation,
 * validation, and retrieval operations.
 */
interface LeadServiceInterface
{
    /**
     * Create a new lead with business logic validation.
     *
     * @param LeadDTO $leadDTO The lead data transfer object
     *
     * @return Lead The created lead model
     *
     * @throws \App\Exceptions\LeadAlreadyExistsException
     */
    public function createLead(LeadDTO $leadDTO): Lead;

    /**
     * Retrieve a lead by email address.
     *
     * @param string $email The email address to search for
     *
     * @return Lead|null The found lead or null if not found
     */
    public function getLeadByEmail(string $email): ?Lead;

    /**
     * Check if a lead exists with the given email.
     *
     * @param string $email The email address to check
     *
     * @return bool True if lead exists, false otherwise
     */
    public function leadExists(string $email): bool;
}
