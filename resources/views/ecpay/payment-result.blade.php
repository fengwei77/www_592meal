<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>付款結果 - 592Meal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Noto Sans TC', sans-serif;
        }
        .brand-orange {
            color: #FB923C;
        }
        .brand-orange-bg {
            background-color: #FB923C;
        }
        .close-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-orange-50 to-white min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- 關閉按鈕 -->
        <button onclick="closeWindow()" class="close-btn bg-gray-600 hover:bg-gray-700 text-white p-2 rounded-full shadow-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <div class="max-w-2xl mx-auto fade-in">
            <!-- 品牌標題 -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold brand-orange mb-2">592Meal</h1>
                <p class="text-gray-600">訂餐服務系統</p>
            </div>

            <div class="bg-white rounded-xl shadow-xl p-8">
              <div class="text-center">
            @if($success)
                <div class="flex justify-center mb-6">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-3">付款成功</h2>
                <p class="text-gray-600 mb-8 text-lg">{{ $message ?: '您的付款已成功完成，感謝您的訂購！' }}</p>
            @else
                <div class="flex justify-center mb-6">
                    <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-3">付款失敗</h2>
                <p class="text-gray-600 mb-8 text-lg">{{ $message ?: '很抱歉，您的付款未能完成，請重新嘗試或聯繫客服。' }}</p>
            @endif
        </div>

        @if($orderNumber)
            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">訂單資訊</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2">
                        <span class="text-gray-600 font-medium">訂單編號：</span>
                        <span class="font-mono text-gray-900">{{ $orderNumber }}</span>
                    </div>

                    @if($order)
                        @if($order instanceof \App\Models\Order)
                            <div class="flex justify-between items-center py-2">
                                <span class="text-gray-600 font-medium">訂單金額：</span>
                                <span class="font-bold text-lg brand-orange">NT$ {{ number_format($order->total_amount) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-gray-600 font-medium">付款狀態：</span>
                                <span class="font-medium px-3 py-1 rounded-full text-sm
                                    @if($order->payment_status === 'paid')
                                        bg-green-100 text-green-800
                                    @else
                                        bg-red-100 text-red-800
                                    @endif
                                ">
                                    @if($order->payment_status === 'paid')
                                        已付款
                                    @else
                                        {{ $order->payment_status }}
                                    @endif
                                </span>
                            </div>
                            @if($order->payment_date)
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-gray-600 font-medium">付款時間：</span>
                                    <span class="text-gray-900">{{ $order->payment_date->format('Y年m月d日 H:i') }}</span>
                                </div>
                            @endif
                        @else
                            <div class="flex justify-between items-center py-2">
                                <span class="text-gray-600 font-medium">訂閱金額：</span>
                                <span class="font-bold text-lg brand-orange">NT$ {{ number_format($order->amount) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-gray-600 font-medium">訂閱狀態：</span>
                                <span class="font-medium px-3 py-1 rounded-full text-sm
                                    @if($order->status === 'paid')
                                        bg-green-100 text-green-800
                                    @else
                                        bg-red-100 text-red-800
                                    @endif
                                ">
                                    @if($order->status === 'paid')
                                        已付款
                                    @else
                                        {{ $order->status }}
                                    @endif
                                </span>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @endif

        <!-- 交易時間 -->
        <div class="text-center text-gray-500 text-sm mb-6">
            交易時間：{{ now()->format('Y年m月d日 H:i:s') }}
        </div>

        @if($success && $order)
            @if($order instanceof \App\Models\Order)
                @if($order->items && $order->items->count() > 0)
                    <div class="bg-white shadow rounded-lg p-6 mt-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">訂單明細</h3>
                        <div class="space-y-2">
                            @foreach($order->items as $item)
                                <div class="flex justify-between text-sm">
                                    <span>{{ $item->name }} x {{ $item->quantity }}</span>
                                    <span>NT$ {{ number_format($item->price * $item->quantity) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        @endif

        <!-- 操作按鈕 -->
        <div class="text-center">
            <button onclick="closeWindow()" class="brand-orange-bg hover:opacity-90 text-white font-medium py-3 px-6 rounded-lg transition-opacity">
                關閉視窗
            </button>
            <a href="{{ url('/') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-6 rounded-lg text-center transition-colors inline-block ml-3">
                回到首頁
            </a>
        </div>

        <!-- 客服資訊 -->
        <div class="mt-8 text-center">
            <p class="text-gray-500 text-sm">
                如有任何問題，請聯繫我們的客服團隊
            </p>
            <p class="text-gray-400 text-xs mt-2">
                © 2024 592Meal. 版權所有。
            </p>
        </div>
    </div>
</div>

<script>
// 關閉視窗函數
function closeWindow() {
    if (window.opener) {
        // 如果是彈出視窗，關閉自己
        window.close();
    } else {
        // 如果不是彈出視窗，導向到首頁
        window.location.href = '/';
    }
}

// 自動關閉功能（可選）
setTimeout(function() {
    // 如果用戶沒有操作，5秒後自動關閉
    if (window.opener) {
        window.close();
    }
}, 5000);

// 防止意外關閉
window.addEventListener('beforeunload', function(e) {
    // 這裡可以添加確認訊息，如果需要的話
});

// 頁面載入完成後的動畫
document.addEventListener('DOMContentLoaded', function() {
    // 添加淡入效果
    document.body.classList.add('fade-in');
});
</script>

</body>
</html>