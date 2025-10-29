@extends('frontend.layouts.app')

@section('title', '購物車 - ' . $store->name)

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- 頁面標題 -->
    <div class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">
                    <i class="fas fa-shopping-cart mr-2"></i>購物車
                </h1>
                <a href="{{ isset($current_store) ? route('frontend.store.detail', $current_store->store_slug_name) : route('frontend.stores.index') }}" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-1"></i>繼續購物
                </a>
            </div>
        </div>
    </div>

    <div class="cart-container mx-auto px-4 sm:px-6 lg:px-8 py-8" style="max-width: 95vw; min-width: 320px;">
        @if(!empty($cartItems))
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- 購物車商品列表 -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b">
                            <h2 class="text-lg font-semibold text-gray-900">商品清單</h2>
                        </div>

                        <div class="divide-y">
                            @foreach($cartItems as $item)
                                <div class="p-6">
                                    <div class="flex items-start space-x-4">
                                        <!-- 商品圖片 -->
                                        <div class="flex-shrink-0 w-20 h-20 bg-gray-100 rounded-lg overflow-hidden">
                                            @if($item['image_url'])
                                                <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}"
                                                     class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <i class="fas fa-utensils text-2xl text-gray-400"></i>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- 商品資訊 -->
                                        <div class="flex-1">
                                            <h3 class="text-lg font-medium text-gray-900 mb-1">{{ $item['name'] }}</h3>
                                            <p class="text-gray-600 mb-3">${{ number_format($item['price'], 0) }}</p>

                                            <!-- 數量控制 -->
                                            <div class="flex items-center space-x-4">
                                                <div class="flex items-center border border-gray-300 rounded-lg">
                                                    <button onclick="updateQuantity({{ $item['id'] }}, {{ $item['quantity'] - 1 }})" data-protect="true"
                                                            class="w-10 h-10 text-gray-600 hover:bg-gray-100 flex items-center justify-center rounded-l-lg transition-colors"
                                                            @if($item['quantity'] <= 1) disabled @endif>
                                                        <i class="fas fa-minus"></i>
                                                    </button>

                                                    <input type="number" id="quantity-{{ $item['id'] }}"
                                                           value="{{ $item['quantity'] }}"
                                                           min="1" max="99"
                                                           class="w-16 text-center border-0 focus:ring-0 font-medium"
                                                           onchange="updateQuantity({{ $item['id'] }}, this.value)">

                                                    <button onclick="updateQuantity({{ $item['id'] }}, {{ $item['quantity'] + 1 }})" data-protect="true"
                                                            class="w-10 h-10 text-gray-600 hover:bg-gray-100 flex items-center justify-center rounded-r-lg transition-colors">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>

                                                <button onclick="removeFromCart({{ $item['id'] }})"
                                                        class="w-10 h-10 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg flex items-center justify-center transition-colors"
                                                        title="移除商品">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- 小計 -->
                                        <div class="text-right">
                                            <p class="text-2xl font-bold text-green-600">
                                                ${{ number_format($item['subtotal'], 0) }}
                                            </p>
                                            <p class="text-sm text-gray-500">小計</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- 購物車操作按鈕 -->
                        <div class="px-6 py-4 bg-gray-50 border-t">
                            <div class="flex justify-between items-center">
                                <button onclick="clearCart()" data-protect="true" class="text-red-600 hover:text-red-800 text-sm">
                                    <i class="fas fa-trash-alt mr-1"></i>清空購物車
                                </button>
                                <a href="{{ isset($current_store) ? route('frontend.store.detail', $current_store->store_slug_name) : route('frontend.stores.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-plus mr-1"></i>繼續購物
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 訂單摘要 -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm sticky top-4">
                        <div class="px-6 py-4 border-b">
                            <h2 class="text-lg font-semibold text-gray-900">訂單摘要</h2>
                        </div>

                        <div class="p-6 space-y-4">
                            <!-- 商品數量 -->
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">商品數量</span>
                                <span class="font-medium">{{ array_sum(array_column($cartItems, 'quantity')) }} 件</span>
                            </div>

                            <!-- 小計 -->
                            <div class="flex justify-between">
                                <span class="text-gray-900">小計</span>
                                <span class="font-medium">${{ number_format($total, 0) }}</span>
                            </div>

                            <!-- 運費 (如適用) -->
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">運費</span>
                                <span class="text-green-600">免費</span>
                            </div>

                            <!-- 總計 -->
                            <div class="pt-4 border-t">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-semibold text-gray-900">總計</span>
                                    <span class="text-2xl font-bold text-green-600">
                                        ${{ number_format($total, 0) }}
                                    </span>
                                </div>
                            </div>

                            <!-- 優惠券 (未來功能) -->
                            <div class="border-t pt-4">
                                <button class="w-full text-sm text-blue-600 hover:text-blue-800 py-2 border border-blue-600 rounded-lg">
                                    <i class="fas fa-ticket-alt mr-1"></i>使用優惠券
                                </button>
                            </div>
                        </div>

                        <!-- 結帳按鈕 -->
                        <div class="px-6 pb-6">
                            @if(isset($store) && $store)
                                <a href="{{ route('frontend.order.create', $store->store_slug_name) }}"
                                   class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors text-center block">
                                    <i class="fas fa-credit-card mr-2"></i>前往結帳
                                </a>
                            @else
                                <button disabled
                                        class="w-full bg-gray-400 text-white py-3 px-4 rounded-lg font-medium cursor-not-allowed text-center block"
                                        title="請先選擇店家">
                                    <i class="fas fa-credit-card mr-2"></i>前往結帳（請先選擇店家）
                                </button>
                            @endif
                        </div>

                        <!-- 安全提示 -->
                        <div class="px-6 pb-6">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-start space-x-2">
                                    <i class="fas fa-shield-alt text-green-600 mt-1"></i>
                                    <div class="text-xs text-gray-600">
                                        <p class="font-medium mb-1">安全購物保障</p>
                                        <p>您的付款資訊將被安全加密保護</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- 購物車為空 -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="text-center py-16">
                    <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">購物車是空的</h3>
                    <p class="text-gray-500 mb-6">還沒有添加任何商品，快去逛逛吧！</p>
                    <a href="{{ isset($current_store) ? route('frontend.store.detail', $current_store->store_slug_name) : route('frontend.stores.index') }}"
                       class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-utensils mr-2"></i>開始購物
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
// 更新商品數量
function updateQuantity(itemId, newQuantity) {
    const quantity = parseInt(newQuantity);

    if (quantity < 1 || quantity > 99 || isNaN(quantity)) {
        return;
    }

    fetch('/cart/update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            item_id: itemId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 重新載入頁面以更新總金額和購物車數量
            location.reload();
        } else {
            showNotification('error', data.message || '更新失敗');
        }
    })
    .catch(error => {
        console.error('更新失敗:', error);
        showNotification('error', '更新失敗，請稍後再試');
    });
}

// 從購物車移除商品
function removeFromCart(itemId) {
    if (!confirm('確定要移除這個商品嗎？')) {
        return;
    }

    fetch('/cart/remove', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            item_id: itemId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 重新載入頁面
            location.reload();
        } else {
            showNotification('error', data.message || '移除失敗');
        }
    })
    .catch(error => {
        console.error('移除失敗:', error);
        showNotification('error', '移除失敗，請稍後再試');
    });
}

// 清空購物車
function clearCart() {
    if (!confirm('確定要清空購物車嗎？')) {
        return;
    }

    fetch('/cart/clear', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showNotification('error', data.message || '清空失敗');
        }
    })
    .catch(error => {
        console.error('清空失敗:', error);
        showNotification('error', '清空失敗，請稍後再試');
    });
}

// 通知功能
function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300 ${
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

// 防止輸入框輸入無效值
document.addEventListener('DOMContentLoaded', function() {
    const quantityInputs = document.querySelectorAll('input[type="number"]');
    quantityInputs.forEach(input => {
        input.addEventListener('input', function() {
            const value = parseInt(this.value);
            if (isNaN(value) || value < 1) {
                this.value = 1;
            } else if (value > 99) {
                this.value = 99;
            }
        });
    });
});
</script>
@endsection