<?php

declare(strict_types=1);

namespace App\Exceptions;

class LeadAlreadyExistsException extends \Exception
{
    public function __construct(string $email, int $leadId)
    {
        $message = "A lead with email '{$email}' already exists (ID: {$leadId})";
        parent::__construct($message);
    }
}
