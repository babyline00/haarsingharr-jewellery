<?php

return [

    'default' => env('CACHE_STORE', env('QUEUE_CONNECTION', 'redis') === 'redis' ? 'redis' : 'database'),

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver'         => 'database',
            'table'          => 'cache',
            'connection'     => null,
            'lock_connection' => env('CACHE_LOCK_CONNECTION', env('DB_LOCK_CONNECTION', null)),
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'cache',
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],
    ],

    'prefix' => env('CACHE_PREFIX', 'haarsingharr_'),
    'lock_block_seconds' => env('CACHE_LOCK_BLOCK', 0),
];
