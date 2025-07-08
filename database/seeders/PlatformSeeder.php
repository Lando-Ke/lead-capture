<?php
// database/seeders/PlatformSeeder.php
namespace Database\Seeders;

use App\Models\Platform;
use App\Enums\WebsiteType;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $platforms = [
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
            [
                'name' => 'Custom Solution',
                'slug' => 'custom',
                'description' => 'Tailored e-commerce platform built from scratch',
                'logo' => 'logos/custom.png',
                'website_types' => [WebsiteType::ECOMMERCE->value],
                'sort_order' => 5,
            ],
            [
                'name' => 'Other',
                'slug' => 'other',
                'description' => 'Another e-commerce platform not listed here',
                'logo' => 'logos/other.png',
                'website_types' => [WebsiteType::ECOMMERCE->value],
                'sort_order' => 6,
            ],
        ];

        foreach ($platforms as $platform) {
            Platform::create($platform);
        }
    }
} 