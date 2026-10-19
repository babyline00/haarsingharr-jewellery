<?php

/**
 * Haarsingharr — Payment Gateways Configuration
 * Each gateway has sandbox credentials documented in the .env.example.
 * Never store live secrets in this file — read them from env() at runtime.
 */

return [

    'default_currency'  => env('PAYMENT_CURRENCY', 'PKR'),
    'default_country'    => env('PAYMENT_COUNTRY', 'PK'),

    'gateways' => [
        'cod' => [
            'enabled'     => (bool) env('COD_ENABLED', true),
            'label'       => 'Cash on Delivery',
            'description' => 'Pay cash to the courier when your order arrives.',
            'max_value'   => (float) env('COD_MAX_VALUE', 200000),
            'fee'         => (float) env('COD_FEE', 0),
            'confirmation_required' => (bool) env('COD_CONFIRMATION_REQUIRED', true),
        ],

        'jazzcash' => [
            'enabled'         => (bool) env('JAZZCASH_ENABLED', false),
            'label'           => 'JazzCash',
            'environment'     => env('JAZZCASH_ENV', 'sandbox'),
            'merchant_id'     => env('JAZZCASH_MERCHANT_ID', ''),
            'password'        => env('JAZZCASH_PASSWORD', ''),
            'integrity_salt'  => env('JAZZCASH_INTEGRITY_SALT', ''),
            'return_url'      => env('JAZZCASH_RETURN_URL', '/payments/return/jazzcash'),
            'api_url'         => env('JAZZCASH_API_URL', 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchanttrans/'),
            'verify_url'      => env('JAZZCASH_VERIFY_URL', 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/querytms/'),
        ],

        'easypaisa' => [
            'enabled'         => (bool) env('EASYPAYSA_ENABLED', false),
            'label'           => 'Easypaisa',
            'environment'     => env('EASYPAYSA_ENV', 'sandbox'),
            'store_id'        => env('EASYPAYSA_STORE_ID', ''),
            'hash'           => env('EASYPAYSA_HASH', ''),
            'account_num'    => env('EASYPAYSA_ACCOUNT_NUM', ''),
            'return_url'      => env('EASYPAYSA_RETURN_URL', '/payments/return/easypaisa'),
            'post_url'        => env('EASYPAYSA_POST_URL', 'https://easypay.easypaisa.com.pk/easypay/Index.jsf'),
            'confirm_url'     => env('EASYPAYSA_CONFIRM_URL', 'https://easypay.easypaisa.com.pk/easypay/Confirm.jsf'),
        ],

        'stripe' => [
            'enabled'         => (bool) env('STRIPE_ENABLED', false),
            'label'           => 'Card (Stripe)',
            'environment'     => env('STRIPE_ENV', 'test'),
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', ''),
            'secret_key'      => env('STRIPE_SECRET_KEY', ''),
            'webhook_secret'  => env('STRIPE_WEBHOOK_SECRET', ''),
        ],
    ],

    'verification' => [
        'verify_amount'   => (bool) env('PAYMENT_VERIFY_AMOUNT', true),
        'verify_currency' => (bool) env('PAYMENT_VERIFY_CURRENCY', true),
        'verify_merchant' => (bool) env('PAYMENT_VERIFY_MERCHANT', true),
        'verify_state'    => (bool) env('PAYMENT_VERIFY_STATE', true),
    ],

    'webhook_timeout' => (int) env('PAYMENT_WEBHOOK_TIMEOUT', 30),
    'redirect_return_seconds' => (int) env('PAYMENT_RETURN_SECONDS', 600),
    'verify_retry_attempts'   => (int) env('PAYMENT_VERIFY_RETRIES', 3),

    'refunds' => [
        'require_approval' => (bool) env('REFUND_REQUIRE_APPROVAL', true),
        'max_partial_pct'   => (float) env('REFUND_MAX_PARTIAL_PCT', 100),
        'roles_can_approve' => ['super-admin', 'store-admin', 'order-manager'],
    ],

    'security' => [
        'never_store_fields' => ['card_number', 'cvv', 'pin', 'secret_key', 'password'],
        'event_retention_days' => (int) env('PAYMENT_EVENT_RETENTION', 365),
    ],
];
