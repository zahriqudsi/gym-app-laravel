<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gym identity (single-gym build)
    |--------------------------------------------------------------------------
    | These are read as defaults. Anything you want editable from the admin UI
    | later should move into a `settings` table / branch settings.
    */
    'name' => env('GYM_NAME', 'My Gym'),
    'currency' => env('GYM_CURRENCY', 'LKR'),
    'currency_symbol' => env('GYM_CURRENCY_SYMBOL', 'Rs'),
    'timezone' => env('GYM_TIMEZONE', 'Asia/Colombo'),
    'locale' => env('GYM_LOCALE', 'en'), // en, si, ta

    /*
    |--------------------------------------------------------------------------
    | Tax
    |--------------------------------------------------------------------------
    */
    'tax' => [
        'enabled' => env('GYM_TAX_ENABLED', false),
        'label' => env('GYM_TAX_LABEL', 'VAT'),
        'rate' => (float) env('GYM_TAX_RATE', 0),   // percent, e.g. 18
        'inclusive' => env('GYM_TAX_INCLUSIVE', true), // prices already include tax
    ],

    /*
    |--------------------------------------------------------------------------
    | Access control at check-in
    |--------------------------------------------------------------------------
    | hard_block  => refuse entry
    | allow_warn  => let them in, show a warning to the front desk
    */
    'access' => [
        'on_expired' => env('GYM_ACCESS_ON_EXPIRED', 'hard_block'),
        'on_dues' => env('GYM_ACCESS_ON_DUES', 'allow_warn'),
        'on_frozen' => env('GYM_ACCESS_ON_FROZEN', 'hard_block'),
        'duplicate_checkin_minutes' => (int) env('GYM_DUP_CHECKIN_MINUTES', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reminders / messaging
    |--------------------------------------------------------------------------
    */
    'reminders' => [
        'quiet_hours' => [
            'start' => env('GYM_QUIET_START', '21:00'),
            'end' => env('GYM_QUIET_END', '08:00'),
        ],
    ],

    'sms' => [
        // 'log' (default, writes to storage/logs), or 'notifylk'
        'driver' => env('SMS_DRIVER', 'log'),
        'sender_id' => env('SMS_SENDER_ID', 'MyGym'),
        // rough cost per SMS segment, used for reporting only
        'cost_per_segment_cents' => (int) env('SMS_COST_PER_SEGMENT_CENTS', 100),
        'notifylk' => [
            'base_url' => env('NOTIFYLK_BASE_URL', 'https://app.notify.lk/api/v1'),
            'user_id' => env('NOTIFYLK_USER_ID'),
            'api_key' => env('NOTIFYLK_API_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Online payments
    |--------------------------------------------------------------------------
    */
    'payments' => [
        'gateway' => env('PAYMENT_GATEWAY', 'payhere'),
        'payhere' => [
            'sandbox' => env('PAYHERE_SANDBOX', true),
            'merchant_id' => env('PAYHERE_MERCHANT_ID'),
            'merchant_secret' => env('PAYHERE_MERCHANT_SECRET'),
            'checkout_url' => env('PAYHERE_CHECKOUT_URL', 'https://sandbox.payhere.lk/pay/checkout'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Numbering
    |--------------------------------------------------------------------------
    */
    'numbering' => [
        'member_prefix' => env('GYM_MEMBER_PREFIX', 'M'),
        'invoice_prefix' => env('GYM_INVOICE_PREFIX', 'INV'),
        'receipt_prefix' => env('GYM_RECEIPT_PREFIX', 'RCP'),
    ],
];
