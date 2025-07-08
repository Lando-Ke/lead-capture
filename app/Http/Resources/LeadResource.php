<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for transforming lead data for API responses.
 * 
 * Provides consistent formatting for lead data with proper
 * field transformation and additional metadata.
 */
final class LeadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request The request instance
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'company' => $this->company,
            'website_url' => $this->website_url,
            'website_type' => [
                'value' => $this->website_type->value,
                'label' => $this->website_type->label(),
                'description' => $this->website_type->description(),
                'icon' => $this->website_type->icon(),
            ],
            'platform' => $this->when($this->platform, function () {
                return [
                    'id' => $this->platform->id,
                    'name' => $this->platform->name,
                    'slug' => $this->platform->slug,
                    'description' => $this->platform->description,
                    'website_types' => $this->platform->website_types,
                ];
            }),
            'submitted_at' => $this->submitted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
} 