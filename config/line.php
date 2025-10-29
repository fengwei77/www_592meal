<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LINE Login Channel Settings
    |--------------------------------------------------------------------------
    |
    | 這些設定用於 LINE Login 整合
    | 請到 LINE Developers Console 建立 Channel 並填入以下資訊
    | https://developers.line.biz/console/
    |
    */

    'channel_id' => env('LINE_LOGIN_CHANNEL_ID', ''),
    'channel_secret' => env('LINE_LOGIN_CHANNEL_SECRET', ''),
    'callback_url' => env('LINE_LOGIN_CALLBACK_URL', env('APP_URL') . '/auth/line/callback'),

    /*
    |--------------------------------------------------------------------------
    | LINE Login API Endpoints
    |--------------------------------------------------------------------------
    */

    'authorize_url' => 'https://access.line.me/oauth2/v2.1/authorize',
    'token_url' => 'https://api.line.me/oauth2/v2.1/token',
    'profile_url' => 'https://api.line.me/v2/profile',
];
