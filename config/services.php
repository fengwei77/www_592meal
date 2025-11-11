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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'line' => [
        'client_id' => env('LINE_LOGIN_CHANNEL_ID'),
        'client_secret' => env('LINE_LOGIN_CHANNEL_SECRET'),
        'redirect' => env('LINE_LOGIN_CALLBACK_URL'),
        'messaging' => [
            'channel_id' => env('LINE_CHANNEL_ID'),
            'channel_secret' => env('LINE_CHANNEL_SECRET'),
            'channel_access_token' => env('LINE_CHANNEL_ACCESS_TOKEN'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'geocoding_api_key' => env('GOOGLE_GEOCODING_API_KEY'),
    ],

    // 綠界金流設定
    'ecpay' => [
        'merchant_id' => env('ECPAY_MERCHANT_ID', '2000132'),
        'hash_key' => env('ECPAY_HASH_KEY', '5294y06Jb3p6vY0'),
        'hash_iv' => env('ECPAY_HASH_IV', 'v77hoKGq4bWTcR0z'),
        'test_mode' => env('ECPAY_TEST_MODE', true),
    ],

];
