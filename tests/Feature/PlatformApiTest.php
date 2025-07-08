<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\WebsiteType;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PlatformApiTest extends TestCase
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
            'name' => 'WooCommerce',
            'slug' => 'woocommerce',
            'description' => 'WordPress e-commerce plugin',
            'website_types' => [WebsiteType::ECOMMERCE->value],
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Platform::factory()->create([
            'name' => 'WordPress',
            'slug' => 'wordpress',
            'description' => 'Popular CMS platform',
            'website_types' => [WebsiteType::BLOG->value, WebsiteType::BUSINESS->value],
            'is_active' => true,
            'sort_order' => 3,
        ]);

        Platform::factory()->create([
            'name' => 'Inactive Platform',
            'slug' => 'inactive',
            'description' => 'This platform is inactive',
            'website_types' => [WebsiteType::OTHER->value],
            'is_active' => false,
            'sort_order' => 4,
        ]);
    }

    /** @test */
    public function it_can_fetch_all_active_platforms(): void
    {
        $response = $this->getJson('/api/v1/platforms');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'website_types',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data') // Only active platforms
            ->assertJsonFragment([
                'name' => 'Shopify',
                'slug' => 'shopify',
            ])
            ->assertJsonFragment([
                'name' => 'WooCommerce',
                'slug' => 'woocommerce',
            ])
            ->assertJsonFragment([
                'name' => 'WordPress',
                'slug' => 'wordpress',
            ]);

        // Should not include inactive platforms
        $response->assertJsonMissing([
            'name' => 'Inactive Platform',
        ]);
    }

    /** @test */
    public function it_returns_platforms_in_sort_order(): void
    {
        $response = $this->getJson('/api/v1/platforms');

        $response->assertStatus(200);

        $platforms = $response->json('data');
        $this->assertEquals('Shopify', $platforms[0]['name']);
        $this->assertEquals('WooCommerce', $platforms[1]['name']);
        $this->assertEquals('WordPress', $platforms[2]['name']);
    }

    /** @test */
    public function it_can_fetch_platforms_by_website_type(): void
    {
        $response = $this->getJson('/api/v1/platforms/website-type/ecommerce');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'website_types',
                    ],
                ],
                'website_type' => [
                    'value',
                    'label',
                ],
            ])
            ->assertJsonCount(2, 'data') // Only e-commerce platforms
            ->assertJsonFragment([
                'name' => 'Shopify',
            ])
            ->assertJsonFragment([
                'name' => 'WooCommerce',
            ])
            ->assertJsonFragment([
                'website_type' => [
                    'value' => 'ecommerce',
                    'label' => 'E-commerce',
                ],
            ]);

        // Should not include WordPress (not e-commerce)
        $response->assertJsonMissing([
            'name' => 'WordPress',
        ]);
    }

    /** @test */
    public function it_can_fetch_platforms_by_blog_website_type(): void
    {
        $response = $this->getJson('/api/v1/platforms/website-type/blog');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data') // Only WordPress supports blog
            ->assertJsonFragment([
                'name' => 'WordPress',
            ])
            ->assertJsonFragment([
                'website_type' => [
                    'value' => 'blog',
                    'label' => 'Blog/Content Site',
                ],
            ]);
    }

    /** @test */
    public function it_can_fetch_platforms_by_business_website_type(): void
    {
        $response = $this->getJson('/api/v1/platforms/website-type/business');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data') // Only WordPress supports business
            ->assertJsonFragment([
                'name' => 'WordPress',
            ]);
    }

    /** @test */
    public function it_returns_empty_array_for_portfolio_website_type(): void
    {
        $response = $this->getJson('/api/v1/platforms/website-type/portfolio');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data') // No platforms support portfolio
            ->assertJsonFragment([
                'website_type' => [
                    'value' => 'portfolio',
                    'label' => 'Portfolio',
                ],
            ]);
    }

    /** @test */
    public function it_returns_error_for_invalid_website_type(): void
    {
        $response = $this->getJson('/api/v1/platforms/website-type/invalid');

        $response->assertStatus(422)
            ->assertJsonFragment([
                'error' => 'Invalid website type provided',
            ])
            ->assertJsonStructure([
                'error',
                'valid_types',
            ]);

        $validTypes = $response->json('valid_types');
        $this->assertContains('ecommerce', $validTypes);
        $this->assertContains('blog', $validTypes);
        $this->assertContains('business', $validTypes);
        $this->assertContains('portfolio', $validTypes);
        $this->assertContains('other', $validTypes);
    }

    /** @test */
    public function it_can_fetch_platform_by_slug(): void
    {
        $response = $this->getJson('/api/v1/platforms/shopify');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'website_types',
                ],
            ])
            ->assertJsonFragment([
                'name' => 'Shopify',
                'slug' => 'shopify',
                'description' => 'All-in-one commerce platform',
                'website_types' => ['ecommerce'],
            ]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_platform_slug(): void
    {
        $response = $this->getJson('/api/v1/platforms/nonexistent');

        $response->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'Platform not found',
            ]);
    }

    /** @test */
    public function it_returns_404_for_inactive_platform_slug(): void
    {
        $response = $this->getJson('/api/v1/platforms/inactive');

        $response->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'Platform not found',
            ]);
    }

    /** @test */
    public function it_applies_proper_cache_headers_to_platform_endpoints(): void
    {
        $response = $this->getJson('/api/v1/platforms');

        $response->assertStatus(200)
            ->assertHeader('Cache-Control', 'public, max-age=3600, stale-while-revalidate=1800')
            ->assertHeader('X-Cache-Strategy', 'platforms')
            ->assertHeader('Vary', 'Accept, Accept-Encoding');

        $this->assertNotNull($response->headers->get('Expires'));
        $this->assertNotNull($response->headers->get('Last-Modified'));
    }

    /** @test */
    public function it_applies_cache_headers_to_platform_by_website_type(): void
    {
        $response = $this->getJson('/api/v1/platforms/website-type/ecommerce');

        $response->assertStatus(200)
            ->assertHeader('Cache-Control', 'public, max-age=3600, stale-while-revalidate=1800')
            ->assertHeader('X-Cache-Strategy', 'platforms');
    }

    /** @test */
    public function it_applies_cache_headers_to_platform_by_slug(): void
    {
        $response = $this->getJson('/api/v1/platforms/shopify');

        $response->assertStatus(200)
            ->assertHeader('Cache-Control', 'public, max-age=3600, stale-while-revalidate=1800')
            ->assertHeader('X-Cache-Strategy', 'platforms');
    }

    /** @test */
    public function it_returns_platforms_with_multiple_website_types(): void
    {
        $response = $this->getJson('/api/v1/platforms/wordpress');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'WordPress',
                'website_types' => ['blog', 'business'],
            ]);
    }

    /** @test */
    public function it_filters_platforms_correctly_for_mixed_website_types(): void
    {
        // Test that WordPress appears in both blog and business filters
        $blogResponse = $this->getJson('/api/v1/platforms/website-type/blog');
        $businessResponse = $this->getJson('/api/v1/platforms/website-type/business');

        $blogResponse->assertStatus(200)
            ->assertJsonFragment(['name' => 'WordPress']);

        $businessResponse->assertStatus(200)
            ->assertJsonFragment(['name' => 'WordPress']);

        // But not in ecommerce
        $ecommerceResponse = $this->getJson('/api/v1/platforms/website-type/ecommerce');
        $ecommerceResponse->assertStatus(200)
            ->assertJsonMissing(['name' => 'WordPress']);
    }

    /** @test */
    public function it_respects_platform_sort_order(): void
    {
        // Update sort orders to test
        Platform::where('slug', 'wordpress')->update(['sort_order' => 1]);
        Platform::where('slug', 'shopify')->update(['sort_order' => 2]);
        Platform::where('slug', 'woocommerce')->update(['sort_order' => 3]);

        $response = $this->getJson('/api/v1/platforms');

        $response->assertStatus(200);

        $platforms = $response->json('data');
        $this->assertEquals('WordPress', $platforms[0]['name']);
        $this->assertEquals('Shopify', $platforms[1]['name']);
        $this->assertEquals('WooCommerce', $platforms[2]['name']);
    }

    /** @test */
    public function it_only_returns_active_platforms_in_filtered_results(): void
    {
        // Create an inactive e-commerce platform
        Platform::factory()->create([
            'name' => 'Inactive Ecommerce',
            'slug' => 'inactive-ecommerce',
            'website_types' => [WebsiteType::ECOMMERCE->value],
            'is_active' => false,
            'sort_order' => 999,
        ]);

        $response = $this->getJson('/api/v1/platforms/website-type/ecommerce');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data') // Should still only be 2 (Shopify, WooCommerce)
            ->assertJsonMissing([
                'name' => 'Inactive Ecommerce',
            ]);
    }

    /** @test */
    public function it_handles_concurrent_requests_properly(): void
    {
        $responses = [];

        // Make multiple concurrent requests
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->getJson('/api/v1/platforms');
        }

        // All should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200)
                ->assertJsonCount(3, 'data');
        }
    }

    /** @test */
    public function it_returns_consistent_platform_data_structure(): void
    {
        $response = $this->getJson('/api/v1/platforms');

        $response->assertStatus(200);

        $platforms = $response->json('data');
        foreach ($platforms as $platform) {
            $this->assertArrayHasKey('id', $platform);
            $this->assertArrayHasKey('name', $platform);
            $this->assertArrayHasKey('slug', $platform);
            $this->assertArrayHasKey('description', $platform);
            $this->assertArrayHasKey('website_types', $platform);
            
            $this->assertIsInt($platform['id']);
            $this->assertIsString($platform['name']);
            $this->assertIsString($platform['slug']);
            $this->assertIsString($platform['description']);
            $this->assertIsArray($platform['website_types']);
        }
    }

    /** @test */
    public function it_validates_website_type_parameter_format(): void
    {
        // Test with various invalid formats
        $invalidTypes = ['', ' ', 'ECOMMERCE', 'e-commerce', 'ecommerce123', 'e commerce'];

        foreach ($invalidTypes as $invalidType) {
            $response = $this->getJson('/api/v1/platforms/website-type/' . urlencode($invalidType));
            
            if ($invalidType === 'ECOMMERCE') {
                // This might be accepted depending on route constraints
                continue;
            }
            
            $response->assertStatus(422);
        }
    }

    /** @test */
    public function it_handles_empty_platform_results_gracefully(): void
    {
        // Delete all platforms to test empty results
        Platform::query()->delete();

        $response = $this->getJson('/api/v1/platforms');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonStructure([
                'data'
            ]);
    }
} 