<?php

return [

    'defaults' => [
        'guard'          => 'web',
        'passwords'      => 'customers',
    ],

    'guards' => [
        'web' => [
            'driver'     => 'session',
            'provider'   => 'users',
            'hash'       => false,
        ],

        // Separate guard for back-office staff (Store Admin, Product Manager, etc.)
        'staff' => [
            'driver'     => 'session',
            'provider'   => 'users',
            'hash'       => false,
        ],

        'sanctum' => [
            'driver'     => 'sanctum',
            'provider'   => null,
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\User::class,
        ],

        // Allow staff lookup using a separate provider if required.
    ],

    'password_timeout' => 10800, // 3 hours

    'passwords' => [
        'customers' => [
            'provider' => 'users',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
        'staff' => [
            'provider' => 'users',
            'table'    => 'password_reset_tokens',
            'expire'   => 30,
            'throttle' => 30,
        ],
    ],

    'auth' => [
        'lockout' => [
            'max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
            'decay_minutes' => (int) env('LOGIN_DECAY_MINUTES', 15),
        ],
        'mfa' => [
            'enabled' => (bool) env('MFA_ENABLED', true),
            'required_roles' => ['super-admin', 'store-admin'],
            'window' => 30,
        ],
        'session' => [
            'rotate_on_auth' => (bool) env('SESSION_ROTATE_ON_AUTH', true),
            'single_active' => (bool) env('SESSION_SINGLE_ACTIVE', false),
        ],
    ],
];
