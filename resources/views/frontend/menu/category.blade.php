@extends('frontend.layouts.app')

@section('title', $category->name . ' - ' . $store->name)

@section('content')
<div class="bg-white">
    <!-- 麵包屑導航 -->
    <div class="bg-gray-50 border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <nav class="flex items-center text-sm text-gray-600">
                <a href="{{ route('frontend.store.detail', $store->store_slug_name) }}" class="hover:text-gray-900">
                    <i class="fas fa-home mr-1"></i>首頁
                </a>
                <span class="mx-2">/</span>
                <span class="text-gray-900">{{ $category->name }}</span>
            </nav>
        </div>
    </div>

    <!-- 分類資訊橫幅 -->
    @if($category->description || $category->image_url)
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row items-center">
                @if($category->image_url)
                    <div class="mb-4 md:mb-0 md:mr-6">
                        <img src="{{ $category->image_url }}" alt="{{ $category->name }}"
                             class="w-20 h-20 rounded-lg border-2 border-white shadow-lg object-cover">
                    </div>
                @endif
                <div>
                    <h1 class="text-2xl font-bold mb-2">
                        @if($category->icon)
                            <i class="{{ $category->icon }} mr-2"></i>
                        @endif
                        {{ $category->name }}
                    </h1>
                    @if($category->description)
                        <p class="text-blue-100">{{ $category->description }}</p>
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
                       class="block px-4 py-2 rounded-lg mb-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                        <i class="fas fa-th-large mr-2"></i>全部菜單
                    </a>

                    <!-- 其他分類 -->
                    @foreach($otherCategories as $otherCategory)
                        @if($otherCategory->menuItems->count() > 0)
                            <a href="{{ route('frontend.store.detail', $store->store_slug_name) }}#category-{{ $otherCategory->id }}"
                               class="block px-4 py-2 rounded-lg mb-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                                @if($otherCategory->icon)
                                    <i class="{{ $otherCategory->icon }} mr-2"></i>
                                @else
                                    <i class="fas fa-utensils mr-2"></i>
                                @endif
                                {{ $otherCategory->name }}
                                <span class="float-right">{{ $otherCategory->menuItems->count() }}</span>
                            </a>
                        @endif
                    @endforeach

                    <!-- 統計資訊 -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="text-sm text-gray-600">
                            <p class="mb-1">
                                <i class="fas fa-utensils mr-1"></i>
                                本分類商品：{{ $items->count() }} 項
                            </p>
                            <p>
                                <i class="fas fa-shopping-cart mr-1"></i>
                                購物車：
                                @if(session()->has('cart') && count(session('cart')) > 0)
                                    <span class="text-green-600 font-medium">{{ array_sum(session('cart')) }} 件</span>
                                @else
                                    <span class="text-gray-500">空</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 主要內容 - 分類項目 -->
            <div class="lg:col-span-3">
                <!-- 分類標題和排序 -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">
                        {{ $category->name }}
                        <span class="text-lg font-normal text-gray-500">({{ $items->count() }} 項商品)</span>
                    </h2>

                    <!-- 排序選項 -->
                    <div class="flex items-center space-x-4">
                        <label class="text-sm text-gray-600">排序：</label>
                        <select id="sort-select" class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="default">預設排序</option>
                            <option value="price-low">價格由低到高</option>
                            <option value="price-high">價格由高到低</option>
                            <option value="name">名稱排序</option>
                        </select>
                    </div>
                </div>

                <!-- 菜單項目網格 -->
                <div id="items-grid" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($items as $item)
                        <div class="menu-item-card item-card" data-price="{{ $item->price }}" data-name="{{ $item->name }}">
                            <!-- 商品圖片 -->
                            @if($item->getImageUrl())
                                <div class="relative h-48 bg-gray-100">
                                    <img src="{{ $item->getImageUrl() }}"
                                         alt="{{ $item->name }}"
                                         class="w-full h-full object-cover">

                                    <!-- 可用狀態標籤 -->
                                    @if(!$item->is_available)
                                        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                            <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm">
                                                暫時供應完畢
                                            </span>
                                        </div>
                                    @endif

                                    <!-- 標籤 -->
                                    @if($item->is_popular)
                                        <div class="absolute top-2 left-2">
                                            <span class="bg-red-500 text-white px-2 py-1 rounded-full text-xs">
                                                <i class="fas fa-star mr-1"></i>熱門
                                            </span>
                                        </div>
                                    @endif
                                    @if($item->is_new)
                                        <div class="absolute top-2 left-2">
                                            <span class="bg-green-500 text-white px-2 py-1 rounded-full text-xs">
                                                <i class="fas fa-sparkles mr-1"></i>新品
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="relative h-48 bg-gray-100 flex items-center justify-center">
                                    <i class="fas fa-utensils text-4xl text-gray-400"></i>
                                    @if(!$item->is_available)
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
                                @if($item->preparation_time || $item->spicy_level || $item->ingredients)
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
                                        @if($item->is_vegetarian)
                                            <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                                                <i class="fas fa-leaf mr-1"></i>素食
                                            </span>
                                        @endif
                                    </div>
                                @endif

                                <!-- 加入購物車按鈕 -->
                                <button onclick="addToCart({{ $item->id }}, '{{ $item->name }}', {{ $item->price }})"
                                        @if(!$item->is_available) disabled @endif
                                        class="w-full py-2 px-4 rounded-lg font-medium transition-colors
                                               {{ $item->is_available
                                                   ? 'bg-blue-600 text-white hover:bg-blue-700'
                                                   : 'bg-gray-300 text-gray-500 cursor-not-allowed' }}">
                                    @if($item->is_available)
                                        <i class="fas fa-cart-plus mr-2"></i>加入購物車
                                    @else
                                        <i class="fas fa-times-circle mr-2"></i>暫時供應完畢
                                    @endif
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($items->isEmpty())
                    <div class="text-center py-12">
                        <i class="fas fa-utensils text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">此分類暫無商品</h3>
                        <p class="text-gray-500 mb-4">店家尚未在此分類添加商品</p>
                        <a href="{{ route('frontend.store.detail', $store->store_slug_name) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-arrow-left mr-2"></i>返回全部菜單
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// 排序功能
document.getElementById('sort-select').addEventListener('change', function() {
    const sortValue = this.value;
    const grid = document.getElementById('items-grid');
    const items = Array.from(grid.querySelectorAll('.item-card'));

    let sortedItems;

    switch(sortValue) {
        case 'price-low':
            sortedItems = items.sort((a, b) =>
                parseFloat(a.dataset.price) - parseFloat(b.dataset.price)
            );
            break;
        case 'price-high':
            sortedItems = items.sort((a, b) =>
                parseFloat(b.dataset.price) - parseFloat(a.dataset.price)
            );
            break;
        case 'name':
            sortedItems = items.sort((a, b) =>
                a.dataset.name.localeCompare(b.dataset.name, 'zh-Hant')
            );
            break;
        default:
            sortedItems = items.sort((a, b) =>
                a.dataset.order - b.dataset.order
            );
    }

    // 重新排列 DOM 元素
    sortedItems.forEach(item => grid.appendChild(item));
});

// 加入購物車功能
function addToCart(itemId, itemName, price) {
    const button = event.target;
    const originalText = button.innerHTML;

    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>加入中...';

    fetch('/cart/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
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

            // 更新側邊欄統計
            updateCartCount(data.cart_count);

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

// 更新側邊欄購物車數量
function updateCartCount(count) {
    const cartCountElement = document.querySelector('.text-green-600.font-medium');
    if (cartCountElement) {
        cartCountElement.textContent = count + ' 件';
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

// 頁面載入時設置預設順序
document.addEventListener('DOMContentLoaded', function() {
    const items = document.querySelectorAll('.item-card');
    items.forEach((item, index) => {
        item.dataset.order = index;
    });
});
</script>
@endsection