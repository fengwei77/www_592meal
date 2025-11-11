@extends('frontend.layouts.app')

@section('title', '訂單確認 - ' . $store->name)

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- 成功橫幅 -->
    <div class="bg-gradient-to-r from-green-600 to-green-700 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="text-center">
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center">
                        <i class="fas fa-check text-green-600 text-3xl"></i>
                    </div>
                </div>
                <h1 class="text-3xl font-bold mb-2">
                    {{ $order->is_scheduled_order ? '訂單已送出！' : '訂單建立成功！' }}
                </h1>
                <p class="text-green-100">感謝您的訂購，我們已收到您的訂單</p>
                @if($order->is_scheduled_order && $order->scheduled_for)
                    <div class="mt-4 inline-block bg-white/20 rounded-lg px-4 py-2">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        <span class="font-medium">此為 {{ $order->scheduled_for->locale('zh_TW')->isoFormat('M月D日 (ddd)') }} 的預訂單</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- 訂單詳情 -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-gray-900">訂單詳情</h2>
                    </div>

                    <div class="p-6">
                        <!-- 訂單編號和狀態 -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm text-blue-600 font-medium">訂單編號</p>
                                    <p class="text-lg font-bold text-blue-900">{{ $order->order_number }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                        {{ $order->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                        <i class="fas {{ $order->status === 'pending' ? 'fa-clock' : 'fa-check-circle' }} mr-1"></i>
                                        {{ $order->status === 'pending' ? '待確認' : '已確認' }}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-2 text-sm text-blue-600">
                                下單時間：{{ $order->created_at->format('Y-m-d H:i:s') }}
                            </div>
                            @if($order->is_scheduled_order && $order->scheduled_for)
                                <div class="mt-2 pt-2 border-t border-blue-200">
                                    <div class="flex items-center text-sm">
                                        <i class="fas fa-calendar-check text-orange-600 mr-2"></i>
                                        <span class="font-medium text-orange-700">預訂時間：</span>
                                        <span class="ml-1 text-orange-900">{{ $order->scheduled_for->locale('zh_TW')->isoFormat('YYYY年M月D日 (ddd) HH:mm') }}</span>
                                    </div>
                                    <p class="mt-1 text-xs text-orange-600">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        此為預訂單，店家將在預訂日開始處理您的訂單
                                    </p>
                                </div>
                            @endif
                        </div>

                        <!-- 顧客資訊 -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">顧客資訊</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600">姓名</p>
                                        <p class="font-medium">{{ $order->customer_name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">電話</p>
                                        <p class="font-medium">{{ $order->customer_phone }}</p>
                                    </div>
                                </div>
                                @if($order->notes)
                                    <div class="mt-3 pt-3 border-t">
                                        <p class="text-sm text-gray-600 mb-1">備註</p>
                                        <p class="text-gray-900">{{ $order->notes }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- 訂單商品 -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">訂單商品</h3>
                            <div class="space-y-3">
                                @foreach($order->orderItems as $orderItem)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center space-x-3">
                                            <!-- 商品圖片 -->
                                            <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                                <i class="fas fa-utensils text-gray-400 text-sm"></i>
                                            </div>

                                            <div>
                                                <h4 class="font-medium text-gray-900">
                                                    {{ $orderItem->menuItem->name ?? '商品已下架' }}
                                                </h4>
                                                <p class="text-sm text-gray-600">
                                                    ${{ number_format($orderItem->unit_price, 0) }} × {{ $orderItem->quantity }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="text-right">
                                            <p class="font-semibold text-gray-900">
                                                ${{ number_format($orderItem->total_price, 0) }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- 訂單總計 -->
                        <div class="mt-6 pt-6 border-t">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold text-gray-900">訂單總計</span>
                                <span class="text-xl font-bold text-green-600">
                                    ${{ number_format($order->total_amount, 0) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 側邊欄 - 下一步 -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm sticky top-4">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-gray-900">接下來怎麼辦？</h2>
                    </div>

                    <div class="p-6">
                        <!-- 訂單狀態說明 -->
                        <div class="mb-6">
                            <div class="flex items-start space-x-3 mb-4">
                                <div class="w-6 h-6 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center text-sm font-medium">
                                    1
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">店家確認</h4>
                                    <p class="text-sm text-gray-600">店家將會確認您的訂單，預計需要 5-10 分鐘</p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-3 mb-4">
                                <div class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-medium">
                                    2
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">準備餐點</h4>
                                    <p class="text-sm text-gray-600">店家開始準備您的餐點</p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-3">
                                <div class="w-6 h-6 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-sm font-medium">
                                    3
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">取餐完成</h4>
                                    <p class="text-sm text-gray-600">您的餐點準備完成，可以取餐</p>
                                </div>
                            </div>
                        </div>

                        <!-- 聯絡資訊 -->
                        @if($store->phone || $store->address)
                            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                                <h4 class="font-medium text-gray-900 mb-2">店家資訊</h4>
                                @if($store->phone)
                                    <p class="text-sm text-gray-600 mb-1">
                                        <i class="fas fa-phone mr-1"></i>{{ $store->phone }}
                                    </p>
                                @endif
                                @if($store->address)
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-map-marker-alt mr-1"></i>{{ $store->address }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        <!-- 操作按鈕 -->
                        <div class="space-y-3">
                            <button onclick="window.print()" class="w-full bg-gray-200 text-gray-800 py-2 px-4 rounded-lg hover:bg-gray-300 transition-colors">
                                <i class="fas fa-print mr-2"></i>列印訂單
                            </button>

                            <a href="{{ route('frontend.store.detail', $store->store_slug_name) }}" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors text-center block">
                                <i class="fas fa-plus mr-2"></i>繼續購物
                            </a>
                        </div>

                        <!-- 注意事項 -->
                        <div class="mt-6 pt-6 border-t">
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex items-start space-x-2">
                                    <i class="fas fa-info-circle text-yellow-600 mt-0.5"></i>
                                    <div class="text-xs text-yellow-800">
                                        <p class="font-medium mb-1">重要提醒</p>
                                        <ul class="space-y-1">
                                            <li>• 請保留訂單編號以便查詢</li>
                                            <li>• 如需修改或取消訂單，請立即聯繫店家</li>
                                            <li>• 訂單確認後，店家將開始準備餐點</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// 複製訂單編號功能
function copyOrderNumber() {
    const orderNumber = '{{ $order->order_number }}';

    if (navigator.clipboard) {
        navigator.clipboard.writeText(orderNumber).then(() => {
            showNotification('success', '訂單編號已複製到剪貼簿');
        }).catch(err => {
            showNotification('error', '複製失敗，請手動複製');
        });
    } else {
        // 降級方案
        const textArea = document.createElement('textarea');
        textArea.value = orderNumber;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            showNotification('success', '訂單編號已複製到剪貼簿');
        } catch (err) {
            showNotification('error', '複製失敗，請手動複製');
        }
        document.body.removeChild(textArea);
    }
}

// 通知功能
function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300 ${
        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
            ${message}
        </div>
    `;

    document.body.appendChild(notification);

    // 3秒後自動移除
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// 定期檢查訂單狀態（可選）
function checkOrderStatus() {
    fetch(`/api/order-status/{{ $order->order_number }}`)
        .then(response => response.json())
        .then(data => {
            if (data.status !== '{{ $order->status }}') {
                // 狀態有變化，重新載入頁面
                location.reload();
            }
        })
        .catch(error => {
            console.error('檢查訂單狀態失敗:', error);
        });
}

// 每30秒檢查一次訂單狀態
setInterval(checkOrderStatus, 30000);
</script>
@endsection