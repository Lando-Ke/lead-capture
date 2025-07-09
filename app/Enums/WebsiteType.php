<?php

// app/Enums/WebsiteType.php

namespace App\Enums;

enum WebsiteType: string
{
    case ECOMMERCE = 'ecommerce';
    case BLOG = 'blog';
    case BUSINESS = 'business';
    case PORTFOLIO = 'portfolio';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ECOMMERCE => 'E-commerce',
            self::BLOG => 'Blog/Content Site',
            self::BUSINESS => 'Corporate/Business Site',
            self::PORTFOLIO => 'Portfolio',
            self::OTHER => 'Other',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ECOMMERCE => 'An online store selling products or services',
            self::BLOG => 'A website focused on publishing articles and content',
            self::BUSINESS => 'A professional website representing your business',
            self::PORTFOLIO => 'A showcase of work, projects, or services',
            self::OTHER => 'A different type of website not listed above',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ECOMMERCE => '🛒',
            self::BLOG => '📝',
            self::BUSINESS => '🏢',
            self::PORTFOLIO => '🎨',
            self::OTHER => '🔍',
        };
    }
}
