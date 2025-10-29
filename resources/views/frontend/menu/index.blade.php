@extends('frontend.layouts.app')

@section('title', $store->name . ' - 菜單')

@section('content')
<div class="bg-white">
    <!-- 店家資訊橫幅 -->
    @if($store->store_logo || $store->description)
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row items-center">
                @if($store->store_logo)
                    <div class="mb-4 md:mb-0 md:mr-6">
                        <img src="{{ $store->logo_url }}" alt="{{ $store->name }}"
                             class="w-24 h-24 rounded-full border-4 border-white shadow-lg object-cover">
                    </div>
                @endif
                <div>
                    <h1 class="text-3xl font-bold mb-2">{{ $store->name }}</h1>
                    @if($store->description)
                        <p class="text-blue-100 max-w-2xl">{{ $store->description }}</p>
                    @endif
                    @if($store->isCurrentlyOpen())
                        <div class="mt-2 inline-flex items-center bg-green-500 px-3 py-1 rounded-full text-sm">
                            <i class="fas fa-clock mr-1"></i>
                            營業中
                        </div>
                    @else
                        <div class="mt-2 inline-flex items-center bg-red-500 px-3 py-1 rounded-full text-sm">
                            <i class="fas fa-clock mr-1"></i>
                            休息中
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- 主要內容區域 -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- 側邊欄 - 分類導航 -->
            <div class="lg:col-span-1">
                <div class="bg-gray-50 rounded-lg p-6 sticky top-4">
                    <h2 class="text-lg font-semibold mb-4 text-gray-900">菜單分類</h2>

                    <!-- 全部菜單 -->
                    <a href="{{ route('frontend.store.detail', $store->store_slug_name) }}"
                       class="block px-4 py-2 rounded-lg mb-2 text-sm font-medium
                              bg-blue-600 text-white">
                        <i class="fas fa-th-large mr-2"></i>全部菜單
                        <span class="float-right">{{ $allItems->count() }}</span>
                    </a>

                    <!-- 分類列表 -->
                    @foreach($categories as $category)
                        @if($category->menuItems->count() > 0)
                            <a href="{{ route('frontend.store.detail', $store->store_slug_name) }}#category-{{ $category->id }}"
                               class="block px-4 py-2 rounded-lg mb-2 text-sm font-medium
                                      text-gray-700 hover:bg-gray-200">
                                @if($category->icon)
                                    <i class="{{ $category->icon }} mr-2"></i>
                                @else
                                    <i class="fas fa-utensils mr-2"></i>
                                @endif
                                {{ $category->name }}
                                <span class="float-right">{{ $category->menuItems->count() }}</span>
                            </a>
                        @endif
                    @endforeach

                    <!-- 營業時間 -->
                    @if($store->business_hours)
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">營業時間</h3>
                            <div class="text-xs text-gray-600">
                                @if($todayHours = $store->getTodayBusinessHours())
                                    @if($todayHours['is_open'])
                                        <p class="text-green-600 font-medium">
                                            今天 {{ $todayHours['open_time'] ?? '休息中' }} - {{ $todayHours['close_time'] ?? '休息中' }}
                                        </p>
                                    @else
                                        <p class="text-red-600">今天休息</p>
                                    @endif
                                @else
                                    <p class="text-gray-500">未設定營業時間</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 主要內容 - 菜單項目 -->
            <div class="lg:col-span-3">
                <!-- 搜尋欄 -->
                <div class="mb-6">
                    <form action="{{ route('frontend.store.detail', $store->store_slug_name) }}" method="GET" class="relative">
                        <input type="text" name="q" placeholder="搜尋菜單項目..."
                               value="{{ request('q') }}"
                               class="w-full px-4 py-3 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <button type="submit" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>

                <!-- 菜單項目列表 -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($allItems as $item)
                        <div class="menu-item-card">
                            <!-- 商品圖片 -->
                            @if(false)
                                <div class="relative h-48 bg-gray-100">
                                    <img src="{{ $item->getImageUrl() }}"
                                         alt="{{ $item->name }}"
                                         class="w-full h-full object-cover">

                                    <!-- 可用狀態標籤 -->
                                    @if($item->is_sold_out)
                                        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                            <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm">
                                                暫時供應完畢
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="relative h-48 bg-gray-100 flex items-center justify-center">
                                    <i class="fas fa-utensils text-4xl text-gray-400"></i>
                                    @if($item->is_sold_out)
                                        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                            <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm">
                                                暫時供應完畢
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <!-- 商品資訊 -->
                            <div class="p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $item->name }}</h3>
                                    <span class="price-tag">${{ number_format($item->price, 0) }}</span>
                                </div>

                                @if($item->description)
                                    <p class="text-gray-600 text-sm mb-3 line-clamp-2">{{ $item->description }}</p>
                                @endif

                                <!-- 額外資訊 -->
                                @if($item->preparation_time || $item->ingredients)
                                    <div class="flex flex-wrap gap-2 mb-3">
                                        @if($item->preparation_time)
                                            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                                <i class="fas fa-clock mr-1"></i>{{ $item->preparation_time }} 分鐘
                                            </span>
                                        @endif
                                        @if($item->spicy_level)
                                            <span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">
                                                <i class="fas fa-fire mr-1"></i>辣度 {{ $item->spicy_level }}
                                            </span>
                                        @endif
                                    </div>
                                @endif

                                <!-- 加入購物車按鈕 -->
                                <button onclick="addToCart({{ $item->id }}, '{{ $item->name }}', {{ $item->price }}, '{{ $store->store_slug_name }}')"
                                        data-protect="true"
                                        @if($item->is_sold_out) disabled @endif
                                        class="w-full py-2 px-4 rounded-lg font-medium transition-colors
                                               {{ !$item->is_sold_out
                                                   ? 'bg-blue-600 text-white hover:bg-blue-700'
                                                   : 'bg-gray-300 text-gray-500 cursor-not-allowed' }}">
                                    @if(!$item->is_sold_out)
                                        <i class="fas fa-cart-plus mr-2"></i>加入購物車
                                    @else
                                        <i class="fas fa-times-circle mr-2"></i>暫時供應完畢
                                    @endif
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($allItems->isEmpty())
                    <div class="text-center py-12">
                        <i class="fas fa-utensils text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">暫無菜單項目</h3>
                        <p class="text-gray-500">店家尚未添加菜單項目</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// 加入購物車功能
