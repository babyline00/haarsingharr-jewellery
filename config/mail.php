<?php

return [

    'default' => env('MAIL_MAILER', env('APP_ENV') === 'production' ? 'smtp' : 'log'),

    'mailers' => [

        'smtp' => [
            'transport'  => 'smtp',
            'url'         => env('MAIL_URL'),
            'host'        => env('MAIL_HOST', '127.0.0.1'),
            'port'        => env('MAIL_PORT', 587),
            'encryption'  => env('MAIL_ENCRYPTION', 'tls'),
            'username'    => env('MAIL_USERNAME'),
            'password'    => env('MAIL_PASSWORD'),
            'timeout'     => env('MAIL_TIMEOUT', 60),
            'local_domain' => env('MAIL_EHLO_DOMAIN', env('APP_URL')),
            'verify_peer' => (bool) env('MAIL_VERIFY_PEER', true),
            'verify_peer_name' => (bool) env('MAIL_VERIFY_PEER_NAME', true),
            'stream_context' => null,
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL', 'stack'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'orders@haarsingharr.example'),
        'name'    => env('MAIL_FROM_NAME', 'Haarsingharr Jewellery'),
    ],

    'reply_to' => [
        'address' => env('MAIL_REPLY_TO_ADDRESS', 'care@haarsingharr.example'),
        'name'    => env('MAIL_REPLY_TO_NAME', 'Haarsingharr Customer Care'),
    ],

    'markdown' => [
        'theme' => 'default',
        'paths' => [
            resource_path('views/emails'),
        ],
    ],
];
