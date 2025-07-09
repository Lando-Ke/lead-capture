<?php

// app/Models/Platform.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Platform extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'website_types',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'website_types' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForWebsiteType($query, string $websiteType)
    {
        return $query->whereJsonContains('website_types', $websiteType);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
