<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>付款中 - 592Meal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Noto Sans TC', sans-serif;
        }
        .brand-orange {
            color: #FB923C;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-orange-50 to-white min-h-screen flex items-center justify-center">
    <div class="text-center">
        <!-- 592Meal 品牌 -->
        <h1 class="text-4xl font-bold brand-orange mb-4">592Meal</h1>

        <!-- 處理中訊息 -->
        <div class="bg-white rounded-xl shadow-xl p-8 max-w-md w-full">
            <div class="flex justify-center mb-6">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-orange-600"></div>
                </div>
            </div>

            <h2 class="text-2xl font-bold text-gray-900 mb-3">正在處理付款結果...</h2>
            <p class="text-gray-600">請稍候，正在為您處理付款結果</p>

            <div class="mt-6">
                <div class="bg-blue-50 rounded-lg p-4">
                    <p class="text-sm text-blue-800">
                        <strong>付款處理中：</strong>系統正在確認您的付款狀態
                    </p>
                </div>
            </div>
        </div>

        <!-- 即將重定向訊息 -->
        <p class="text-gray-500 text-sm mt-4">即將跳轉到付款結果頁面...</p>
    </div>

    <script>
        // 模擬 2 秒後重定向到結果頁面
        setTimeout(function() {
            window.location.href = '/ecpay/payment-result';
        }, 2000);
    </script>
</body>
</html>