function addToCart(itemId, itemName, price, storeSlug) {
    const button = event.target;
    const originalText = button.innerHTML;

    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>加入中...';

    // 獲取 CSRF token
    const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
    if (!csrfTokenElement) {
        console.error('CSRF token meta tag not found');
        button.disabled = false;
        button.innerHTML = originalText;
        alert('系統錯誤，請重新整理頁面');
        return;
    }

    fetch(`/store/${storeSlug}/cart/add`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfTokenElement.getAttribute('content')
        },
        body: JSON.stringify({
            item_id: itemId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 更新購物車按鈕
            const cartButton = document.getElementById('cart-button');
            const badge = cartButton.querySelector('.cart-badge');

            if (badge) {
                badge.textContent = data.cart_count;
            } else {
                const newBadge = document.createElement('span');
                newBadge.className = 'cart-badge';
                newBadge.textContent = data.cart_count;
                cartButton.appendChild(newBadge);
            }

            // 顯示成功訊息
            showNotification('success', data.message);

            // 按鈕恢復原狀
            button.disabled = false;
            button.innerHTML = originalText;
        } else {
            throw new Error(data.message || '加入購物車失敗');
        }
    })
    .catch(error => {
        console.error('加入購物車失敗:', error);
        showNotification('error', '加入購物車失敗，請稍後再試');

        // 按鈕恢復原狀
        button.disabled = false;
        button.innerHTML = originalText;
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
</script>
@endsection