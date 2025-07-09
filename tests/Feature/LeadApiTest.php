<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LeadApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itValidatesEmailOnCheckEndpoint(): void
    {
        $response = $this->getJson('/api/v1/leads/invalid-email/check');

        $response->assertStatus(422)
            ->assertJsonFragment([
                'success' => false,
                'message' => 'Invalid email address.',
            ]);
    }
}
