@extends('frontend.layouts.app')

@section('title', '訂單詳情 - ' . $order->order_number)

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- 頁面標題 -->
    <div class="bg-white border-b">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 xl:px-12 py-8" style="max-width: 1400px;">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">
                    <i class="fas fa-file-invoice mr-2"></i>訂單詳情
                </h1>
                <a href="{{ route('frontend.order.index') }}" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-1"></i>返回訂單列表
                </a>
            </div>
        </div>
    </div>

    <div class="mx-auto px-4 sm:px-6 lg:px-8 xl:px-12 py-8" style="max-width: 1400px;">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- 主要內容區 -->
            <div class="lg:col-span-2 space-y-6">
                <!-- 訂單狀態卡片 -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-semibold text-gray-900">訂單狀態</h2>
                        <span class="px-4 py-2 rounded-full text-sm font-medium
                            @if($order->status === 'pending') bg-yellow-100 text-yellow-800
                            @elseif($order->status === 'confirmed') bg-blue-100 text-blue-800
                            @elseif($order->status === 'preparing') bg-indigo-100 text-indigo-800
                            @elseif($order->status === 'ready') bg-green-100 text-green-800
                            @elseif($order->status === 'completed') bg-green-100 text-green-800
                            @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ $order->status_label }}
                        </span>
                    </div>

                    <!-- 狀態進度條 -->
                    <div class="relative pt-1">
                        <div class="flex mb-2 items-center justify-between">
                            <div class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full
                                {{ in_array($order->status, ['pending', 'confirmed', 'preparing', 'ready', 'completed']) ? 'text-blue-600 bg-blue-200' : 'text-gray-600 bg-gray-200' }}">
                                @if($order->status === 'completed')
                                    已完成
                                @elseif($order->status === 'cancelled')
                                    已取消
                                @else
                                    處理中
                                @endif
                            </div>
                        </div>
                        <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-gray-200">
                            <div style="width:
                                @if($order->status === 'pending') 20%
                                @elseif($order->status === 'confirmed') 40%
                                @elseif($order->status === 'preparing') 60%
                                @elseif($order->status === 'ready') 80%
                                @elseif($order->status === 'completed') 100%
                                @else 0%
                                @endif"
                                class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center
                                {{ $order->status === 'cancelled' ? 'bg-red-500' : 'bg-blue-500' }}">
                            </div>
                        </div>
                    </div>

                    <!-- 訂單狀態說明 -->
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-700">
                            @if($order->status === 'pending')
                                <i class="fas fa-clock text-yellow-600 mr-2"></i>您的訂單已送出，等待店家確認中
                            @elseif($order->status === 'confirmed')
                                <i class="fas fa-check-circle text-blue-600 mr-2"></i>店家已確認您的訂單，準備製作中
                            @elseif($order->status === 'preparing')
                                <i class="fas fa-utensils text-indigo-600 mr-2"></i>您的餐點正在製作中
                            @elseif($order->status === 'ready')
                                <i class="fas fa-bell text-green-600 mr-2"></i>您的餐點已準備完成，請前往取餐
                            @elseif($order->status === 'completed')
                                <i class="fas fa-check-double text-green-600 mr-2"></i>訂單已完成，感謝您的訂購
                            @elseif($order->status === 'cancelled')
                                <i class="fas fa-times-circle text-red-600 mr-2"></i>此訂單已取消
                            @endif
                        </p>
                    </div>
                </div>

                <!-- 訂單商品列表 -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-gray-900">訂單商品</h2>
                    </div>

                    <div class="divide-y">
                        @foreach($order->orderItems as $item)
                            <div class="p-6">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start space-x-4 flex-1">
                                        <!-- 商品圖標 -->
                                        <div class="flex-shrink-0 w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-utensils text-gray-400 text-xl"></i>
                                        </div>

                                        <!-- 商品資訊 -->
                                        <div class="flex-1">
                                            <h3 class="text-base font-semibold text-gray-900">
                                                {{ $item->menuItem->name ?? '商品已下架' }}
                                            </h3>
                                            <p class="text-sm text-gray-600 mt-1">
                                                單價 ${{ number_format($item->unit_price, 0) }} × {{ $item->quantity }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- 小計 -->
                                    <div class="text-right ml-4">
                                        <p class="text-lg font-bold text-green-700">
                                            ${{ number_format($item->total_price, 0) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- 總計 -->
                    <div class="px-6 py-4 bg-gray-50 border-t">
                        <div class="flex items-center justify-between">
                            <span class="text-lg font-semibold text-gray-900">訂單總額</span>
                            <span class="text-2xl font-bold text-green-700">
                                ${{ number_format($order->total_amount, 0) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 側邊欄 -->
            <div class="lg:col-span-1 space-y-6">
                <!-- 訂單資訊 -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">訂單資訊</h3>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-gray-600">訂單編號</dt>
                            <dd class="font-medium text-gray-900 mt-1">{{ $order->order_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-600">下單時間</dt>
                            <dd class="font-medium text-gray-900 mt-1">{{ $order->created_at->format('Y/m/d H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-600">店家名稱</dt>
                            <dd class="font-medium text-gray-900 mt-1">{{ $order->store->name }}</dd>
                        </div>
                        @if($order->notes)
                            <div>
                                <dt class="text-gray-600">訂單備註</dt>
                                <dd class="font-medium text-gray-900 mt-1">{{ $order->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <!-- 顧客資訊 -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">顧客資訊</h3>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-gray-600">姓名</dt>
                            <dd class="font-medium text-gray-900 mt-1 flex items-center">
                                @if($order->line_display_name)
                                    <i class="fab fa-line text-green-600 mr-2"></i>
                                @endif
                                {{ $order->customer_name }}
                            </dd>
                        </div>
                        @if($order->customer_phone)
                            <div>
                                <dt class="text-gray-600">聯絡電話</dt>
                                <dd class="font-medium text-gray-900 mt-1">{{ $order->customer_phone }}</dd>
                            </div>
                        @endif
                        @if($order->line_picture_url)
                            <div>
                                <dt class="text-gray-600">LINE 頭像</dt>
                                <dd class="mt-2">
                                    <img src="{{ $order->line_picture_url }}" alt="LINE 頭像"
                                         class="w-12 h-12 rounded-full border-2 border-green-500">
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <!-- 操作按鈕 -->
                <div class="bg-white rounded-lg shadow-sm p-6 space-y-3">
                    @if(in_array($order->status, ['pending', 'confirmed']))
                        <!-- 取消訂單按鈕 -->
                        <button type="button" id="cancelOrderBtn"
                                class="block w-full px-4 py-3 bg-red-600 text-white text-center rounded-lg hover:bg-red-700 transition-colors font-medium">
                            <i class="fas fa-times-circle mr-2"></i>取消訂單
                        </button>
                    @endif

                    <a href="{{ route('frontend.store.detail', $order->store->store_slug_name) }}"
                       class="block w-full px-4 py-3 bg-blue-600 text-white text-center rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        <i class="fas fa-store mr-2"></i>前往店家頁面
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Order cancel script loaded');
    const cancelBtn = document.getElementById('cancelOrderBtn');
    console.log('Cancel button:', cancelBtn);

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Cancel button clicked');

            if (confirm('確定要取消此訂單嗎？取消後無法復原。')) {
                cancelOrder();
            }
        });
    } else {
        console.log('Cancel button not found - order status may not allow cancellation');
    }

    function cancelOrder() {
        console.log('Starting order cancellation...');

        // 禁用按鈕防止重複點擊
        cancelBtn.disabled = true;
        cancelBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>取消中...';

        const url = '{{ route('frontend.order.cancel', $order->order_number) }}';
        const csrfToken = document.querySelector('meta[name="csrf-token"]');

        console.log('Request URL:', url);
        console.log('CSRF Token:', csrfToken ? 'Found' : 'Not found');

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error('HTTP error ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);

            if (data.success) {
                // 顯示成功訊息
                let message = data.message;
                if (data.warning) {
                    message += '\n\n' + data.warning;
                }

                alert(message);

                // 重新載入頁面以顯示更新後的訂單狀態
                console.log('Reloading page...');
                window.location.reload();
            } else {
                // 顯示錯誤訊息
                alert(data.message || '取消訂單失敗，請稍後再試');

                // 恢復按鈕
                cancelBtn.disabled = false;
                cancelBtn.innerHTML = '<i class="fas fa-times-circle mr-2"></i>取消訂單';
            }
        })
        .catch(error => {
            console.error('Error details:', error);
            alert('發生錯誤：' + error.message + '\n請稍後再試');

            // 恢復按鈕
            cancelBtn.disabled = false;
            cancelBtn.innerHTML = '<i class="fas fa-times-circle mr-2"></i>取消訂單';
        });
    }
});
</script>
@endpush

@endsection
