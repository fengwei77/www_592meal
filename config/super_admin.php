<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Super Admin Configuration
    |--------------------------------------------------------------------------
    |
    | 超級管理員配置設定，用於系統初始化
    |
    */

    'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
    'email' => env('SUPER_ADMIN_EMAIL', 'admin@example.com'),
    'password' => env('SUPER_ADMIN_PASSWORD', 'admin123456'),
];