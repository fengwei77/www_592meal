<?php

return [

    'title' => '登入',

    'heading' => '登入帳號',

    'form' => [

        'email' => [
            'label' => '電子郵件地址',
        ],

        'password' => [
            'label' => '密碼',
        ],

        'remember' => [
            'label' => '記住我',
        ],

        'actions' => [

            'authenticate' => [
                'label' => '登入',
            ],

            'request_password_reset' => [
                'label' => '忘記密碼？',
            ],

        ],

    ],

    'multi_factor' => [

        'heading' => '雙重驗證',

        'description' => '請輸入您的驗證碼以完成登入。',

        'form' => [

            'code' => [
                'label' => '驗證碼',
                'placeholder' => '請輸入 6 位數驗證碼',
            ],

            'recovery_code' => [
                'label' => '復原碼',
                'placeholder' => '請輸入復原碼',
            ],

            'actions' => [

                'authenticate' => [
                    'label' => '驗證',
                ],

                'use_recovery_code' => [
                    'label' => '使用復原碼',
                ],

                'use_authentication_code' => [
                    'label' => '使用驗證碼',
                ],

            ],

        ],

        'notifications' => [

            'invalid_code' => [
                'title' => '驗證碼無效',
                'body' => '您輸入的驗證碼不正確，請重試。',
            ],

        ],

    ],

    'messages' => [

        'failed' => '所提供的帳號密碼與資料庫中的記錄不相符。',

    ],

    'notifications' => [

        'throttled' => [
            'title' => '嘗試登入次數過多。請在 :seconds 秒後重試。',
        ],

    ],

];