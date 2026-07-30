<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Test-mode dummy keys — replace with your Razorpay test/live key pair in .env.
    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID', 'rzp_test_DUMMYKEY000000'),
        'key_secret' => env('RAZORPAY_KEY_SECRET', 'dummysecretdummysecret1234'),
        'mode' => env('PAYMENT_GATEWAY_MODE', 'razorpay'),
    ],

    // GST on hotel accommodation: per-night tariff up to the threshold is taxed
    // at the low rate, above it at the high rate. Rates in basis points.
    'gst' => [
        'threshold_minor' => env('GST_TARIFF_THRESHOLD_MINOR', 750000), // ₹7,500/night
        'low_rate_bp' => env('GST_LOW_RATE_BP', 500),   // 5%
        'high_rate_bp' => env('GST_HIGH_RATE_BP', 1800), // 18%
    ],

    // Temporary local/testing OTP delivery. Set this to false as soon as an
    // SMS gateway is connected so verification codes are never exposed.
    'booking_otp' => [
        'show_on_screen' => env('OTP_SHOW_ON_SCREEN', true),
    ],

];
