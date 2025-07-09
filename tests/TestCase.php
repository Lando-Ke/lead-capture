<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the application is properly configured for testing
        $this->withoutVite();

        // Start session for proper state management
        $this->startSession();

        // Disable CSRF protection globally for tests
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    /**
     * Disable CSRF protection for the current test.
     *
     * @return $this
     */
    protected function withoutCsrf()
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        return $this;
    }
}
