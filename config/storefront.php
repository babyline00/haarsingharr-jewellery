<?php

/**
 * Haarsingharr Storefront configuration — global brand settings editable
 * through the admin "Store Settings" module.
 */

return [

    'shipping' => [
        'default_method' => env('SHIPPING_DEFAULT_METHOD', 'standard'),
        'free_shipping_threshold' => (float) env('SHIPPING_FREE_THRESHOLD', 25000),
        'flat_rate'      => (float) env('SHIPPING_FLAT_RATE', 350),
        'express_rate'   => (float) env('SHIPPING_EXPRESS_RATE', 700),
        'cod_eligible'   => (bool) env('SHIPPING_COD_ELIGIBLE', true),
        'dimensions_unit' => env('SHIPPING_DIM_UNIT', 'cm'),
        'weight_unit'    => env('SHIPPING_WEIGHT_UNIT', 'g'),
        'handling_days'  => (int) env('SHIPPING_HANDLING_DAYS', 1),
    ],

    'tax' => [
        'enabled'        => (bool) env('TAX_ENABLED', false),
        'inclusive'      => (bool) env('TAX_INCLUSIVE', true),
        'default_rate'   => (float) env('TAX_DEFAULT_RATE', 0),
        'provincial_rates' => [
            'punjab'      => (float) env('TAX_PUNJAB', 0),
            'sindh'       => (float) env('TAX_SINDH', 0),
            'kpk'         => (float) env('TAX_KPK', 0),
            'balochistan' => (float) env('TAX_BALOCHISTAN', 0),
            'islamabad'   => (float) env('TAX_ISLAMABAD', 0),
            'gilgit'      => (float) env('TAX_GILGIT', 0),
            'ajk'         => (float) env('TAX_AJK', 0),
        ],
    ],

    'inventory' => [
        'reserve_minutes'    => (int) env('INVENTORY_RESERVE_MINUTES', 30),
        'allow_backorders'   => (bool) env('INVENTORY_BACKORDERS', false),
        'low_stock_threshold' => (int) env('INVENTORY_LOW_STOCK', 5),
        'out_of_stock_threshold' => (int) env('INVENTORY_OUT_STOCK', 0),
        'safety_stock'       => (int) env('INVENTORY_SAFETY_STOCK', 1),
    ],

    'cart' => [
        'expire_minutes'   => (int) env('CART_EXPIRE_MINUTES', 43200), // 30 days
        'guest_expire_minutes' => (int) env('CART_GUEST_EXPIRE_MINUTES', 10080), // 7 days
        'max_items_per_cart'  => (int) env('CART_MAX_ITEMS', 50),
        'max_qty_per_item'    => (int) env('CART_MAX_QTY', 20),
    ],

    'reviews' => [
        'enabled'           => (bool) env('REVIEWS_ENABLED', true),
        'require_purchase'  => (bool) env('REVIEWS_REQUIRE_PURCHASE', true),
        'auto_approve'      => (bool) env('REVIEWS_AUTO_APPROVE', false),
        'max_images'        => (int) env('REVIEWS_MAX_IMAGES', 3),
        'min_words'         => (int) env('REVIEWS_MIN_WORDS', 10),
        'rate_limit_per_day' => (int) env('REVIEWS_DAILY_LIMIT', 3),
    ],

    'wishlist' => [
        'enabled'         => (bool) env('WISHLIST_ENABLED', true),
        'max_items'       => (int) env('WISHLIST_MAX', 100),
    ],

    'notifications' => [
        'order_placed'    => (bool) env('NOTIFY_ORDER_PLACED', true),
        'order_paid'      => (bool) env('NOTIFY_ORDER_PAID', true),
        'order_shipped'   => (bool) env('NOTIFY_ORDER_SHIPPED', true),
        'order_delivered' => (bool) env('NOTIFY_ORDER_DELIVERED', true),
        'order_cancelled' => (bool) env('NOTIFY_ORDER_CANCELLED', true),
        'low_stock'       => (bool) env('NOTIFY_LOW_STOCK', true),
        'whatsapp_number' => env('WHATSAPP_NUMBER', ''),
    ],

    'uploads' => [
        'max_image_size'    => (int) env('UPLOAD_MAX_IMAGE_KB', 6144), // 6 MB
        'allowed_mimes'     => explode(',', env('UPLOAD_IMAGE_MIMES', 'jpg,jpeg,png,webp,avif')),
        'max_image_width'   => (int) env('UPLOAD_MAX_WIDTH', 2500),
        'max_image_height'  => (int) env('UPLOAD_MAX_HEIGHT', 2500),
    ],

    'contact' => [
        'email'   => env('CONTACT_EMAIL', 'care@haarsingharr.example'),
        'phone'   => env('CONTACT_PHONE', '+92 300 0000000'),
        'whatsapp'=> env('CONTACT_WHATSAPP', '+92 300 0000000'),
        'address' => env('CONTACT_ADDRESS', 'Main Boulevard, Lahore, Pakistan'),
        'hours'   => env('CONTACT_HOURS', 'Mon–Sat, 10:00–20:00 PKT'),
    ],

    'social' => [
        'instagram' => env('SOCIAL_INSTAGRAM', ''),
        'facebook'  => env('SOCIAL_FACEBOOK', ''),
        'tiktok'    => env('SOCIAL_TIKTOK', ''),
        'pinterest' => env('SOCIAL_PINTEREST', ''),
        'youtube'   => env('SOCIAL_YOUTUBE', ''),
    ],

    'legal' => [
        'company_name'  => env('COMPANY_NAME', 'Haarsingharr Jewellers (Pvt) Ltd'),
        'ntn'           => env('COMPANY_NTN', ''),
        'registration'  => env('COMPANY_REGISTRATION', ''),
    ],
];
