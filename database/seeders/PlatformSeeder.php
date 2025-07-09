<?php
// database/seeders/PlatformSeeder.php
namespace Database\Seeders;

use App\DTOs\PlatformDTO;
use App\Enums\WebsiteType;
use App\Models\Platform;
use Illuminate\Database\Seeder;

/**
 * Platform Seeder
 * 
 * Seeds the platforms table with platform data for all website types.
 */
class PlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing platforms first
        Platform::truncate();
        
        $platforms = $this->getPlatformData();

        foreach ($platforms as $platformData) {
            $platformDTO = PlatformDTO::fromArray($platformData);
            Platform::create($platformDTO->toArray());
        }
    }

    /**
     * Get platform seed data for all website types
     * 
     * @return array<int, array<string, mixed>>
     */
    private function getPlatformData(): array
    {
        return [
            // E-commerce platforms
            [
                'name' => 'Shopify',
                'slug' => 'shopify',
                'description' => 'All-in-one commerce platform for online stores',
                'logo' => 'logos/shopify.png',
                'website_types' => [WebsiteType::ECOMMERCE->value],
                'sort_order' => 1,
            ],
            [
                'name' => 'WooCommerce',
                'slug' => 'woocommerce',
                'description' => 'Customizable WordPress e-commerce plugin',
                'logo' => 'logos/woocommerce.png',
                'website_types' => [WebsiteType::ECOMMERCE->value],
                'sort_order' => 2,
            ],
            [
                'name' => 'BigCommerce',
                'slug' => 'bigcommerce',
                'description' => 'Scalable e-commerce platform for growing businesses',
                'logo' => 'logos/bigcommerce.png',
                'website_types' => [WebsiteType::ECOMMERCE->value],
                'sort_order' => 3,
            ],
            [
                'name' => 'Magento',
                'slug' => 'magento',
                'description' => 'Flexible e-commerce solution with extensive features',
                'logo' => 'logos/magento.png',
                'website_types' => [WebsiteType::ECOMMERCE->value],
                'sort_order' => 4,
            ],
            
            // Blog platforms
            [
                'name' => 'WordPress',
                'slug' => 'wordpress',
                'description' => 'Popular CMS platform for blogs and websites',
                'logo' => 'logos/wordpress.png',
                'website_types' => [WebsiteType::BLOG->value, WebsiteType::BUSINESS->value],
                'sort_order' => 10,
            ],
            [
                'name' => 'Ghost',
                'slug' => 'ghost',
                'description' => 'Modern publishing platform for content creators',
                'logo' => 'logos/ghost.png',
                'website_types' => [WebsiteType::BLOG->value],
                'sort_order' => 11,
            ],
            [
                'name' => 'Medium',
                'slug' => 'medium',
                'description' => 'Popular blogging platform for writers and readers',
                'logo' => 'logos/medium.png',
                'website_types' => [WebsiteType::BLOG->value],
                'sort_order' => 12,
            ],
            [
                'name' => 'Substack',
                'slug' => 'substack',
                'description' => 'Newsletter and blog publishing platform',
                'logo' => 'logos/substack.png',
                'website_types' => [WebsiteType::BLOG->value],
                'sort_order' => 13,
            ],
            
            // Business platforms
            [
                'name' => 'Wix',
                'slug' => 'wix',
                'description' => 'Drag-and-drop website builder for businesses',
                'logo' => 'logos/wix.png',
                'website_types' => [WebsiteType::BUSINESS->value],
                'sort_order' => 20,
            ],
            [
                'name' => 'Squarespace',
                'slug' => 'squarespace',
                'description' => 'All-in-one website builder for businesses',
                'logo' => 'logos/squarespace.png',
                'website_types' => [WebsiteType::BUSINESS->value],
                'sort_order' => 21,
            ],
            [
                'name' => 'Webflow',
                'slug' => 'webflow',
                'description' => 'Professional website builder with design freedom',
                'logo' => 'logos/webflow.png',
                'website_types' => [WebsiteType::BUSINESS->value, WebsiteType::PORTFOLIO->value],
                'sort_order' => 22,
            ],
            
            // Portfolio platforms
            [
                'name' => 'Behance',
                'slug' => 'behance',
                'description' => 'Creative portfolio showcase platform',
                'logo' => 'logos/behance.png',
                'website_types' => [WebsiteType::PORTFOLIO->value],
                'sort_order' => 30,
            ],
            [
                'name' => 'Dribbble',
                'slug' => 'dribbble',
                'description' => 'Design portfolio and inspiration platform',
                'logo' => 'logos/dribbble.png',
                'website_types' => [WebsiteType::PORTFOLIO->value],
                'sort_order' => 31,
            ],
            [
                'name' => 'Adobe Portfolio',
                'slug' => 'adobe-portfolio',
                'description' => 'Professional portfolio builder by Adobe',
                'logo' => 'logos/adobe-portfolio.png',
                'website_types' => [WebsiteType::PORTFOLIO->value],
                'sort_order' => 32,
            ],
            
            // Generic/Other platforms
            [
                'name' => 'Custom Development',
                'slug' => 'custom-development',
                'description' => 'Tailored solution built from scratch',
                'logo' => 'logos/custom.png',
                'website_types' => [
                    WebsiteType::ECOMMERCE->value,
                    WebsiteType::BLOG->value,
                    WebsiteType::BUSINESS->value,
                    WebsiteType::PORTFOLIO->value,
                    WebsiteType::OTHER->value
                ],
                'sort_order' => 100,
            ],
            [
                'name' => 'Other Platform',
                'slug' => 'other',
                'description' => 'Another platform not listed above',
                'logo' => 'logos/other.png',
                'website_types' => [
                    WebsiteType::ECOMMERCE->value,
                    WebsiteType::BLOG->value,
                    WebsiteType::BUSINESS->value,
                    WebsiteType::PORTFOLIO->value,
                    WebsiteType::OTHER->value
                ],
                'sort_order' => 101,
            ],
        ];
    }
} 