<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DPO Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all configuration options for the DPO payment gateway
    | integration. Set your merchant credentials and preferences here.
    |
    */

    // Merchant credentials
    'company_token' => env('DPO_COMPANY_TOKEN', ''),
    'service_type' => env('DPO_SERVICE_TYPE', '3854'), // Default service type for web payments

    // Environment settings
    'test_mode' => env('DPO_TEST_MODE', true),
    'api_url' => env('DPO_API_URL', 'https://secure.3gdirectpay.com'),
    'test_api_url' => env('DPO_TEST_API_URL', 'https://secure1.sandbox.directpay.online'),

    // Default country and currency settings
    'default_country' => env('DPO_DEFAULT_COUNTRY', 'ZM'), // Zambia as default
    'default_currency' => env('DPO_DEFAULT_CURRENCY', 'ZMW'), // Zambian Kwacha as default

    // Callback URLs
    'back_url' => env('DPO_BACK_URL', '/dpo/callback'),
    'redirect_url' => env('DPO_REDIRECT_URL', '/dpo/success'),
    'notify_url' => env('DPO_NOTIFY_URL', '/api/dpo/notify'),

    // Payment settings
    'payment_timeout' => env('DPO_PAYMENT_TIMEOUT', 3600), // In seconds (1 hour default)
    'allow_recurring' => env('DPO_ALLOW_RECURRING', true),

    // Supported countries and their configurations
    'countries' => [
        'ZM' => [
            'name' => 'Zambia',
            'currency' => 'ZMW',
            'mobile_providers' => ['Airtel Money', 'MTN MoMo', 'Zamtel Kwacha'],
            'supports_recurring' => true,
            'vat_rate' => 16,
        ],
        'KE' => [
            'name' => 'Kenya',
            'currency' => 'KES',
            'mobile_providers' => ['M-Pesa', 'Airtel Money'],
            'supports_recurring' => true,
            'vat_rate' => 16,
        ],
        'TZ' => [
            'name' => 'Tanzania',
            'currency' => 'TZS',
            'mobile_providers' => ['M-Pesa', 'Airtel Money', 'Tigo Pesa', 'HaloPesa'],
            'supports_recurring' => true,
            'vat_rate' => 18,
        ],
        'UG' => [
            'name' => 'Uganda',
            'currency' => 'UGX',
            'mobile_providers' => ['MTN MoMo', 'Airtel Money'],
            'supports_recurring' => true,
            'vat_rate' => 18,
        ],
        'ZA' => [
            'name' => 'South Africa',
            'currency' => 'ZAR',
            'mobile_providers' => [],
            'supports_recurring' => true,
            'vat_rate' => 15,
        ],
        'RW' => [
            'name' => 'Rwanda',
            'currency' => 'RWF',
            'mobile_providers' => ['MTN MoMo', 'Airtel Money'],
            'supports_recurring' => true,
            'vat_rate' => 18,
        ],
        'ET' => [
            'name' => 'Ethiopia',
            'currency' => 'ETB',
            'mobile_providers' => ['CBE Birr', 'telebirr'],
            'supports_recurring' => false,
            'vat_rate' => 15,
        ],
        'NG' => [
            'name' => 'Nigeria',
            'currency' => 'NGN',
            'mobile_providers' => [],
            'supports_recurring' => true,
            'vat_rate' => 7.5,
        ],
        'GH' => [
            'name' => 'Ghana',
            'currency' => 'GHS',
            'mobile_providers' => ['MTN MoMo', 'Vodafone Cash', 'AirtelTigo Money'],
            'supports_recurring' => true,
            'vat_rate' => 12.5,
        ],
        'BW' => [
            'name' => 'Botswana',
            'currency' => 'BWP',
            'mobile_providers' => ['Orange Money', 'MyZaka'],
            'supports_recurring' => true,
            'vat_rate' => 12,
        ],
        'NA' => [
            'name' => 'Namibia',
            'currency' => 'NAD',
            'mobile_providers' => [],
            'supports_recurring' => true,
            'vat_rate' => 15,
        ],
        'MU' => [
            'name' => 'Mauritius',
            'currency' => 'MUR',
            'mobile_providers' => ['MCB Juice', 'MyT Money'],
            'supports_recurring' => true,
            'vat_rate' => 15,
        ],
        'MW' => [
            'name' => 'Malawi',
            'currency' => 'MWK',
            'mobile_providers' => ['Airtel Money', 'TNM Mpamba'],
            'supports_recurring' => false,
            'vat_rate' => 16.5,
        ],
        'ZW' => [
            'name' => 'Zimbabwe',
            'currency' => 'USD', // USD is commonly used
            'mobile_providers' => ['EcoCash', 'OneMoney'],
            'supports_recurring' => true,
            'vat_rate' => 15,
        ],
        'CI' => [
            'name' => 'Côte d\'Ivoire',
            'currency' => 'XOF',
            'mobile_providers' => ['Orange Money', 'MTN MoMo', 'Moov Money'],
            'supports_recurring' => false,
            'vat_rate' => 18,
        ],
    ],

    // Currency configurations
    'currencies' => [
        'ZMW' => ['symbol' => 'K', 'decimals' => 2],
        'KES' => ['symbol' => 'KSh', 'decimals' => 2],
        'TZS' => ['symbol' => 'TSh', 'decimals' => 0],
        'UGX' => ['symbol' => 'USh', 'decimals' => 0],
        'ZAR' => ['symbol' => 'R', 'decimals' => 2],
        'RWF' => ['symbol' => 'FRw', 'decimals' => 0],
        'ETB' => ['symbol' => 'Br', 'decimals' => 2],
        'NGN' => ['symbol' => '₦', 'decimals' => 2],
        'GHS' => ['symbol' => '₵', 'decimals' => 2],
        'BWP' => ['symbol' => 'P', 'decimals' => 2],
        'NAD' => ['symbol' => 'N$', 'decimals' => 2],
        'MUR' => ['symbol' => '₨', 'decimals' => 2],
        'MWK' => ['symbol' => 'MK', 'decimals' => 2],
        'USD' => ['symbol' => '$', 'decimals' => 2],
        'XOF' => ['symbol' => 'CFA', 'decimals' => 0],
    ],

    // Recurring payment settings
    'recurring' => [
        'enabled' => true,
        'immediate_charge' => true,
        'retry_failed_payments' => true,
        'max_retry_attempts' => 3,
        'retry_delay_hours' => 24,
    ],

    // Logging
    'logging' => [
        'enabled' => env('DPO_LOGGING_ENABLED', true),
        'channel' => env('DPO_LOG_CHANNEL', 'dpo'),
    ],

    // Cache settings
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'prefix' => 'dpo_',
    ],
];
