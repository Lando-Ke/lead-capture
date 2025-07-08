<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Platform;
use App\Enums\WebsiteType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'company' => $this->faker->company,
            'website_url' => $this->faker->optional()->url,
            'website_type' => $this->faker->randomElement(WebsiteType::cases()),
            'platform_id' => null,
            'submitted_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the lead is for an e-commerce website.
     */
    public function ecommerce(): static
    {
        return $this->state(function (array $attributes) {
            $platform = Platform::factory()->ecommerce()->create();
            
            return [
                'website_type' => WebsiteType::ECOMMERCE,
                'platform_id' => $platform->id,
            ];
        });
    }

    /**
     * Indicate that the lead is for a blog website.
     */
    public function blog(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'website_type' => WebsiteType::BLOG,
                'platform_id' => null,
            ];
        });
    }

    /**
     * Indicate that the lead is for a business website.
     */
    public function business(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'website_type' => WebsiteType::BUSINESS,
                'platform_id' => null,
            ];
        });
    }

    /**
     * Indicate that the lead is for a portfolio website.
     */
    public function portfolio(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'website_type' => WebsiteType::PORTFOLIO,
                'platform_id' => null,
            ];
        });
    }

    /**
     * Indicate that the lead has a specific platform.
     */
    public function withPlatform(Platform $platform = null): static
    {
        return $this->state(function (array $attributes) use ($platform) {
            $platform = $platform ?: Platform::factory()->create();
            
            return [
                'platform_id' => $platform->id,
                'website_type' => WebsiteType::ECOMMERCE,
            ];
        });
    }

    /**
     * Indicate that the lead was submitted recently.
     */
    public function recent(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'submitted_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
            ];
        });
    }
}
