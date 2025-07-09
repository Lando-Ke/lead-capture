<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for transforming platform data for API responses.
 *
 * Provides consistent formatting for platform data with proper
 * field transformation and additional metadata.
 */
final class PlatformResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request The request instance
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'logo' => $this->logo,
            'website_types' => $this->website_types,
            'website_types_formatted' => collect($this->website_types)->map(function ($type) {
                $websiteType = \App\Enums\WebsiteType::from($type);

                return [
                    'value' => $websiteType->value,
                    'label' => $websiteType->label(),
                    'description' => $websiteType->description(),
                    'icon' => $websiteType->icon(),
                ];
            })->toArray(),
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
