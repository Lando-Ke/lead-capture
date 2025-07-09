<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the application is properly configured for testing
        $this->withoutVite();
        
        // Disable CSRF protection for testing
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        
        // Fake notifications to prevent actual sending during tests
        \Illuminate\Support\Facades\Notification::fake();
    }
}
