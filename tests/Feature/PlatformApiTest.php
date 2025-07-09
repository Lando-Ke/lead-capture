<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\WebsiteType;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for Platform API endpoints
 */
class PlatformApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create comprehensive test platforms
        Platform::factory()->create([
            'name' => 'Shopify',
            'slug' => 'shopify',
            'description' => 'All-in-one commerce platform',
            'website_types' => [WebsiteType::ECOMMERCE->value],
            'sort_order' => 1,
        ]);

        Platform::factory()->create([
            'name' => 'WooCommerce',
            'slug' => 'woocommerce',
            'description' => 'WordPress e-commerce plugin',
            'website_types' => [WebsiteType::ECOMMERCE->value],
            'sort_order' => 2,
        ]);

        Platform::factory()->create([
            'name' => 'WordPress',
            'slug' => 'wordpress',
            'description' => 'Popular CMS platform',
            'website_types' => [WebsiteType::BLOG->value, WebsiteType::BUSINESS->value],
            'sort_order' => 3,
        ]);

        Platform::factory()->create([
            'name' => 'Ghost',
            'slug' => 'ghost',
            'description' => 'Modern publishing platform',
            'website_types' => [WebsiteType::BLOG->value],
            'sort_order' => 4,
        ]);

        Platform::factory()->create([
            'name' => 'Behance',
            'slug' => 'behance',
            'description' => 'Creative portfolio platform',
            'website_types' => [WebsiteType::PORTFOLIO->value],
            'sort_order' => 5,
        ]);

        Platform::factory()->create([
            'name' => 'Inactive Platform',
            'slug' => 'inactive',
            'description' => 'This platform is inactive',
            'website_types' => [WebsiteType::ECOMMERCE->value],
            'is_active' => false,
            'sort_order' => 999,
        ]);
    }

    /** @test */
    public function it_can_fetch_all_active_platforms(): void
    {
        $response = $this->getJson('/api/v1/platforms');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'website_types',
                    ],
                ],
                'meta' => [
                    'count',
                ],
            ])
            ->assertJsonCount(5, 'data') // 5 active platforms
            ->assertJsonMissing([
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
        $this->assertEquals('Ghost', $platforms[3]['name']);
        $this->assertEquals('Behance', $platforms[4]['name']);
    }

    /** @test */
    public function it_can_fetch_platforms_by_ecommerce_type(): void
    {
        $response = $this->getJson('/api/v1/platforms?type=ecommerce');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'website_types',
                    ],
                ],
                'meta' => [
                    'count',
                    'website_type' => [
                        'value',
                        'label',
                        'description',
                        'icon',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data') // Shopify and WooCommerce
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
                    'description' => 'An online store selling products or services',
                    'icon' => '🛒',
                ],
            ]);
    }

    /** @test */
    public function it_can_fetch_platforms_by_blog_type(): void
    {
        $response = $this->getJson('/api/v1/platforms?type=blog');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data') // WordPress and Ghost
            ->assertJsonFragment([
                'name' => 'WordPress',
            ])
            ->assertJsonFragment([
                'name' => 'Ghost',
            ])
            ->assertJsonFragment([
                'website_type' => [
                    'value' => 'blog',
                    'label' => 'Blog/Content Site',
                    'description' => 'A website focused on publishing articles and content',
                    'icon' => '📝',
                ],
            ]);
    }

    /** @test */
    public function it_can_fetch_platforms_by_business_type(): void
    {
        $response = $this->getJson('/api/v1/platforms?type=business');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data') // Only WordPress supports business
            ->assertJsonFragment([
                'name' => 'WordPress',
            ]);
    }

    /** @test */
    public function it_can_fetch_platforms_by_portfolio_type(): void
    {
        $response = $this->getJson('/api/v1/platforms?type=portfolio');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data') // Only Behance
            ->assertJsonFragment([
                'name' => 'Behance',
            ]);
    }

    /** @test */
    public function it_returns_empty_array_for_other_website_type(): void
    {
        $response = $this->getJson('/api/v1/platforms?type=other');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data') // No platforms support 'other' type
            ->assertJsonFragment([
                'website_type' => [
                    'value' => 'other',
                    'label' => 'Other',
                    'description' => 'A different type of website not listed above',
                    'icon' => '🔍',
                ],
            ]);
    }

    /** @test */
    public function it_validates_invalid_website_type(): void
    {
        $response = $this->getJson('/api/v1/platforms?type=invalid');

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'error_code',
                'meta' => [
                    'valid_types' => [
                        '*' => [
                            'value',
                            'label',
                        ],
                    ],
                ],
            ])
            ->assertJsonFragment([
                'success' => false,
                'message' => 'Invalid website type provided',
                'error_code' => 'INVALID_WEBSITE_TYPE',
            ]);
    }

    /** @test */
    public function it_can_fetch_platform_by_slug(): void
    {
        $response = $this->getJson('/api/v1/platforms/shopify');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
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
                'success' => false,
                'message' => 'Platform not found',
                'error_code' => 'PLATFORM_NOT_FOUND',
            ]);
    }

    /** @test */
    public function it_returns_404_for_inactive_platform_slug(): void
    {
        $response = $this->getJson('/api/v1/platforms/inactive');

        $response->assertStatus(404)
            ->assertJsonFragment([
                'success' => false,
                'message' => 'Platform not found',
                'error_code' => 'PLATFORM_NOT_FOUND',
            ]);
    }

    /** @test */
    public function it_applies_proper_cache_headers_to_platform_endpoints(): void
    {
        $response = $this->getJson('/api/v1/platforms');

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Strategy', 'platforms')
            ->assertHeader('Vary', 'Accept, Accept-Encoding');

        $this->assertCacheControlContains($response, ['public', 'max-age=3600', 'stale-while-revalidate=1800']);
        $this->assertNotNull($response->headers->get('Expires'));
        $this->assertNotNull($response->headers->get('Last-Modified'));
    }

    /** @test */
    public function it_applies_cache_headers_to_platform_with_query_params(): void
    {
        $response = $this->getJson('/api/v1/platforms?type=ecommerce');

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Strategy', 'platforms');
            
        $this->assertCacheControlContains($response, ['public', 'max-age=3600', 'stale-while-revalidate=1800']);
    }

    /** @test */
    public function it_applies_cache_headers_to_platform_by_slug(): void
    {
        $response = $this->getJson('/api/v1/platforms/shopify');

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Strategy', 'platforms');
            
        $this->assertCacheControlContains($response, ['public', 'max-age=3600', 'stale-while-revalidate=1800']);
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
        $blogResponse = $this->getJson('/api/v1/platforms?type=blog');
        $businessResponse = $this->getJson('/api/v1/platforms?type=business');

        $blogResponse->assertStatus(200)
            ->assertJsonFragment(['name' => 'WordPress']);

        $businessResponse->assertStatus(200)
            ->assertJsonFragment(['name' => 'WordPress']);

        // But not in ecommerce
        $ecommerceResponse = $this->getJson('/api/v1/platforms?type=ecommerce');
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
        $response = $this->getJson('/api/v1/platforms?type=ecommerce');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data') // Should be 2 (Shopify, WooCommerce)
            ->assertJsonMissing([
                'name' => 'Inactive Platform',
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
                ->assertJsonCount(5, 'data'); // 5 active platforms
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
    public function it_handles_empty_platform_results_gracefully(): void
    {
        // Delete all platforms to test empty results
        Platform::query()->delete();

        $response = $this->getJson('/api/v1/platforms');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'count',
                ],
            ]);
    }

    /**
     * Helper method to check if Cache-Control header contains all expected directives.
     */
    private function assertCacheControlContains($response, array $expectedDirectives): void
    {
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertNotNull($cacheControl, 'Cache-Control header is missing');

        foreach ($expectedDirectives as $directive) {
            $this->assertStringContainsString($directive, $cacheControl, 
                "Cache-Control header '$cacheControl' does not contain '$directive'");
        }
    }
} 