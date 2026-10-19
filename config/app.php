<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Haarsingharr — Brand Identity
    |--------------------------------------------------------------------------
    |
    | Central place for the brand name, tagline and contact details.
    | Used across emails, invoices and the storefront header.
    |
    */

    'name' => env('APP_NAME', 'Haarsingharr.pk'),
    'tagline' => env('APP_TAGLINE', 'Jewelry For Every You'),
    'brand_short' => env('APP_BRAND_SHORT', 'HAARSINGHARR'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    */

    'env' => env('APP_ENV', 'production'),
    'env_testing' => env('APP_ENV', 'testing'),
    'debug' => (bool) env('APP_DEBUG', false),
    'maintenance' => [
        'driver' => 'file',
        'message' => 'We are polishing the showcase — back shortly.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Locale & Currency
    |--------------------------------------------------------------------------
    */

    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Karachi'),
    'currency' => env('APP_CURRENCY', 'PKR'),
    'currency_symbol' => env('APP_CURRENCY_SYMBOL', 'Rs.'),
    'currency_position' => env('APP_CURRENCY_POSITION', 'before'),

    /*
    |--------------------------------------------------------------------------
    | Application Key & Cipher
    |--------------------------------------------------------------------------
    */

    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'previous_keys' => [
        ...array_filter(
            array_map('trim', explode(',', env('APP_PREVIOUS_KEYS', '')))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | PHP Configuration Override for cPanel hosting
    |--------------------------------------------------------------------------
    */

    'php' => [
        'ini' => [
            'memory_limit'       => env('PHP_MEMORY_LIMIT', '512M'),
            'max_execution_time' => env('PHP_MAX_EXECUTION_TIME', '120'),
            'upload_max_filesize'=> env('PHP_UPLOAD_MAX', '12M'),
            'post_max_size'      => env('PHP_POST_MAX', '12M'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | URL
    |--------------------------------------------------------------------------
    */

    'url' => env('APP_URL', 'http://localhost'),
    'asset_url' => env('ASSET_URL'),
    'force_https' => (bool) env('APP_FORCE_HTTPS', true),

    /*
    |--------------------------------------------------------------------------
    | Frontend / Admin paths
    |--------------------------------------------------------------------------
    */

    'admin_path' => env('ADMIN_PATH', 'admin'),
    'admin_domain' => env('ADMIN_DOMAIN', ''),
    'storefront_domain' => env('STOREFRONT_DOMAIN', ''),

    /*
    |--------------------------------------------------------------------------
    | Maintenance & feature toggles
    |--------------------------------------------------------------------------
    */

    'features' => [
        'guest_checkout'        => (bool) env('FEATURE_GUEST_CHECKOUT', true),
        'wishlist'             => (bool) env('FEATURE_WISHLIST', true),
        'product_questions'    => (bool) env('FEATURE_PRODUCT_QUESTIONS', true),
        'reviews'              => (bool) env('FEATURE_REVIEWS', true),
        'newsletter'           => (bool) env('FEATURE_NEWSLETTER', true),
        'mfa_required'        => (bool) env('FEATURE_MFA_REQUIRED', true),
        'whatsapp_click_to_chat' => (bool) env('FEATURE_WHATSAPP_CLICK_CHAT', true),
    ],

    'providers' => [
        // ServiceProvider overrides
    ],
];
