<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 綠界金流設定
    |--------------------------------------------------------------------------
    |
    */

    'merchant_id' => env('ECPAY_MERCHANT_ID', '3002607'),
    'hash_key' => env('ECPAY_HASH_KEY', 'pwFHCqoQZGmho4w6'),
    'hash_iv' => env('ECPAY_HASH_IV', 'EkRm7iFT261dpevs'),
    'test_mode' => env('ECPAY_TEST_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | 回傳URL設定
    |--------------------------------------------------------------------------
    |
    */
    'return_url' => env('ECPAY_RETURN_URL'),
    'payment_info_url' => env('ECPAY_PAYMENT_INFO_URL'),
    'client_return_url' => env('ECPAY_CLIENT_RETURN_URL'),

    /*
    |--------------------------------------------------------------------------
    | 訂閱系統設定
    |--------------------------------------------------------------------------
    |
    */
    'monthly_price' => env('SUBSCRIPTION_MONTHLY_PRICE', 50),
    'trial_days' => env('SUBSCRIPTION_TRIAL_DAYS', 30),
    'order_expire_hours' => env('SUBSCRIPTION_ORDER_EXPIRE_HOURS', 72),
    'reminder_days_before' => env('SUBSCRIPTION_REMINDER_DAYS_BEFORE', 7),

    /*
    |--------------------------------------------------------------------------
    | 繳費期限設定
    |--------------------------------------------------------------------------
    |
    */
    'cvs_expire_days' => env('ECPAY_CVS_EXPIRE_DAYS', 7),
    'atm_expire_days' => env('ECPAY_ATM_EXPIRE_DAYS', 1),
    'barcode_expire_days' => env('ECPAY_BARCODE_EXPIRE_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | 錯誤重試設定
    |--------------------------------------------------------------------------
    |
    */
    'max_retry_attempts' => env('ECPAY_MAX_RETRY_ATTEMPTS', 3),
    'retry_delay_seconds' => env('ECPAY_RETRY_DELAY_SECONDS', 5),

    /*
    |--------------------------------------------------------------------------
    | 日誌設定
    |--------------------------------------------------------------------------
    |
    */
    'log_enabled' => env('ECPAY_LOG_ENABLED', true),
    'log_level' => env('ECPAY_LOG_LEVEL', 'info'),
];