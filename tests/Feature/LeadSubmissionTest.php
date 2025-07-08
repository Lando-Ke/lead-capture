<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\WebsiteType;
use App\Models\Lead;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeadSubmissionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test platforms
        Platform::factory()->create([
            'name' => 'Shopify',
            'slug' => 'shopify',
            'description' => 'All-in-one commerce platform',
            'website_types' => [WebsiteType::ECOMMERCE->value],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Platform::factory()->create([
            'name' => 'WordPress',
            'slug' => 'wordpress',
            'description' => 'Popular CMS platform',
            'website_types' => [WebsiteType::BLOG->value, WebsiteType::BUSINESS->value],
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }

    /** @test */
    public function it_can_submit_a_valid_lead(): void
    {
        $platform = Platform::first();
        $leadData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => 'Acme Corp',
            'website_url' => 'https://example.com',
            'website_type' => WebsiteType::ECOMMERCE->value,
            'platform_id' => $platform->id,
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'company',
                    'website_url',
                    'website_type' => [
                        'value',
                        'label',
                        'description',
                        'icon',
                    ],
                    'platform' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'website_types',
                    ],
                    'submitted_at',
                    'created_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Lead submitted successfully',
                'data' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'company' => 'Acme Corp',
                    'website_url' => 'https://example.com',
                    'website_type' => [
                        'value' => 'ecommerce',
                        'label' => 'E-commerce',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('leads', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => 'Acme Corp',
            'website_url' => 'https://example.com',
            'website_type' => 'ecommerce',
            'platform_id' => $platform->id,
        ]);
    }

    /** @test */
    public function it_requires_name_field(): void
    {
        $leadData = [
            'email' => 'john@example.com',
            'website_type' => WebsiteType::BUSINESS->value,
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_requires_email_field(): void
    {
        $leadData = [
            'name' => 'John Doe',
            'website_type' => WebsiteType::BUSINESS->value,
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_requires_website_type_field(): void
    {
        $leadData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['website_type']);
    }

    /** @test */
    public function it_validates_email_format(): void
    {
        $leadData = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'website_type' => WebsiteType::BUSINESS->value,
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_validates_email_uniqueness(): void
    {
        $existingLead = Lead::factory()->create([
            'email' => 'john@example.com',
        ]);

        $leadData = [
            'name' => 'Jane Doe',
            'email' => 'john@example.com',
            'website_type' => WebsiteType::BUSINESS->value,
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_validates_website_type_enum(): void
    {
        $leadData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'website_type' => 'invalid-type',
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['website_type']);
    }

    /** @test */
    public function it_requires_platform_id_for_ecommerce_websites(): void
    {
        $leadData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'website_type' => WebsiteType::ECOMMERCE->value,
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['platform_id']);
    }

    /** @test */
    public function it_does_not_require_platform_id_for_non_ecommerce_websites(): void
    {
        $leadData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'website_type' => WebsiteType::BLOG->value,
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_validates_platform_id_exists(): void
    {
        $leadData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'website_type' => WebsiteType::ECOMMERCE->value,
            'platform_id' => 999,
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['platform_id']);
    }

    /** @test */
    public function it_validates_platform_id_is_active(): void
    {
        $inactivePlatform = Platform::factory()->create([
            'is_active' => false,
        ]);

        $leadData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'website_type' => WebsiteType::ECOMMERCE->value,
            'platform_id' => $inactivePlatform->id,
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['platform_id']);
    }

    /** @test */
    public function it_validates_platform_supports_website_type(): void
    {
        $blogPlatform = Platform::where('slug', 'wordpress')->first();

        $leadData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'website_type' => WebsiteType::ECOMMERCE->value,
            'platform_id' => $blogPlatform->id,
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['platform_id']);
    }

    /** @test */
    public function it_validates_name_length(): void
    {
        $leadData = [
            'name' => 'J', // Too short
            'email' => 'john@example.com',
            'website_type' => WebsiteType::BUSINESS->value,
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_validates_name_with_numbers_and_special_chars(): void
    {
        $leadData = [
            'name' => 'John123!@#',
            'email' => 'john@example.com',
            'website_type' => WebsiteType::BUSINESS->value,
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_validates_website_url_format(): void
    {
        $leadData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'website_url' => 'invalid-url',
            'website_type' => WebsiteType::BUSINESS->value,
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['website_url']);
    }

    /** @test */
    public function it_accepts_valid_website_url(): void
    {
        $leadData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'website_url' => 'https://example.com',
            'website_type' => WebsiteType::BUSINESS->value,
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_handles_database_errors_gracefully(): void
    {
        // Simulate database error
        DB::shouldReceive('beginTransaction')->andThrow(new \Exception('Database error'));

        $leadData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'website_type' => WebsiteType::BUSINESS->value,
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'An error occurred while submitting the lead',
                'error_code' => 'SUBMISSION_ERROR',
            ]);
    }

    /** @test */
    public function it_respects_rate_limiting_on_lead_submission(): void
    {
        $leadData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'website_type' => WebsiteType::BUSINESS->value,
        ];

        // Make requests up to the rate limit
        for ($i = 0; $i < 5; $i++) {
            $leadData['email'] = "john{$i}@example.com";
            $response = $this->postJson('/api/v1/leads', $leadData);
            $response->assertStatus(201);
        }

        // The 6th request should be rate limited
        $leadData['email'] = 'john6@example.com';
        $response = $this->postJson('/api/v1/leads', $leadData);
        $response->assertStatus(429);
    }

    /** @test */
    public function it_can_check_if_email_exists(): void
    {
        $lead = Lead::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->getJson('/api/v1/leads/existing@example.com/check');

        $response->assertStatus(200)
            ->assertJson([
                'exists' => true,
                'submitted_at' => $lead->submitted_at->toISOString(),
            ]);
    }

    /** @test */
    public function it_can_check_if_email_does_not_exist(): void
    {
        $response = $this->getJson('/api/v1/leads/nonexistent@example.com/check');

        $response->assertStatus(200)
            ->assertJson([
                'exists' => false,
                'submitted_at' => null,
            ]);
    }

    /** @test */
    public function it_respects_rate_limiting_on_email_check(): void
    {
        // Make requests up to the rate limit
        for ($i = 0; $i < 10; $i++) {
            $response = $this->getJson('/api/v1/leads/test@example.com/check');
            $response->assertStatus(200);
        }

        // The 11th request should be rate limited
        $response = $this->getJson('/api/v1/leads/test@example.com/check');
        $response->assertStatus(429);
    }

    /** @test */
    public function it_returns_proper_json_response_structure(): void
    {
        $platform = Platform::first();
        $leadData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'website_type' => WebsiteType::ECOMMERCE->value,
            'platform_id' => $platform->id,
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'website_type' => [
                        'value',
                        'label',
                        'description',
                        'icon',
                    ],
                    'platform' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'website_types',
                    ],
                    'submitted_at',
                    'created_at',
                ],
            ]);
    }

    /** @test */
    public function it_stores_submitted_at_timestamp(): void
    {
        $platform = Platform::first();
        $leadData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'website_type' => WebsiteType::ECOMMERCE->value,
            'platform_id' => $platform->id,
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201);

        $lead = Lead::where('email', 'john@example.com')->first();
        $this->assertNotNull($lead->submitted_at);
        $this->assertTrue($lead->submitted_at->isToday());
    }

    /** @test */
    public function it_handles_optional_fields_correctly(): void
    {
        $leadData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'website_type' => WebsiteType::BUSINESS->value,
            // No company or website_url
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('leads', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => null,
            'website_url' => null,
            'website_type' => 'business',
            'platform_id' => null,
        ]);
    }
} 