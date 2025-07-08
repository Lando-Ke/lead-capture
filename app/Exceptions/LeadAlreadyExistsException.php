<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when attempting to create a lead with an email that already exists.
 */
final class LeadAlreadyExistsException extends Exception
{
    /**
     * Create a new exception instance.
     * 
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(string $message = 'Lead already exists', int $code = 409, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 