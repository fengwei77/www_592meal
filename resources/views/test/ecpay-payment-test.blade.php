<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ECPay 付款測試 - 592Meal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Noto Sans TC', sans-serif;
        }
        .brand-orange {
            color: #FB923C;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold brand-orange mb-2">592Meal</h1>
            <p class="text-gray-600">ECPay 付款結果測試頁面</p>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">測試付款結果頁面</h2>
            <p class="text-gray-600 mb-6">點擊下面的按鈕來測試不同情境的付款結果頁面顯示效果：</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- 測試成功頁面 -->
                <div class="bg-green-50 rounded-lg p-4">
                    <h3 class="font-semibold text-green-800 mb-2">✅ 測試成功情境</h3>
                    <p class="text-green-700 text-sm mb-3">模擬付款成功，顯示成功頁面</p>
                    <a href="/ecpay/payment-result?order_number=TEST_ORDER_123456&success=1&message=付款測試成功！"
                       target="_blank"
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded inline-block transition-colors">
                        測試成功頁面
                    </a>
                </div>

                <!-- 測試失敗頁面 -->
                <div class="bg-red-50 rounded-lg p-4">
                    <h3 class="font-semibold text-red-800 mb-2">❌ 測試失敗情境</h3>
                    <p class="text-red-700 text-sm mb-3">模擬付款失敗，顯示失敗頁面</p>
                    <a href="/ecpay/payment-result?order_number=TEST_ORDER_FAILED_789&success=0&message=付款測試失敗，請重新嘗試"
                       target="_blank"
                       class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded inline-block transition-colors">
                        測試失敗頁面
                    </a>
                </div>

                <!-- 測試無資料頁面 -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-800 mb-2">⚠️ 測試無資料情境</h3>
                    <p class="text-gray-700 text-sm mb-3">直接訪問結果頁面，無任何資料</p>
                    <a href="/ecpay/payment-result" target="_blank"
                       class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded inline-block transition-colors">
                        測試無資料頁面
                    </a>
                </div>

                <!-- 模擬真實付款流程 -->
                <div class="bg-blue-50 rounded-lg p-4">
                    <h3 class="font-semibold text-blue-800 mb-2">🔄 模擬完整付款流程</h3>
                    <p class="text-blue-700 text-sm mb-3">透過 JavaScript 設定 session 並重定向</p>
                    <button onclick="simulatePaymentFlow()"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition-colors mb-2">
                        模擬付款流程
                    </button>
                    <button onclick="clearSession()"
                            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded transition-colors ml-2">
                        清除測試 Session
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-yellow-50 rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-yellow-900 mb-4">📋 說明</h2>
            <ul class="space-y-2 text-yellow-800">
                <li><strong>正常流程：</strong>用戶完成付款後，ECPay 會自動重定向到結果頁面</li>
                <li><strong>測試方式：</strong>以上按鈕會在新視窗中開啟對應的結果頁面</li>
                <li><strong>登出問題：</strong>已修復，現在付款完成後用戶不會被登出</li>
                <li><strong>Session 處理：</strong>系統會自動清除付款結果 session</li>
                <li><strong>簡化按鈕：</strong>移除重新付款功能，只保留關閉視窗和回到首頁</li>
            </ul>
        </div>
    </div>

    <script>
        function simulatePaymentFlow() {
            // 模擬設定 session 資料
            const formData = new FormData();
            formData.append('success', '1');
            formData.append('order_number', 'SIMULATED_' + Date.now());
            formData.append('message', '模擬付款流程測試成功！');

            fetch('/test/set-payment-session', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: formData
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 重定向到結果頁面
                    window.open('/ecpay/payment-result', '_blank');
                } else {
                    alert('設定 session 失敗');
                }
            }).catch(error => {
                console.error('設定 session 失敗:', error);
                alert('設定 session 失敗，請直接訪問結果頁面');
            });
        }

        function clearSession() {
            fetch('/test/clear-payment-session', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('測試 session 已清除');
                }
            }).catch(error => {
                console.error('清除 session 失敗:', error);
            });
        }
    </script>
</body>
</html>