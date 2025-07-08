<?php
// app/Enums/Platform.php
namespace App\Enums;

enum PlatformType: string
{
    case SHOPIFY = 'shopify';
    case WOOCOMMERCE = 'woocommerce';
    case BIGCOMMERCE = 'bigcommerce';
    case MAGENTO = 'magento';
    case CUSTOM = 'custom';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::SHOPIFY => 'Shopify',
            self::WOOCOMMERCE => 'WooCommerce',
            self::BIGCOMMERCE => 'BigCommerce',
            self::MAGENTO => 'Magento',
            self::CUSTOM => 'Custom Solution',
            self::OTHER => 'Other',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::SHOPIFY => 'All-in-one commerce platform for online stores',
            self::WOOCOMMERCE => 'Customizable WordPress e-commerce plugin',
            self::BIGCOMMERCE => 'Scalable e-commerce platform for growing businesses',
            self::MAGENTO => 'Flexible e-commerce solution with extensive features',
            self::CUSTOM => 'Tailored e-commerce platform built from scratch',
            self::OTHER => 'Another e-commerce platform not listed here',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::SHOPIFY => '🛍️',
            self::WOOCOMMERCE => '🔧',
            self::BIGCOMMERCE => '📈',
            self::MAGENTO => '🏪',
            self::CUSTOM => '⚙️',
            self::OTHER => '🔍',
        };
    }
} 