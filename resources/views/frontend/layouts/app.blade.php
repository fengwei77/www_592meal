<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '592Meal') - 592Meal 訂餐平台</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Tailwind CSS -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <style>
        /* 自定義樣式 */
        .store-primary {
            @apply bg-blue-600 text-white;
        }

        .cart-badge {
            @apply bg-red-500 text-white rounded-full text-xs px-2 py-1 absolute -top-2 -right-2;
        }

        .menu-item-card {
            @apply border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200;
        }

        .price-tag {
            @apply text-xl font-bold text-green-600;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- 導航列 -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <!-- 店家名稱 -->
                    @if(isset($current_store))
                        <h1 class="text-xl font-semibold text-gray-900">
                            {{ $current_store->name }}
                        </h1>
                    @else
                        <h1 class="text-xl font-semibold text-gray-900">592Meal</h1>
                    @endif
                </div>

                <div class="flex items-center space-x-4">
                    <!-- 我的訂單按鈕 -->
                    @if(session('line_logged_in'))
                        <a href="{{ route('frontend.order.index') }}" class="hidden md:flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-receipt mr-2"></i>我的訂單
                        </a>

                        <!-- 通知設定按鈕 -->
                        <a href="{{ route('customer.notifications.settings') }}" class="hidden md:flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-bell mr-2"></i>通知設定
                        </a>
                    @endif

                    <!-- 購物車按鈕 (只在店家購物頁面顯示) -->
                    @if(request()->routeIs('frontend.store.detail') ||
                        request()->routeIs('frontend.cart.*') ||
                        request()->routeIs('frontend.order.create'))
                        <button id="cart-button" class="relative p-2 text-gray-600 hover:text-gray-900 transition-colors">
                            <i class="fas fa-shopping-cart text-lg"></i>
                            @if(session()->has('cart') && count(session('cart')) > 0)
                                <span class="cart-badge">{{ array_sum(session('cart')) }}</span>
                            @endif
                        </button>
                    @endif

                    <!-- LINE 登入/登出 -->
                    @if(session('line_logged_in'))
                        <div class="hidden md:flex items-center space-x-2">
                            @if(session('line_user.picture_url'))
                                <img src="{{ session('line_user.picture_url') }}" alt="LINE 頭像" class="w-8 h-8 rounded-full border border-green-500">
                            @endif
                            <span class="text-sm text-gray-700">{{ session('line_user.display_name') }}</span>
                            <form action="{{ route('line.logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-sign-out-alt"></i>
                                </button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('line.login') }}" class="hidden md:flex items-center px-3 py-2 text-sm font-medium text-white rounded-lg transition-all duration-200 hover:opacity-90" style="background-color: #06C755;">
                            <i class="fab fa-line mr-2"></i>登入
                        </a>
                    @endif

                    <!-- 選單按鈕 -->
                    <button id="mobile-menu-button" class="md:hidden p-2 text-gray-600 hover:text-gray-900">
                        <i class="fas fa-bars text-lg"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 移動端選單 -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t">
            <div class="px-2 pt-2 pb-3 space-y-1">
                @if(isset($current_store))
                    <a href="{{ route('frontend.store.detail', $current_store->store_slug_name) }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                        <i class="fas fa-home mr-2"></i>首頁
                    </a>
                @else
                    <a href="{{ route('frontend.stores.index') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                        <i class="fas fa-home mr-2"></i>店家清單
                    </a>
                @endif

                @if(session('line_logged_in'))
                    <a href="{{ route('frontend.order.index') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                        <i class="fas fa-receipt mr-2"></i>我的訂單
                    </a>

                    <!-- 通知設定（移動端） -->
                    <a href="{{ route('customer.notifications.settings') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                        <i class="fas fa-bell mr-2"></i>通知設定
                    </a>
                @endif

                <a href="{{ route('frontend.about') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                    <i class="fas fa-info-circle mr-2"></i>關於我們
                </a>
                <a href="{{ route('frontend.contact') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                    <i class="fas fa-phone mr-2"></i>聯絡我們
                </a>

                <!-- LINE 登入/登出（移動端） -->
                @if(session('line_logged_in'))
                    <div class="px-3 py-2 border-t">
                        <div class="flex items-center space-x-2 mb-2">
                            @if(session('line_user.picture_url'))
                                <img src="{{ session('line_user.picture_url') }}" alt="LINE 頭像" class="w-8 h-8 rounded-full border border-green-500">
                            @endif
                            <span class="text-sm text-gray-700">{{ session('line_user.display_name') }}</span>
                        </div>
                        <form action="{{ route('line.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full text-left px-3 py-2 rounded-md text-base font-medium text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i>登出
                            </button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('line.login') }}" class="block px-3 py-2 rounded-md text-base font-medium text-white hover:opacity-90" style="background-color: #06C755;">
                        <i class="fab fa-line mr-2"></i>LINE 登入
                    </a>
                @endif
            </div>
        </div>
    </nav>

    <!-- 主要內容 -->
    <main class="min-h-screen">
        @yield('content')
    </main>

    <!-- 底部 -->
    <footer class="bg-gray-800 text-white mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- 店家資訊 -->
                @if(isset($current_store))
                    <div>
                        <h3 class="text-lg font-semibold mb-4">{{ $current_store->name }}</h3>
                        @if($current_store->phone)
                            <p class="text-gray-300 mb-2">
                                <i class="fas fa-phone mr-2"></i>{{ $current_store->phone }}
                            </p>
                        @endif
                        @if($current_store->address)
                            <p class="text-gray-300">
                                <i class="fas fa-map-marker-alt mr-2"></i>{{ $current_store->address }}
                            </p>
                        @endif
                    </div>
                @endif

                <!-- 快速連結 -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">快速連結</h3>
                    <ul class="space-y-2">
                        @if(isset($current_store))
                            <li><a href="{{ route('frontend.store.detail', $current_store->store_slug_name) }}" class="text-gray-300 hover:text-white">菜單</a></li>
                            <li><a href="#" class="text-gray-300 hover:text-white" onclick="alert('購物車功能開發中')">購物車</a></li>
                        @else
                            <li><a href="{{ route('frontend.stores.index') }}" class="text-gray-300 hover:text-white">店家清單</a></li>
                        @endif
                        <li><a href="{{ route('frontend.about') }}" class="text-gray-300 hover:text-white">關於我們</a></li>
                        <li><a href="{{ route('frontend.contact') }}" class="text-gray-300 hover:text-white">聯絡我們</a></li>
                    </ul>
                </div>

                <!-- 營業時間 -->
                @if(isset($current_store) && $current_store->business_hours)
                    <div>
                        <h3 class="text-lg font-semibold mb-4">營業時間</h3>
                        <p class="text-gray-300">
                            請參考店家營業時間資訊
                        </p>
                    </div>
                @endif
            </div>

            <div class="border-t border-gray-700 mt-8 pt-8 text-center">
                <p class="text-gray-300">
                    &copy; {{ date('Y') }} 592Meal. 版權所有。
                </p>
            </div>
        </div>
    </footer>

    <!-- 調試工具 (僅在開發環境顯示) -->
    @if(config('app.env') === 'local')
    <div class="fixed bottom-4 right-4 z-50">
        <button onclick="window.simpleFormProtection.reset()"
                class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-sm shadow-lg"
                title="重置表單防護">
            🔓 重置防護
        </button>
        <button onclick="console.log('Form protection status:', window.simpleFormProtection.isSubmitting)"
                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg text-sm shadow-lg ml-2"
                title="檢查防護狀態">
            🔍 檢查狀態
        </button>
    </div>
    @endif

    <!-- 購物車側邊欄 -->
    <div id="cart-sidebar" class="fixed right-0 top-0 bg-white shadow-lg transform translate-x-full transition-transform duration-300 z-50 flex flex-col" style="width: 90vw; max-width: 800px; min-width: 320px; height: 100vh; max-height: 100vh;">
    <style>
        @media (max-width: 640px) {
            #cart-sidebar {
                width: 95vw !important;
                max-width: 95vw !important;
                min-width: 300px !important;
            }
        }
        @media (min-width: 641px) and (max-width: 768px) {
            #cart-sidebar {
                width: 90vw !important;
                max-width: 800px !important;
                min-width: 350px !important;
            }
        }
        @media (min-width: 769px) {
            #cart-sidebar {
                width: 800px !important;
                max-width: 800px !important;
            }
        }

        /* 購物車滾動條樣式 */
        .cart-items-container {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }
        .cart-items-container::-webkit-scrollbar {
            width: 8px;
        }
        .cart-items-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        .cart-items-container::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        .cart-items-container::-webkit-scrollbar-thumb:hover {
            background-color: #94a3b8;
        }
    </style>
        <div class="p-4 border-b flex justify-between items-center flex-shrink-0">
            <h3 class="text-lg font-semibold">購物車</h3>
            <button id="close-cart" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="cart-content" class="flex-1 overflow-hidden flex flex-col p-4">
            <!-- 購物車內容將通過 JavaScript 動態載入 -->
        </div>
    </div>

    <!-- 遮罩層 -->
    <div id="cart-overlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-40"></div>

    <!-- 防重複提交腳本 -->
    @vite(['resources/js/simple-form-protection.js'])

    <!-- JavaScript -->
    <script>
        // 購物車功能
        document.addEventListener('DOMContentLoaded', function() {
            const cartButton = document.getElementById('cart-button');
            const cartSidebar = document.getElementById('cart-sidebar');
            const closeCart = document.getElementById('close-cart');
            const cartOverlay = document.getElementById('cart-overlay');
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');

            // 移動端背景滾動鎖定
            function lockBodyScroll() {
                document.body.style.overflow = 'hidden';
                document.body.style.position = 'fixed';
                document.body.style.width = '100%';
            }

            function unlockBodyScroll() {
                document.body.style.overflow = '';
                document.body.style.position = '';
                document.body.style.width = '';
            }

            // 開啟購物車
            if (cartButton) {
                cartButton.addEventListener('click', function() {
                    cartSidebar.classList.remove('translate-x-full');
                    cartOverlay.classList.remove('hidden');
                    loadCartContent();
                    // 移動端鎖定背景滾動
                    if (window.innerWidth <= 768) {
                        lockBodyScroll();
                    }
                });
            }

            // 關閉購物車
            if (closeCart) {
                closeCart.addEventListener('click', function() {
                    cartSidebar.classList.add('translate-x-full');
                    cartOverlay.classList.add('hidden');
                    unlockBodyScroll();
                });
            }

            if (cartOverlay) {
                cartOverlay.addEventListener('click', function() {
                    cartSidebar.classList.add('translate-x-full');
                    cartOverlay.classList.add('hidden');
                    unlockBodyScroll();
                });
            }

            // 移動端選單
            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                });
            }

            // 載入購物車內容
            function loadCartContent() {
                // 如果有當前店家資訊，使用店家特定的購物車路由
                let cartUrl = '/cart';
                if (window.currentStore && window.currentStore.slug) {
                    cartUrl = `/store/${window.currentStore.slug}/cart`;
                }

                fetch(cartUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    }
                })
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('cart-content').innerHTML = html;
                        // 載入完成後初始化購物車滾動支援
                        initCartScrollSupport();
                    })
                    .catch(error => {
                        console.error('載入購物車失敗:', error);
                        document.getElementById('cart-content').innerHTML = '<p class="text-gray-500">載入失敗</p>';
                    });
            }

            // 初始化購物車滾動支援（適用於所有裝置）
            function initCartScrollSupport() {
                const cartContent = document.getElementById('cart-content');
                const scrollableArea = cartContent.querySelector('.cart-items-container');

                if (!scrollableArea) return;

                let isScrolling = false;

                // 桌機版鼠標滾動優化
                function handleWheelScroll(e) {
                    const scrollTop = scrollableArea.scrollTop;
                    const scrollHeight = scrollableArea.scrollHeight;
                    const clientHeight = scrollableArea.clientHeight;

                    const isAtTop = scrollTop <= 0;
                    const isAtBottom = scrollTop + clientHeight >= scrollHeight - 1;

                    // 如果已經在邊界且繼續向相同方向滾動，防止頁面背景滾動
                    if ((isAtTop && e.deltaY < 0) || (isAtBottom && e.deltaY > 0)) {
                        e.preventDefault();
                    }
                }

                // 移動端觸控支援
                function initTouchSupport() {
                    let touchStartY = 0;
                    let touchEndY = 0;

                    scrollableArea.addEventListener('touchstart', function(e) {
                        touchStartY = e.touches[0].clientY;
                    }, { passive: true });

                    scrollableArea.addEventListener('touchmove', function(e) {
                        touchEndY = e.touches[0].clientY;
                        const scrollTop = scrollableArea.scrollTop;
                        const scrollHeight = scrollableArea.scrollHeight;
                        const clientHeight = scrollableArea.clientHeight;

                        const isAtTop = scrollTop <= 0;
                        const isAtBottom = scrollTop + clientHeight >= scrollHeight - 1;

                        if ((isAtTop && touchEndY > touchStartY) || (isAtBottom && touchEndY < touchStartY)) {
                            e.preventDefault();
                        }
                    }, { passive: false });
                }

                // 改善滾動性能
                function optimizeScroll() {
                    scrollableArea.addEventListener('scroll', function() {
                        if (!isScrolling) {
                            scrollableArea.classList.add('scrolling');
                            isScrolling = true;
                        }

                        // 節流處理
                        if (this.scrollTimeout) {
                            clearTimeout(this.scrollTimeout);
                        }
                        this.scrollTimeout = setTimeout(() => {
                            scrollableArea.classList.remove('scrolling');
                            isScrolling = false;
                        }, 150);
                    }, { passive: true });
                }

                // 根據裝置類型初始化
                if (window.innerWidth <= 768) {
                    initTouchSupport();
                } else {
                    // 桌機版添加鼠標滾動處理
                    scrollableArea.addEventListener('wheel', handleWheelScroll, { passive: false });
                }

                // 通用滾動優化
                optimizeScroll();

                // 添加滾動條樣式
                const style = document.createElement('style');
                style.textContent = `
                    .cart-items-container {
                        scrollbar-width: thin;
                        scrollbar-color: #cbd5e1 transparent;
                    }
                    .cart-items-container::-webkit-scrollbar {
                        width: 6px;
                    }
                    .cart-items-container::-webkit-scrollbar-track {
                        background: transparent;
                    }
                    .cart-items-container::-webkit-scrollbar-thumb {
                        background-color: #cbd5e1;
                        border-radius: 3px;
                        transition: background-color 0.2s;
                    }
                    .cart-items-container::-webkit-scrollbar-thumb:hover {
                        background-color: #9ca3af;
                    }
                    .cart-items-container.scrolling::-webkit-scrollbar-thumb {
                        background-color: #6b7280;
                    }
                `;
                document.head.appendChild(style);
            }

            // 從購物車移除商品（側邊欄）
            window.removeFromCart = function(itemId) {
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
                        // 更新購物車數量
                        updateCartBadge(data.cart_count);

                        // 重新載入購物車內容
                        loadCartContent();

                        // 如果在購物車頁面，重新載入頁面
                        if (window.location.pathname === '/cart') {
                            location.reload();
                        }
                    } else {
                        showNotification('error', data.message || '移除失敗');
                    }
                })
                .catch(error => {
                    console.error('移除失敗:', error);
                    showNotification('error', '移除失敗，請稍後再試');
                });
            }

            // 更新購物車徽章
            function updateCartBadge(count) {
                const cartButton = document.getElementById('cart-button');
                const badge = cartButton.querySelector('.cart-badge');

                if (count > 0) {
                    if (badge) {
                        badge.textContent = count;
                    } else {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'cart-badge';
                        newBadge.textContent = count;
                        cartButton.appendChild(newBadge);
                    }
                } else {
                    if (badge) {
                        badge.remove();
                    }
                }
            }

            // 更新商品數量（完整購物車頁面）
            function updateQuantity(itemId, newQuantity) {
                const quantity = parseInt(newQuantity);

                if (quantity < 1 || quantity > 99 || isNaN(quantity)) {
                    return;
                }

                // 顯示載入狀態 - 檢查是否有對應的輸入元素
                const input = document.getElementById(`quantity-${itemId}`);
                if (input) {
                    // 如果有輸入元素（主要購物車頁面），則更新輸入框顯示
                    const originalValue = input.value;
                    input.value = '...';
                    input.disabled = true;
                }

                // 發送請求
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
                        // 如果有輸入元素，恢復其值
                        if (input) {
                            input.value = input.getAttribute('data-original-value') || '1';
                            input.disabled = false;
                        }
                        showNotification('error', data.message || '更新失敗');
                    }
                })
                .catch(error => {
                    console.error('更新失敗:', error);
                    // 如果有輸入元素，恢復其值
                    if (input) {
                        input.value = input.getAttribute('data-original-value') || '1';
                        input.disabled = false;
                    }
                    showNotification('error', '更新失敗，請稍後再試');
                });
            }

            // 更新購物車商品數量（側邊欄使用，帶確認對話框）
            window.updateCartQuantity = function(itemId, newQuantity) {
                const quantity = parseInt(newQuantity);

                // 當數量小於 1 時，顯示確認對話框
                if (quantity < 1) {
                    if (!confirm('確定要移除這個商品嗎？')) {
                        return;
                    }
                }

                // 驗證數量範圍
                if (quantity > 99 || isNaN(quantity)) {
                    showNotification('error', '商品數量必須在 1-99 之間');
                    return;
                }

                // 發送更新請求
                fetch('/cart/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        item_id: itemId,
                        quantity: Math.max(0, quantity)
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 重新載入購物車內容
                        loadCartContent();
                        // 更新購物車徽章
                        updateCartBadge(data.cart_count);
                        // 顯示成功通知
                        if (quantity < 1) {
                            showNotification('success', '商品已從購物車移除');
                        } else {
                            showNotification('success', '購物車已更新');
                        }
                    } else {
                        showNotification('error', data.message || '更新失敗');
                    }
                })
                .catch(error => {
                    console.error('更新失敗:', error);
                    showNotification('error', '更新失敗，請稍後再試');
                });
            };

            // 清空購物車（帶確認對話框）
            window.clearCartWithConfirm = function() {
                if (!confirm('確定要清空購物車嗎？所有商品都將被移除。')) {
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
                        // 重新載入購物車內容
                        loadCartContent();
                        // 更新購物車徽章
                        updateCartBadge(0);
                        // 顯示成功通知
                        showNotification('success', '購物車已清空');
                    } else {
                        showNotification('error', data.message || '清空失敗');
                    }
                })
                .catch(error => {
                    console.error('清空失敗:', error);
                    showNotification('error', '清空失敗，請稍後再試');
                });
            };
        });
    </script>

    @yield('scripts')
</body>
</html>