<?php
// app/Models/Lead.php
namespace App\Models;

use App\Enums\WebsiteType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'company',
        'website_url',
        'website_type',
        'platform_id',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'website_type' => WebsiteType::class,
    ];

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    public function scopeForWebsiteType($query, WebsiteType $websiteType)
    {
        return $query->where('website_type', $websiteType->value);
    }

    public function scopeWithPlatform($query)
    {
        return $query->whereNotNull('platform_id');
    }

    public function isEcommerce(): bool
    {
        return $this->website_type === WebsiteType::ECOMMERCE;
    }
} 