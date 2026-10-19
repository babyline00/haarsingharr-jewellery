<?php

return [

    'default' => env('FILESYSTEM_DISK', env('APP_ENV') === 'production' ? 'private' : 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => true,
        ],

        // Public disk — only for safe-to-expose assets (optimised product thumbnails,
        // hero banners, favicon). Invoices and customer uploads live in `private`.
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => true,
        ],

        // Private disk — anything that must never be web-accessible.
        'private' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'throw' => true,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => true,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

    // Media library image optimisation presets
    'media_presets' => [
        'thumb'    => ['w' => 200,  'h' => 200, 'q' => 85],
        'card'     => ['w' => 600,  'h' => 600, 'q' => 85],
        'gallery'  => ['w' => 1200, 'h' => 1200, 'q' => 88],
        'hero'     => ['w' => 2000, 'h' => null, 'q' => 88],
    ],
];
