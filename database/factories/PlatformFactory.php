<?php

namespace Database\Factories;

use App\Models\Platform;
use App\Enums\WebsiteType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Platform>
 */
class PlatformFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $platforms = [
            [
                'name' => 'Shopify',
                'slug' => 'shopify',
                'description' => 'All-in-one commerce platform for online stores',
                'website_types' => [WebsiteType::ECOMMERCE->value],
            ],
            [
                'name' => 'WooCommerce',
                'slug' => 'woocommerce',
                'description' => 'Customizable WordPress e-commerce plugin',
                'website_types' => [WebsiteType::ECOMMERCE->value],
            ],
            [
                'name' => 'WordPress',
                'slug' => 'wordpress',
                'description' => 'Popular content management system',
                'website_types' => [WebsiteType::BLOG->value, WebsiteType::BUSINESS->value],
            ],
            [
                'name' => 'Squarespace',
                'slug' => 'squarespace',
                'description' => 'All-in-one website builder',
                'website_types' => [WebsiteType::BUSINESS->value, WebsiteType::PORTFOLIO->value],
            ],
        ];

        $platform = $this->faker->randomElement($platforms);

        return [
            'name' => $platform['name'],
            'slug' => $platform['slug'],
            'description' => $platform['description'],
            'logo' => null,
            'website_types' => $platform['website_types'],
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }

    /**
     * Indicate that the platform is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the platform supports e-commerce.
     */
    public function ecommerce(): static
    {
        return $this->state(fn (array $attributes) => [
            'website_types' => [WebsiteType::ECOMMERCE->value],
        ]);
    }

    /**
     * Indicate that the platform supports blogging.
     */
    public function blog(): static
    {
        return $this->state(fn (array $attributes) => [
            'website_types' => [WebsiteType::BLOG->value],
        ]);
    }

    /**
     * Indicate that the platform supports business websites.
     */
    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'website_types' => [WebsiteType::BUSINESS->value],
        ]);
    }

    /**
     * Indicate that the platform supports portfolio websites.
     */
    public function portfolio(): static
    {
        return $this->state(fn (array $attributes) => [
            'website_types' => [WebsiteType::PORTFOLIO->value],
        ]);
    }

    /**
     * Indicate that the platform supports multiple website types.
     */
    public function multipleTypes(): static
    {
        return $this->state(fn (array $attributes) => [
            'website_types' => [
                WebsiteType::BUSINESS->value,
                WebsiteType::BLOG->value,
            ],
        ]);
    }
}
