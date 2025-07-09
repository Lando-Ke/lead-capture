<?php

// database/seeders/PlatformSeeder.php

namespace Database\Seeders;

use App\DTOs\PlatformDTO;
use App\Enums\WebsiteType;
use App\Models\Platform;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Platform Seeder.
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
        // Clear existing platforms first (disable foreign key checks temporarily)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Platform::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $platforms = $this->getPlatformData();

        foreach ($platforms as $platformData) {
            $platformDTO = PlatformDTO::fromArray($platformData);
            Platform::create($platformDTO->toArray());
        }
    }

    /**
     * Get platform seed data for all website types.
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
                'logo' => 'images/platforms/shopify.png',
                'website_types' => [WebsiteType::ECOMMERCE->value],
                'sort_order' => 1,
            ],
            [
                'name' => 'WooCommerce',
                'slug' => 'woocommerce',
                'description' => 'Customizable WordPress e-commerce plugin',
                'logo' => 'images/platforms/woocommerce.png',
                'website_types' => [WebsiteType::ECOMMERCE->value],
                'sort_order' => 2,
            ],
            [
                'name' => 'BigCommerce',
                'slug' => 'bigcommerce',
                'description' => 'Scalable e-commerce platform for growing businesses',
                'logo' => 'images/platforms/bigcommerce.png',
                'website_types' => [WebsiteType::ECOMMERCE->value],
                'sort_order' => 3,
            ],
            [
                'name' => 'Magento',
                'slug' => 'magento',
                'description' => 'Flexible e-commerce solution with extensive features',
                'logo' => 'images/platforms/magento.png',
                'website_types' => [WebsiteType::ECOMMERCE->value],
                'sort_order' => 4,
            ],

            // Blog platforms
            [
                'name' => 'WordPress',
                'slug' => 'wordpress',
                'description' => 'Popular CMS platform for blogs and websites',
                'logo' => 'images/platforms/wordpress.png',
                'website_types' => [WebsiteType::BLOG->value, WebsiteType::BUSINESS->value],
                'sort_order' => 10,
            ],
            [
                'name' => 'Ghost',
                'slug' => 'ghost',
                'description' => 'Modern publishing platform for content creators',
                'logo' => 'images/platforms/ghost.png',
                'website_types' => [WebsiteType::BLOG->value],
                'sort_order' => 11,
            ],
            [
                'name' => 'Medium',
                'slug' => 'medium',
                'description' => 'Popular blogging platform for writers and readers',
                'logo' => 'images/platforms/medium.png',
                'website_types' => [WebsiteType::BLOG->value],
                'sort_order' => 12,
            ],
            [
                'name' => 'Substack',
                'slug' => 'substack',
                'description' => 'Newsletter and blog publishing platform',
                'logo' => 'images/platforms/substack.png',
                'website_types' => [WebsiteType::BLOG->value],
                'sort_order' => 13,
            ],

            // Business platforms
            [
                'name' => 'Wix',
                'slug' => 'wix',
                'description' => 'Drag-and-drop website builder for businesses',
                'logo' => 'images/platforms/wix.png',
                'website_types' => [WebsiteType::BUSINESS->value],
                'sort_order' => 20,
            ],
            [
                'name' => 'Squarespace',
                'slug' => 'squarespace',
                'description' => 'All-in-one website builder for businesses',
                'logo' => 'images/platforms/squarespace.png',
                'website_types' => [WebsiteType::BUSINESS->value],
                'sort_order' => 21,
            ],
            [
                'name' => 'Webflow',
                'slug' => 'webflow',
                'description' => 'Professional website builder with design freedom',
                'logo' => 'images/platforms/webflow.png',
                'website_types' => [WebsiteType::BUSINESS->value, WebsiteType::PORTFOLIO->value],
                'sort_order' => 22,
            ],

            // Portfolio platforms
            [
                'name' => 'Behance',
                'slug' => 'behance',
                'description' => 'Creative portfolio showcase platform',
                'logo' => 'images/platforms/behance.png',
                'website_types' => [WebsiteType::PORTFOLIO->value],
                'sort_order' => 30,
            ],
            [
                'name' => 'Dribbble',
                'slug' => 'dribbble',
                'description' => 'Design portfolio and inspiration platform',
                'logo' => 'images/platforms/dribbble.png',
                'website_types' => [WebsiteType::PORTFOLIO->value],
                'sort_order' => 31,
            ],
            [
                'name' => 'Adobe Portfolio',
                'slug' => 'adobe-portfolio',
                'description' => 'Professional portfolio builder by Adobe',
                'logo' => 'images/platforms/adobe-portfolio.png',
                'website_types' => [WebsiteType::PORTFOLIO->value],
                'sort_order' => 32,
            ],

            // Generic/Other platforms
            [
                'name' => 'Custom Development',
                'slug' => 'custom-development',
                'description' => 'Tailored solution built from scratch',
                'logo' => 'images/platforms/custom-development.png',
                'website_types' => [
                    WebsiteType::ECOMMERCE->value,
                    WebsiteType::BLOG->value,
                    WebsiteType::BUSINESS->value,
                    WebsiteType::PORTFOLIO->value,
                    WebsiteType::OTHER->value,
                ],
                'sort_order' => 100,
            ],
            [
                'name' => 'Other Platform',
                'slug' => 'other',
                'description' => 'Another platform not listed above',
                'logo' => 'images/platforms/other.png',
                'website_types' => [
                    WebsiteType::ECOMMERCE->value,
                    WebsiteType::BLOG->value,
                    WebsiteType::BUSINESS->value,
                    WebsiteType::PORTFOLIO->value,
                    WebsiteType::OTHER->value,
                ],
                'sort_order' => 101,
            ],
        ];
    }
}
