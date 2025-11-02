<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>訂單管理 - {{ $store->name }}</title>

    <!-- Tailwind CSS -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            -webkit-tap-highlight-color: transparent;
        }

        body {
            overflow: hidden;
            position: fixed;
            width: 100%;
            height: 100%;
        }

        .content-wrapper {
            height: calc(100vh - 60px); /* 扣除頂部狀態列高度 */
        }

        /* 手機版：上下2欄 */
        .pending-zone {
            height: 30vh;
            overflow-y: auto;
        }

        .main-zone {
            height: 70vh;
            overflow-y: auto;
        }

        /* 桌機版：左右2欄 */
        @media (min-width: 768px) {
            .main-zone {
                width: 60%;
                height: 100%;
            }

            .pending-zone {
                width: 40%;
                height: 100%;
            }
        }

        .order-card {
            position: relative;
            transition: transform 0.3s ease;
            touch-action: pan-y;
        }

        /* 交錯背景色 - 白色和淺綠色 */
        .order-card:nth-child(odd) {
            background-color: white;
        }

        .order-card:nth-child(even) {
            background-color: #fafff9; /* 淺綠色 */
        }

        .order-card.swiping {
            transition: none;
        }

        .swipe-indicator {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .swipe-indicator.left {
            left: 0;
            background: linear-gradient(to right, rgba(239, 68, 68, 0.9), transparent);
            color: white;
        }

        .swipe-indicator.right {
            right: 0;
            background: linear-gradient(to left, rgba(34, 197, 94, 0.9), transparent);
            color: white;
        }

        .swipe-indicator.active {
            opacity: 1;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .badge {
            @apply absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center;
        }

        /* 新訂單提示動畫 */
        @keyframes pulse-ring {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }
            50% {
                box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        .new-order-pulse {
            animation: pulse-ring 2s infinite;
        }

        /* 滑動指示器顏色 */
        .swipe-bg-reject {
            background: linear-gradient(to right, #ef4444, transparent);
        }

        .swipe-bg-confirm {
            background: linear-gradient(to left, #22c55e, transparent);
        }

        .swipe-bg-abandon {
            background: linear-gradient(to right, #f59e0b, transparent);
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- 頂部狀態列 -->
    <div class="bg-white border-b px-4 py-3 flex items-center justify-between sticky top-0 z-50">
        <div>
            <h1 class="text-lg font-bold text-gray-900">{{ $store->name }}</h1>
            <p class="text-xs text-gray-500">訂單管理系統</p>
        </div>
        <div class="flex items-center space-x-3">
            <div class="text-right">
                <div class="text-xs text-gray-500">今日營收</div>
                <div class="text-sm font-bold text-green-600" id="today-revenue">$0</div>
            </div>
            <button onclick="toggleSound()" id="sound-btn" class="p-2 rounded-full hover:bg-gray-100">
                <i class="fas fa-volume-up text-gray-600"></i>
            </button>
            @if(session('staff_authenticated'))
                <!-- 店員登出按鈕 -->
                <form action="{{ route('admin.store.staff.logout', $store->store_slug_name) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="p-2 rounded-full hover:bg-gray-100 text-gray-600" title="登出">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- 內容包裝容器 (手機:上下排列 / 桌機:左右排列) -->
    <div class="content-wrapper flex flex-col md:flex-row">
        <!-- 主要內容區 (手機:下方70% / 桌機:左側60%) -->
        <div class="main-zone bg-white order-2 md:order-1">
            <!-- 頁籤導航 -->
            <div class="flex border-b bg-white sticky top-0 z-10 overflow-x-auto">
                <button class="tab-btn flex-1 px-4 py-3 text-sm font-medium border-b-2 border-blue-600 text-blue-600 whitespace-nowrap" data-tab="confirmed">
                    <span>製作中</span>
                    <span class="ml-1 bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full text-xs">{{ $confirmedOrders->count() }}</span>
                </button>
                <button class="tab-btn flex-1 px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-600 hover:text-gray-900 whitespace-nowrap" data-tab="ready">
                    <span>待取貨</span>
                    <span class="ml-1 bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs">{{ $readyOrders->count() }}</span>
                </button>
                <button class="tab-btn flex-1 px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-600 hover:text-gray-900 whitespace-nowrap" data-tab="history">
                    <span>歷史記錄</span>
                    <span class="ml-1 bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs">{{ $historicalOrders->flatten()->count() }}</span>
                </button>
            </div>

            <!-- 頁籤內容 -->
            <div class="p-2">
                <!-- 製作中 -->
                <div id="confirmed-tab" class="tab-content active space-y-2">
                    <div class="text-xs text-gray-500 px-2 py-1">左滑退單 · 右滑標記為待取貨</div>
                    <div id="confirmed-orders-container">
                        @forelse($confirmedOrders as $order)
                            @include('store.orders.partials.order-card', [
                                'order' => $order,
                                'zone' => 'confirmed',
                                'leftSwipeAction' => 'reject',
                                'rightSwipeAction' => 'ready'
                            ])
                        @empty
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-clipboard-list text-4xl mb-2"></i>
                                <p>目前沒有製作中的訂單</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- 待取貨 -->
                <div id="ready-tab" class="tab-content space-y-2">
                    <div class="text-xs text-gray-500 px-2 py-1">右滑完成訂單 · 左滑標記棄單</div>
                    <div id="ready-orders-container">
                        @forelse($readyOrders as $order)
                            @include('store.orders.partials.order-card', [
                                'order' => $order,
                                'zone' => 'ready',
                                'leftSwipeAction' => 'abandon',
                                'rightSwipeAction' => 'complete'
                            ])
                        @empty
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-shopping-bag text-4xl mb-2"></i>
                                <p>目前沒有待取貨的訂單</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- 歷史記錄 -->
                <div id="history-tab" class="tab-content">
                    <div class="space-y-2">
                        @forelse($historicalOrders as $date => $orders)
                            <div class="border rounded-lg bg-gray-50">
                                <!-- 日期標題（可折疊） -->
                                <button
                                    class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-100 transition-colors"
                                    data-toggle="collapse"
                                    data-target="history-{{ $date }}"
                                >
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-calendar-day text-gray-600"></i>
                                        <div>
                                            <div class="font-semibold text-gray-900">
                                                {{ \Carbon\Carbon::parse($date)->locale('zh_TW')->isoFormat('YYYY年M月D日 (ddd)') }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                共 {{ $orders->count() }} 筆訂單
                                            </div>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200" id="icon-history-{{ $date }}"></i>
                                </button>

                                <!-- 折疊內容 -->
                                <div id="history-{{ $date }}" class="hidden border-t">
                                    <div class="p-2 space-y-2 bg-white">
                                        @foreach($orders as $order)
                                            @include('store.orders.partials.order-card-readonly', ['order' => $order])
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-history text-4xl mb-2"></i>
                                <p>目前沒有歷史訂單</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- 即時進單區 (手機:上方30% / 桌機:右側40%) -->
        <div class="pending-zone bg-red-50 border-b-4 md:border-b-0 md:border-l-4 border-red-500 order-1 md:order-2">
            <div class="px-4 py-2 bg-red-600 text-white flex items-center justify-between sticky top-0">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-bell fa-shake"></i>
                    <span class="font-semibold">新訂單</span>
                    <span class="bg-white text-red-600 px-2 py-0.5 rounded-full text-xs font-bold" id="pending-count">
                        {{ $pendingOrders->count() }}
                    </span>
                </div>
                <span class="text-xs hidden md:inline">左滑退單 · 右滑接單</span>
                <span class="text-xs md:hidden">滑動操作</span>
            </div>

            <div id="pending-orders-container" class="p-2 space-y-2">
                @forelse($pendingOrders as $order)
                    @include('store.orders.partials.order-card', [
                        'order' => $order,
                        'zone' => 'pending',
                        'leftSwipeAction' => 'reject',
                        'rightSwipeAction' => 'confirm'
                    ])
                @empty
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>目前沒有新訂單</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- 新訂單通知彈窗 -->
    <div id="new-order-notification" class="hidden fixed top-20 left-1/2 transform -translate-x-1/2 z-50 w-[90%] md:w-auto md:max-w-md px-2 md:px-0">
        <div class="bg-gradient-to-r from-red-600 to-orange-600 text-white rounded-lg shadow-2xl p-3 md:p-6 w-full">
            <div class="flex items-start gap-2 md:gap-4">
                <div class="flex-shrink-0 hidden sm:block">
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-white rounded-full flex items-center justify-center">
                        <i class="fas fa-bell text-red-600 text-lg md:text-2xl animate-pulse"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-base md:text-xl font-bold mb-1">🔔 新訂單通知！</h3>
                    <p class="text-white/90 mb-3 text-sm md:text-base" id="notification-message">您有新的訂單需要處理</p>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <button onclick="closeNotification()" class="flex-1 sm:flex-none px-4 py-2 text-sm md:text-base bg-white text-red-600 rounded-lg font-medium hover:bg-gray-100 transition-colors">
                            知道了
                        </button>
                        <button onclick="viewNewOrders()" class="flex-1 sm:flex-none px-4 py-2 text-sm md:text-base bg-red-800 text-white rounded-lg font-medium hover:bg-red-900 transition-colors">
                            查看訂單
                        </button>
                    </div>
                </div>
                <button onclick="closeNotification()" class="flex-shrink-0 text-white/70 hover:text-white -mt-1">
                    <i class="fas fa-times text-lg md:text-xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 推播設定提示 -->
    <div id="notification-permission-prompt" class="hidden fixed bottom-4 right-4 z-50">
        <div class="bg-white rounded-lg shadow-xl p-4 max-w-sm border-2 border-blue-500">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <i class="fas fa-bell text-blue-600 text-2xl"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-bold text-gray-900 mb-1">開啟訂單通知</h4>
                    <p class="text-sm text-gray-600 mb-3">即時接收新訂單推播通知，不錯過任何訂單</p>
                    <div class="flex space-x-2">
                        <button onclick="requestNotificationPermission()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                            開啟通知
                        </button>
                        <button onclick="closePermissionPrompt()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 transition-colors">
                            稍後
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 確認對話框 -->
    <div id="confirm-dialog" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 id="dialog-title" class="text-lg font-bold mb-2"></h3>
            <p id="dialog-message" class="text-gray-600 mb-4"></p>

            <!-- 棄單二次確認選項 -->
            <div id="abandon-confirm-options" class="hidden mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-sm font-medium text-gray-700 mb-3">請確認以下事項：</p>
                <label class="flex items-center space-x-2 mb-2 cursor-pointer">
                    <input type="checkbox" id="contacted-customer" class="w-4 h-4 text-orange-600 rounded">
                    <span class="text-sm text-gray-700">已嘗試聯繫顧客</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" id="wait-enough" class="w-4 h-4 text-orange-600 rounded">
                    <span class="text-sm text-gray-700">已等待足夠時間</span>
                </label>
            </div>

            <textarea id="dialog-reason" class="hidden w-full border rounded px-3 py-2 mb-4" rows="3" placeholder="請輸入原因（選填）"></textarea>

            <div class="flex space-x-2">
                <button onclick="closeDialog()" class="flex-1 px-4 py-2 border rounded-lg hover:bg-gray-50">
                    取消
                </button>
                <button id="dialog-extend-btn" class="hidden flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    延長等待
                </button>
                <button id="dialog-confirm-btn" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    確認
                </button>
            </div>
        </div>
    </div>

    <!-- 新訂單音效 -->
    <audio id="notification-sound" preload="auto">
        <!-- 使用較長的提示音，更容易注意到 -->
        <source src="data:audio/mp3;base64,SUQzBAAAAAAAI1RTU0UAAAAPAAADTGF2ZjU4Ljc2LjEwMAAAAAAAAAAAAAAA//tQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWGluZwAAAA8AAAACAAADhAC7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7v/////////////////////////////////////////////////////////////AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=" type="audio/mp3">
    </audio>

    <script>
        // 全局變數
        let soundEnabled = true;
        let currentSwipeElement = null;
        let startX = 0;
        let currentX = 0;
        let isSwiping = false;
        let pollingInterval = null;
        let lastCheckTime = new Date().toISOString();
        let notificationPermissionGranted = false;

        // CSRF Token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // 店家 slug
        const storeSlug = '{{ $store->store_slug_name }}';

        // 格式化等待時間顯示（時分格式）
        function formatWaitTime(minutes) {
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            return hours > 0 ? `${hours}小時${mins}分` : `${mins}分`;
        }

        // 頁籤切換
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabName = this.dataset.tab;

                // 更新按鈕狀態
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove('border-blue-600', 'text-blue-600');
                    b.classList.add('border-transparent', 'text-gray-600');
                });
                this.classList.remove('border-transparent', 'text-gray-600');
                this.classList.add('border-blue-600', 'text-blue-600');

                // 更新內容顯示
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById(tabName + '-tab').classList.add('active');
            });
        });

        // 音效切換
        function toggleSound() {
            soundEnabled = !soundEnabled;
            const btn = document.getElementById('sound-btn');
            btn.innerHTML = soundEnabled
                ? '<i class="fas fa-volume-up text-gray-600"></i>'
                : '<i class="fas fa-volume-mute text-gray-400"></i>';
        }

        // 播放提示音
        function playNotificationSound() {
            if (soundEnabled) {
                document.getElementById('notification-sound').play().catch(e => console.log('無法播放音效'));
            }
        }

        // 顯示 Toast 提示訊息
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            const bgColors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-orange-500',
                info: 'bg-blue-500'
            };
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };

            toast.className = `fixed top-20 right-4 ${bgColors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300 flex items-center space-x-2`;
            toast.innerHTML = `
                <i class="fas ${icons[type]}"></i>
                <span>${message}</span>
            `;

            document.body.appendChild(toast);

            // 動畫進入
            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
            }, 10);

            // 3秒後移除
            setTimeout(() => {
                toast.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        }

        // 初始化滑動手勢
        function initSwipeGesture(card) {
            let startX = 0;
            let currentX = 0;
            let isSwiping = false;

            card.addEventListener('touchstart', handleTouchStart, {passive: true});
            card.addEventListener('touchmove', handleTouchMove, {passive: false});
            card.addEventListener('touchend', handleTouchEnd, {passive: true});

            function handleTouchStart(e) {
                startX = e.touches[0].clientX;
                currentX = startX;
                isSwiping = true;
                card.classList.add('swiping');
            }

            function handleTouchMove(e) {
                if (!isSwiping) return;

                e.preventDefault();
                currentX = e.touches[0].clientX;
                const deltaX = currentX - startX;

                // 限制滑動範圍
                const maxSwipe = 100;
                const limitedDelta = Math.max(-maxSwipe, Math.min(maxSwipe, deltaX));

                card.style.transform = `translateX(${limitedDelta}px)`;

                // 顯示/隱藏指示器
                const leftIndicator = card.querySelector('.swipe-indicator.left');
                const rightIndicator = card.querySelector('.swipe-indicator.right');

                if (deltaX < -30 && leftIndicator) {
                    leftIndicator.classList.add('active');
                    rightIndicator?.classList.remove('active');
                } else if (deltaX > 30 && rightIndicator) {
                    rightIndicator.classList.add('active');
                    leftIndicator?.classList.remove('active');
                } else {
                    leftIndicator?.classList.remove('active');
                    rightIndicator?.classList.remove('active');
                }
            }

            function handleTouchEnd(e) {
                if (!isSwiping) return;

                const deltaX = currentX - startX;
                const threshold = 80;

                card.classList.remove('swiping');

                if (Math.abs(deltaX) > threshold) {
                    if (deltaX < 0) {
                        // 左滑
                        const action = card.dataset.leftAction;
                        if (action) {
                            handleSwipeAction(card, action);
                        }
                    } else {
                        // 右滑
                        const action = card.dataset.rightAction;
                        if (action) {
                            handleSwipeAction(card, action);
                        }
                    }
                }

                // 重置位置
                card.style.transform = '';
                card.querySelectorAll('.swipe-indicator').forEach(indicator => {
                    indicator.classList.remove('active');
                });

                isSwiping = false;
            }
        }

        // 處理滑動動作
        function handleSwipeAction(card, action) {
            const orderNumber = card.dataset.orderNumber;

            switch(action) {
                case 'confirm':
                    confirmOrder(orderNumber, card);
                    break;
                case 'reject':
                    showRejectDialog(orderNumber, card);
                    break;
                case 'ready':
                    markOrderReady(orderNumber, card);
                    break;
                case 'complete':
                    completeOrder(orderNumber, card);
                    break;
                case 'abandon':
                    showAbandonDialog(orderNumber, card);
                    break;
            }
        }

        // 確認訂單
        async function confirmOrder(orderNumber, card) {
            try {
                console.log('Confirming order:', orderNumber);

                const response = await fetch(`/store/${storeSlug}/manage/orders/${orderNumber}/confirm`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // 移除卡片動畫
                    card.style.transition = 'all 0.3s ease';
                    card.style.transform = 'translateX(100%)';
                    card.style.opacity = '0';

                    setTimeout(() => {
                        card.remove();
                        // 添加到製作中區域
                        addOrderToConfirmed(data.order);
                        // 接單後不應該添加到歷史記錄，只有完成或取消時才加入
                        updateCounts();
                    }, 300);
                } else {
                    // 檢查是否因為客戶取消訂單
                    if (data.cancelled && data.order) {
                        console.warn('訂單已被客戶取消:', data.message);
                        showToast('此訂單已被客戶取消', 'warning');

                        // 移除卡片並加入歷史記錄
                        card.style.transition = 'all 0.3s ease';
                        card.style.opacity = '0';
                        setTimeout(() => {
                            card.remove();
                            addOrderToHistory(data.order);
                            updateCounts();
                        }, 300);
                    } else {
                        // 訂單狀態不正確，可能已被處理
                        console.warn('訂單狀態已改變:', data.message);
                        showToast('此訂單已被處理，將自動移除', 'warning');

                        // 移除卡片
                        card.style.transition = 'all 0.3s ease';
                        card.style.opacity = '0';
                        setTimeout(() => {
                            card.remove();
                            updateCounts();
                        }, 300);
                    }
                }
            } catch (error) {
                console.error('確認訂單失敗:', error);
                showToast('確認訂單失敗，請重試', 'error');
                card.style.transform = '';
            }
        }

        // 顯示退單對話框
        function showRejectDialog(orderNumber, card) {
            // 檢查訂單是否在製作中區域
            const isInConfirmedZone = card.closest('#confirmed-orders-container');
            const dialogMessage = isInConfirmedZone
                ? '此訂單正在製作中，確定要退單嗎？\n（將會即時通知客戶）'
                : '確定要退回此訂單嗎？';

            showConfirmDialog(
                '確認退單',
                dialogMessage,
                true,
                async (reason) => {
                    await rejectOrder(orderNumber, card, reason);
                }
            );
            card.style.transform = '';
        }

        // 退單
        async function rejectOrder(orderNumber, card, reason) {
            try {
                const response = await fetch(`/store/${storeSlug}/manage/orders/${orderNumber}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ reason })
                });

                const data = await response.json();
                console.log('退單 API 回應:', data);

                if (data.success) {
                    console.log('退單成功，開始處理 UI 更新');
                    card.style.transition = 'all 0.3s ease';
                    card.style.transform = 'translateX(-100%)';
                    card.style.opacity = '0';

                    setTimeout(() => {
                        console.log('移除卡片並添加到歷史記錄');
                        card.remove();
                        // 退單直接添加到歷史記錄（因為之前沒有進入歷史記錄）
                        if (data.order) {
                            console.log('收到訂單資料，添加到歷史記錄:', data.order);
                            addOrderToHistory(data.order);
                        } else {
                            console.error('沒有收到訂單資料');
                        }
                        updateCounts();
                        updateStats();
                    }, 300);
                    showToast('訂單已退單', 'success');
                } else {
                    console.warn('退單失敗:', data.message);
                    showToast(data.message || '此訂單無法退單', 'warning');
                    card.style.opacity = '0';
                    setTimeout(() => {
                        card.remove();
                        updateCounts();
                        updateStats();
                    }, 300);
                }
            } catch (error) {
                console.error('退單失敗:', error);
                showToast('操作失敗，請稍後再試', 'error');
            }
        }

        // 標記為待取貨
        async function markOrderReady(orderNumber, card) {
            try {
                console.log('開始標記為待取貨:', orderNumber);
                const response = await fetch(`/store/${storeSlug}/manage/orders/${orderNumber}/ready`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const data = await response.json();
                console.log('標記為待取貨 API 回應:', data);

                if (data.success) {
                    console.log('標記為待取貨成功，開始處理 UI 更新');
                    card.style.transition = 'all 0.3s ease';
                    card.style.transform = 'translateX(100%)';
                    card.style.opacity = '0';

                    setTimeout(() => {
                        console.log('移除卡片並添加到待取貨區');
                        card.remove();
                        addOrderToReady(data.order);
                        // 待取貨狀態不應該添加到歷史記錄，只有完成或取消時才加入
                        updateCounts();
                    }, 300);
                } else {
                    // 檢查是否因為客戶取消訂單
                    if (data.cancelled && data.order) {
                        console.warn('訂單已被客戶取消:', data.message);
                        showToast('此訂單已被客戶取消', 'warning');

                        // 移除卡片並加入歷史記錄
                        card.style.transition = 'all 0.3s ease';
                        card.style.opacity = '0';
                        setTimeout(() => {
                            card.remove();
                            addOrderToHistory(data.order);
                            updateCounts();
                        }, 300);
                    } else {
                        console.warn('更新狀態失敗:', data.message);
                        showToast(data.message || '此訂單無法更新狀態', 'warning');
                        card.style.opacity = '0';
                        setTimeout(() => {
                            card.remove();
                            updateCounts();
                        }, 300);
                    }
                }
            } catch (error) {
                console.error('更新狀態失敗:', error);
                showToast('操作失敗，請稍後再試', 'error');
                card.style.transform = '';
            }
        }

        // 完成訂單
        async function completeOrder(orderNumber, card) {
            try {
                console.log('開始完成訂單:', orderNumber);
                const response = await fetch(`/store/${storeSlug}/manage/orders/${orderNumber}/complete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const data = await response.json();
                console.log('完成訂單 API 回應:', data);

                if (data.success) {
                    console.log('完成訂單成功，開始處理 UI 更新');
                    card.style.transition = 'all 0.3s ease';
                    card.style.transform = 'translateX(100%)';
                    card.style.opacity = '0';

                    setTimeout(() => {
                        console.log('移除卡片並更新歷史記錄');
                        card.remove();
                        // 更新歷史記錄中的訂單狀態
                        if (data.order) {
                            console.log('收到訂單資料，更新歷史記錄:', data.order);
                            addOrderToHistory(data.order);
                        } else {
                            console.error('沒有收到訂單資料');
                        }
                        updateCounts();
                        updateStats();
                    }, 300);
                    showToast('訂單已完成', 'success');
                } else {
                    console.warn('完成訂單失敗:', data.message);
                    showToast(data.message || '此訂單無法完成', 'warning');
                    card.style.opacity = '0';
                    setTimeout(() => {
                        card.remove();
                        updateCounts();
                        updateStats();
                    }, 300);
                }
            } catch (error) {
                console.error('完成訂單失敗:', error);
                showToast('操作失敗，請稍後再試', 'error');
                card.style.transform = '';
            }
        }

        // 顯示棄單對話框
        function showAbandonDialog(orderNumber, card) {
            showConfirmDialog(
                '確認棄單',
                '此訂單已準備好等待取餐，請確認以下事項後再標記為棄單：',
                true,
                async (reason) => {
                    await abandonOrder(orderNumber, card, reason);
                },
                true  // isAbandon = true
            );
            card.style.transform = '';
        }

        // 棄單
        async function abandonOrder(orderNumber, card, reason) {
            try {
                const response = await fetch(`/store/${storeSlug}/manage/orders/${orderNumber}/abandon`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ reason })
                });

                const data = await response.json();
                console.log('棄單 API 回應:', data);

                if (data.success) {
                    console.log('棄單成功，開始處理 UI 更新');
                    card.style.transition = 'all 0.3s ease';
                    card.style.transform = 'translateX(-100%)';
                    card.style.opacity = '0';

                    setTimeout(() => {
                        console.log('移除卡片並更新歷史記錄');
                        card.remove();
                        // 更新歷史記錄中的訂單狀態
                        if (data.order) {
                            console.log('收到訂單資料，更新歷史記錄:', data.order);
                            addOrderToHistory(data.order);
                        } else {
                            console.error('沒有收到訂單資料');
                        }
                        updateCounts();
                        updateStats();
                    }, 300);
                    showToast('訂單已標記為棄單', 'success');
                } else {
                    console.warn('棄單失敗:', data.message);
                    showToast(data.message || '此訂單無法棄單', 'warning');
                    card.style.opacity = '0';
                    setTimeout(() => {
                        card.remove();
                        updateCounts();
                        updateStats();
                    }, 300);
                }
            } catch (error) {
                console.error('棄單失敗:', error);
                showToast('操作失敗，請稍後再試', 'error');
            }
        }

        // 顯示確認對話框
        function showConfirmDialog(title, message, needReason, onConfirm, isAbandon = false) {
            const dialog = document.getElementById('confirm-dialog');
            document.getElementById('dialog-title').textContent = title;
            document.getElementById('dialog-message').textContent = message;

            const reasonTextarea = document.getElementById('dialog-reason');
            const abandonOptions = document.getElementById('abandon-confirm-options');
            const extendBtn = document.getElementById('dialog-extend-btn');
            const confirmBtn = document.getElementById('dialog-confirm-btn');

            // 重置勾選框
            document.getElementById('contacted-customer').checked = false;
            document.getElementById('wait-enough').checked = false;

            // 根據是否為棄單操作顯示相應元素
            if (isAbandon) {
                abandonOptions.classList.remove('hidden');
                extendBtn.classList.remove('hidden');
                confirmBtn.textContent = '確認棄單';
            } else {
                abandonOptions.classList.add('hidden');
                extendBtn.classList.add('hidden');
                confirmBtn.textContent = '確認';
            }

            if (needReason) {
                reasonTextarea.classList.remove('hidden');
                reasonTextarea.value = '';
            } else {
                reasonTextarea.classList.add('hidden');
            }

            // 確認按鈕處理
            confirmBtn.onclick = () => {
                if (isAbandon) {
                    const contacted = document.getElementById('contacted-customer').checked;
                    const waitEnough = document.getElementById('wait-enough').checked;

                    if (!contacted || !waitEnough) {
                        alert('請確認已完成所有必要步驟（聯繫顧客及等待足夠時間）');
                        return;
                    }
                }

                const reason = needReason ? reasonTextarea.value : null;
                onConfirm(reason);
                closeDialog();
            };

            // 延長等待按鈕處理
            extendBtn.onclick = () => {
                alert('已延長等待時間 15 分鐘');
                closeDialog();
                // TODO: 實作延長等待的邏輯（更新訂單的 updated_at 時間）
            };

            dialog.classList.remove('hidden');
        }

        // 關閉對話框
        function closeDialog() {
            document.getElementById('confirm-dialog').classList.add('hidden');
        }

        // 折疊/展開日期面板
        function toggleCollapse(elementId) {
            console.log('toggleCollapse 被調用，elementId:', elementId);

            const content = document.getElementById(elementId);
            const icon = document.getElementById('icon-' + elementId);

            console.log('找到的元素:', {
                content: !!content,
                icon: !!icon,
                contentHidden: content ? content.classList.contains('hidden') : 'N/A'
            });

            if (!content || !icon) {
                console.warn('找不到元素，elementId:', elementId);
                return;
            }

            if (content.classList.contains('hidden')) {
                // 展開
                content.classList.remove('hidden');
                icon.classList.add('rotate-180');
                console.log('已展開:', elementId);
            } else {
                // 折疊
                content.classList.add('hidden');
                icon.classList.remove('rotate-180');
                console.log('已折疊:', elementId);
            }
        }

        // 桌機版按鈕處理函數
        function handleDesktopAction(orderNumber, action) {
            const card = document.querySelector(`[data-order-number="${orderNumber}"]`);
            if (!card) return;

            switch(action) {
                case 'confirm':
                    confirmOrder(orderNumber, card);
                    break;
                case 'reject':
                    showRejectDialog(orderNumber, card);
                    break;
                case 'ready':
                    markOrderReady(orderNumber, card);
                    break;
                case 'complete':
                    completeOrder(orderNumber, card);
                    break;
                case 'abandon':
                    showAbandonDialog(orderNumber, card);
                    break;
            }
        }

        // 建立訂單卡片HTML
        function createOrderCard(order, zone, leftAction = '', rightAction = '') {
            const orderNumber = order.order_number;
            const shortNumber = orderNumber.substring(orderNumber.length - 6);
            const createdAt = new Date(order.created_at);
            const timeString = createdAt.toLocaleTimeString('zh-TW', { hour: '2-digit', minute: '2-digit' });

            // 計算時間差
            const now = new Date();
            const diffMs = now - createdAt;
            const diffMins = Math.floor(diffMs / 60000);
            const timeAgo = diffMins < 1 ? '剛剛' : diffMins < 60 ? `${diffMins}分鐘前` : `${Math.floor(diffMins / 60)}小時前`;

            let leftIndicator = '';
            if (leftAction === 'reject') {
                leftIndicator = '<div class="swipe-indicator left"><i class="fas fa-times-circle"></i></div>';
            } else if (leftAction === 'abandon') {
                leftIndicator = '<div class="swipe-indicator left"><i class="fas fa-ban"></i></div>';
            }

            let rightIndicator = '';
            if (rightAction === 'confirm') {
                rightIndicator = '<div class="swipe-indicator right"><i class="fas fa-check-circle"></i></div>';
            } else if (rightAction === 'ready') {
                rightIndicator = '<div class="swipe-indicator right"><i class="fas fa-bell"></i></div>';
            } else if (rightAction === 'complete') {
                rightIndicator = '<div class="swipe-indicator right"><i class="fas fa-check-double"></i></div>';
            }

            let leftButton = '';
            let rightButton = '';

            if (leftAction === 'reject') {
                leftButton = `<button onclick="handleDesktopAction('${orderNumber}', 'reject')" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors"><i class="fas fa-times-circle mr-2"></i>退單</button>`;
            } else if (leftAction === 'abandon') {
                leftButton = `<button onclick="handleDesktopAction('${orderNumber}', 'abandon')" class="flex-1 px-4 py-2 text-white rounded-lg font-medium transition-colors" style="background-color: #546e7a !important;" onmouseover="this.style.backgroundColor='#455a64 !important'" onmouseout="this.style.backgroundColor='#546e7a !important'"><i class="fas fa-ban mr-2"></i>棄單</button>`;
            }

            if (rightAction === 'confirm') {
                rightButton = `<button onclick="handleDesktopAction('${orderNumber}', 'confirm')" class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors"><i class="fas fa-check-circle mr-2"></i>接單</button>`;
            } else if (rightAction === 'ready') {
                rightButton = `<button onclick="handleDesktopAction('${orderNumber}', 'ready')" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"><i class="fas fa-bell mr-2"></i>待取貨</button>`;
            } else if (rightAction === 'complete') {
                rightButton = `<button onclick="handleDesktopAction('${orderNumber}', 'complete')" class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors"><i class="fas fa-check-double mr-2"></i>完成</button>`;
            }

            const desktopButtons = (leftButton || rightButton) ?
                `<div class="hidden md:flex border-t mt-3 pt-3 space-x-2">${leftButton}${rightButton}</div>` : '';

            // 商品列表
            let itemsHtml = '';
            if (order.order_items && order.order_items.length > 0) {
                itemsHtml = order.order_items.map(item => `
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center space-x-2 flex-1">
                            <span class="w-6 h-6 bg-blue-100 text-blue-600 rounded flex items-center justify-center text-xs font-bold">${item.quantity}</span>
                            <span class="text-gray-700">${item.menu_item?.name || '商品已下架'}</span>
                        </div>
                        <span class="text-gray-600">$${parseInt(item.total_price).toLocaleString()}</span>
                    </div>
                `).join('');
            }

            // 備註
            const notesHtml = order.notes ? `
                <div class="border-t mt-3 pt-3">
                    <div class="flex items-start space-x-2">
                        <i class="fas fa-comment-dots text-orange-500 mt-0.5"></i>
                        <div class="flex-1">
                            <div class="text-xs font-medium text-gray-700 mb-1">顧客備註</div>
                            <div class="text-sm text-gray-600 bg-orange-50 rounded p-2">${order.notes}</div>
                        </div>
                    </div>
                </div>
            ` : '';

            const newOrderBadge = zone === 'pending' ?
                '<span class="bg-red-100 text-red-800 text-xs px-2 py-0.5 rounded-full font-medium new-order-pulse">新訂單</span>' : '';

            // 待取貨狀態的等待時間徽章
            let waitTimeBadge = '';
            if (zone === 'ready' && order.updated_at) {
                const updatedAt = new Date(order.updated_at);
                const waitMs = now - updatedAt;
                const waitMinutes = Math.floor(waitMs / 60000);

                let badgeClass = 'bg-green-100 text-green-800';
                let statusText = '正常';
                if (waitMinutes >= 30) {
                    badgeClass = 'bg-red-100 text-red-800';
                    statusText = '請聯繫顧客';
                } else if (waitMinutes >= 15) {
                    badgeClass = 'bg-yellow-100 text-yellow-800';
                    statusText = '等待中';
                }

                const timeDisplay = formatWaitTime(waitMinutes);

                waitTimeBadge = `<span class="${badgeClass} text-xs px-2 py-0.5 rounded-full font-medium" data-wait-time="${waitMinutes}" data-updated-at="${Math.floor(updatedAt.getTime() / 1000)}">
                    <i class="fas fa-hourglass-half mr-1"></i>
                    <span class="wait-time-text">${statusText} (${timeDisplay})</span>
                </span>`;
            }

            return `
                <div class="order-card rounded-lg shadow-sm p-4 relative"
                     data-order-number="${orderNumber}"
                     data-left-action="${leftAction}"
                     data-right-action="${rightAction}">
                    ${leftIndicator}
                    ${rightIndicator}
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-1">
                                <span class="text-lg font-bold text-gray-900">#${shortNumber}</span>
                                ${newOrderBadge}
                                ${waitTimeBadge}
                            </div>
                            <div class="text-xs text-gray-500">
                                <i class="fas fa-clock mr-1"></i>${timeString}
                                <span class="mx-1">·</span>${timeAgo}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-green-600">$${parseInt(order.total_amount).toLocaleString()}</div>
                            <div class="text-xs text-gray-500">${order.order_items?.length || 0} 件</div>
                        </div>
                    </div>
                    <div class="border-t pt-3 mb-3">
                        <div class="flex items-center space-x-2">
                            ${order.line_picture_url ?
                                `<img src="${order.line_picture_url}" alt="LINE 頭像" class="w-8 h-8 rounded-full border border-green-500">` :
                                `<div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center"><i class="fas fa-user text-gray-400 text-xs"></i></div>`
                            }
                            <div class="flex-1">
                                <div class="font-medium text-sm">
                                    ${order.line_display_name ? '<i class="fab fa-line text-green-600 mr-1 text-xs"></i>' : ''}
                                    ${order.customer_name}
                                </div>
                                ${order.customer_phone ? `<div class="text-xs text-gray-500"><i class="fas fa-phone mr-1"></i>${order.customer_phone}</div>` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="border-t pt-3 space-y-2">${itemsHtml}</div>
                    ${notesHtml}
                    ${desktopButtons}
                </div>
            `;
        }

        // 添加訂單到製作中
        function addOrderToConfirmed(order) {
            const container = document.getElementById('confirmed-orders-container');
            const emptyMessage = container.querySelector('.text-center.py-8');

            if (emptyMessage) {
                emptyMessage.remove();
            }

            const cardHtml = createOrderCard(order, 'confirmed', 'reject', 'ready');
            container.insertAdjacentHTML('afterbegin', cardHtml);

            const newCard = container.firstElementChild;
            initSwipeGesture(newCard);

            // 添加動畫效果
            newCard.style.opacity = '0';
            newCard.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                newCard.style.transition = 'all 0.3s ease';
                newCard.style.opacity = '1';
                newCard.style.transform = 'translateY(0)';
            }, 10);
        }

        // 添加訂單到待取貨
        function addOrderToReady(order) {
            const container = document.getElementById('ready-orders-container');
            const emptyMessage = container.querySelector('.text-center.py-8');

            if (emptyMessage) {
                emptyMessage.remove();
            }

            const cardHtml = createOrderCard(order, 'ready', 'abandon', 'complete');
            container.insertAdjacentHTML('afterbegin', cardHtml);

            const newCard = container.firstElementChild;
            initSwipeGesture(newCard);

            // 添加動畫效果
            newCard.style.opacity = '0';
            newCard.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                newCard.style.transition = 'all 0.3s ease';
                newCard.style.opacity = '1';
                newCard.style.transform = 'translateY(0)';
            }, 10);
        }


        // 更新計數
        function updateCounts() {
            console.log('更新計數...');

            // 更新新訂單計數
            const pendingCount = document.querySelectorAll('#pending-orders-container .order-card').length;
            const pendingElement = document.getElementById('pending-count');
            if (pendingElement) {
                pendingElement.textContent = pendingCount;
                console.log('新訂單計數:', pendingCount);
            }

            // 更新各頁籤計數
            const confirmedCount = document.querySelectorAll('#confirmed-orders-container .order-card').length;
            const readyCount = document.querySelectorAll('#ready-orders-container .order-card').length;

            console.log('製作中訂單計數:', confirmedCount);
            console.log('待取貨訂單計數:', readyCount);

            // 更新頁籤上的數字
            document.querySelectorAll('.tab-btn').forEach(btn => {
                const tab = btn.dataset.tab;
                const badge = btn.querySelector('.px-2');
                if (!badge) return;

                switch(tab) {
                    case 'confirmed':
                        badge.textContent = confirmedCount;
                        console.log('更新製作中頁籤計數:', confirmedCount);
                        break;
                    case 'ready':
                        badge.textContent = readyCount;
                        console.log('更新待取貨頁籤計數:', readyCount);
                        break;
                }
            });

            // 同時更新歷史記錄計數
            updateHistoryTabCount();
        }

        // 添加或更新歷史記錄中的訂單
        function addOrderToHistory(order) {
            try {
                console.log('添加/更新歷史記錄中的訂單:', order);

                const historyTab = document.getElementById('history-tab');
                if (!historyTab) {
                    console.error('找不到歷史記錄頁籤');
                    return;
                }

                console.log('找到歷史記錄頁籤:', historyTab);

                // 檢查訂單是否已存在於歷史記錄中
                const existingOrderCard = document.querySelector(`#history-tab [data-order-number="${order.order_number}"]`);
                if (existingOrderCard) {
                    console.log('訂單已存在於歷史記錄中，更新狀態');
                    updateOrderInHistory(existingOrderCard, order);
                    return;
                }

                console.log('訂單不存在於歷史記錄中，新增訂單');

                // 檢查主要容器
                let mainContainer = historyTab.querySelector('.space-y-2');
                if (!mainContainer) {
                    console.error('找不到主要歷史記錄容器 .space-y-2');
                    return;
                }

                console.log('找到主要容器:', mainContainer);

                // 檢查並移除空訊息
                const emptyMessage = mainContainer.querySelector('.text-center.py-8');
                if (emptyMessage) {
                    console.log('移除空訊息');
                    emptyMessage.remove();
                }

                // 取得今天的日期
                const today = new Date().toISOString().split('T')[0];
                const orderDate = new Date(order.updated_at || order.created_at).toISOString().split('T')[0];

                console.log('今天日期:', today, '訂單日期:', orderDate);

                // 尋找或創建日期組
                let dateGroup = mainContainer.querySelector(`[data-history-date="${today}"]`);
                let isExistingGroup = !!dateGroup;

                console.log('找到現有日期組:', isExistingGroup);

                if (!dateGroup) {
                    console.log('創建新的日期組');
                    // 創建新的日期組，匹配原始 HTML 結構
                    dateGroup = document.createElement('div');
                    dateGroup.className = 'border rounded-lg bg-gray-50';
                    dateGroup.setAttribute('data-history-date', today);

                    const dateFormatted = new Date().toLocaleDateString('zh-TW', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        weekday: 'short'
                    });

                    dateGroup.innerHTML = `
                        <button class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-100 transition-colors"
                                data-toggle="collapse"
                                data-target="history-${today}">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-calendar-day text-gray-600"></i>
                                <div>
                                    <div class="font-semibold text-gray-900">${dateFormatted}</div>
                                    <div class="text-xs text-gray-500">
                                        共 <span class="history-count">1</span> 筆訂單
                                    </div>
                                </div>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200"
                               id="icon-history-${today}"></i>
                        </button>
                        <div id="history-${today}" class="hidden border-t">
                            <div class="p-2 space-y-2 bg-white">
                                <!-- 訂單卡片將插入這裡 -->
                            </div>
                        </div>
                    `;

                    // 插入到容器的開頭
                    mainContainer.insertBefore(dateGroup, mainContainer.firstChild);

                    // 自動展開今天的日期組
                    setTimeout(() => {
                        const contentDiv = document.getElementById(`history-${today}`);
                        const iconDiv = document.getElementById(`icon-history-${today}`);
                        if (contentDiv) {
                            contentDiv.classList.remove('hidden');
                        }
                        if (iconDiv) {
                            iconDiv.classList.add('rotate-180');
                        }
                    }, 100);
                } else {
                    console.log('使用現有日期組');
                    // 更新計數
                    const countSpan = dateGroup.querySelector('.history-count');
                    if (countSpan) {
                        const currentCount = parseInt(countSpan.textContent);
                        countSpan.textContent = currentCount + 1;
                        console.log('更新計數:', currentCount, '→', currentCount + 1);
                    }
                }

                // 找到訂單容器（匹配原始結構：div.border-t > div.p-2.space-y-2.bg-white）
                let orderContainer;
                if (isExistingGroup) {
                    orderContainer = dateGroup.querySelector('.border-t .p-2.space-y-2.bg-white');
                } else {
                    orderContainer = dateGroup.querySelector('#history-' + today + ' .p-2.space-y-2.bg-white');
                }

                if (!orderContainer) {
                    console.error('找不到訂單容器');
                    console.log('dateGroup HTML:', dateGroup.innerHTML);
                    return;
                }

                console.log('找到訂單容器:', orderContainer);

                // 創建歷史訂單卡片
                const cardHtml = createHistoryOrderCard(order);
                console.log('創建的卡片 HTML:', cardHtml);

                // 插入訂單卡片
                orderContainer.insertAdjacentHTML('afterbegin', cardHtml);

                // 添加動畫效果
                const newCard = orderContainer.firstElementChild;
                if (newCard) {
                    newCard.style.opacity = '0';
                    newCard.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        newCard.style.transition = 'all 0.3s ease';
                        newCard.style.opacity = '1';
                        newCard.style.transform = 'translateY(0)';
                        console.log('卡片動畫完成');
                    }, 10);
                } else {
                    console.error('找不到新創建的卡片元素');
                }

                // 更新歷史記錄頁籤的計數
                updateHistoryTabCount();

                // 自動切換到歷史記錄頁籤並展開
                setTimeout(() => {
                    console.log('開始切換到歷史記錄頁籤');
                    // 切換到歷史記錄頁籤
                    document.querySelectorAll('.tab-btn').forEach(btn => {
                        btn.classList.remove('border-blue-600', 'text-blue-600');
                        btn.classList.add('border-transparent', 'text-gray-600');
                    });

                    const historyTabBtn = document.querySelector('.tab-btn[data-tab="history"]');
                    if (historyTabBtn) {
                        historyTabBtn.classList.remove('border-transparent', 'text-gray-600');
                        historyTabBtn.classList.add('border-blue-600', 'text-blue-600');
                        console.log('歷史記錄頁籤按鈕已切換');
                    } else {
                        console.error('找不到歷史記錄頁籤按鈕');
                    }

                    // 顯示歷史記錄內容
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    historyTab.classList.add('active');

                    console.log('已切換到歷史記錄頁籤');

                    // 確保今日日期組展開（不管是新創建還是已存在的）
                    const todayDateGroup = historyTab.querySelector(`[data-history-date="${today}"]`);
                    if (todayDateGroup) {
                        const contentId = `history-${today}`;
                        const iconId = `icon-history-${today}`;
                        const contentDiv = document.getElementById(contentId);
                        const iconDiv = document.getElementById(iconId);

                        console.log('找到今日日期組，確保展開:', {
                            contentId,
                            iconId,
                            contentExists: !!contentDiv,
                            iconExists: !!iconDiv,
                            contentHidden: contentDiv ? contentDiv.classList.contains('hidden') : 'N/A'
                        });

                        if (contentDiv && contentDiv.classList.contains('hidden')) {
                            contentDiv.classList.remove('hidden');
                            console.log('已展開今日日期組內容');
                        }
                        if (iconDiv && !iconDiv.classList.contains('rotate-180')) {
                            iconDiv.classList.add('rotate-180');
                            console.log('已更新今日日期組圖標');
                        }
                    } else {
                        console.warn('找不到今日日期組');
                    }
                }, 100); // 縮短延遲時間從300ms到100ms

                console.log('訂單已成功添加到歷史記錄');
            } catch (error) {
                console.error('addOrderToHistory 函數執行失敗:', error);
                console.error('錯誤堆疊:', error.stack);
            }
        }

        // 建立歷史訂單卡片HTML
        function createHistoryOrderCard(order) {
            const orderNumber = order.order_number;
            const shortNumber = orderNumber.substring(orderNumber.length - 6);
            const updatedAt = new Date(order.updated_at || order.created_at);
            const timeString = updatedAt.toLocaleTimeString('zh-TW', { hour: '2-digit', minute: '2-digit' });

            // 判斷訂單狀態
            let statusBadge = '';
            if (order.status === 'cancelled') {
                if (order.cancellation_type === 'rejected') {
                    statusBadge = '<span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium"><i class="fas fa-times mr-1"></i>退單</span>';
                } else if (order.cancellation_type === 'abandoned') {
                    statusBadge = '<span class="bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded-full font-medium"><i class="fas fa-ban mr-1"></i>棄單</span>';
                } else if (order.cancellation_type === 'customer_cancelled') {
                    statusBadge = '<span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded-full font-medium"><i class="fas fa-user-times mr-1"></i>客人取消</span>';
                } else {
                    statusBadge = '<span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded-full font-medium"><i class="fas fa-times mr-1"></i>已取消</span>';
                }
            } else if (order.status === 'completed') {
                statusBadge = '<span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-medium"><i class="fas fa-check mr-1"></i>已完成</span>';
            }

            // 商品列表
            let itemsHtml = '';
            const orderItems = order.order_items || order.orderItems; // 支援兩種欄位名稱
            console.log('訂單商品資料:', orderItems);

            if (orderItems && orderItems.length > 0) {
                itemsHtml = orderItems.map(item => `
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center space-x-2 flex-1">
                            <span class="w-5 h-5 bg-gray-100 text-gray-600 rounded flex items-center justify-center text-xs">${item.quantity}</span>
                            <span class="text-gray-600">${item.menu_item?.name || item.menuItem?.name || '商品已下架'}</span>
                        </div>
                        <span class="text-gray-500">$${parseInt(item.total_price || item.totalPrice).toLocaleString()}</span>
                    </div>
                `).join('');
            } else {
                console.warn('沒有找到商品資料，orderItems:', orderItems);
            }

            // 取消原因
            const reasonHtml = order.cancellation_reason ? `
                <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                    <i class="fas fa-info-circle mr-1"></i>
                    ${order.cancellation_reason}
                </div>
            ` : '';

            return `
                <div class="order-card rounded-lg shadow-sm p-3 border border-gray-200" data-order-number="${orderNumber}">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-1">
                                <span class="font-bold text-gray-900">#${shortNumber}</span>
                                ${statusBadge}
                            </div>
                            <div class="text-xs text-gray-500">
                                <i class="fas fa-clock mr-1"></i>${timeString}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-gray-700">$${parseInt(order.total_amount).toLocaleString()}</div>
                            <div class="text-xs text-gray-400">${order.order_items?.length || 0} 件</div>
                        </div>
                    </div>
                    <div class="border-t pt-2 mb-2">
                        <div class="flex items-center space-x-2">
                            ${order.line_picture_url ?
                                `<img src="${order.line_picture_url}" alt="LINE 頭像" class="w-6 h-6 rounded-full border border-gray-300">` :
                                `<div class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center"><i class="fas fa-user text-gray-400 text-xs"></i></div>`
                            }
                            <div class="flex-1">
                                <div class="text-sm">
                                    ${order.line_display_name ? '<i class="fab fa-line text-green-600 mr-1 text-xs"></i>' : ''}
                                    ${order.customer_name}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-1 text-sm">${itemsHtml}</div>
                    ${reasonHtml}
                </div>
            `;
        }

        // 更新歷史記錄中已存在的訂單
        function updateOrderInHistory(existingCard, order) {
            try {
                console.log('更新歷史記錄中的訂單狀態:', order);

                // 更新狀態徽章
                const statusContainer = existingCard.querySelector('.flex.items-center.space-x-2.mb-1');
                if (statusContainer) {
                    // 移除舊的狀態徽章（所有非訂單編號的span）
                    const oldStatusBadges = statusContainer.querySelectorAll('span');
                    oldStatusBadges.forEach(span => {
                        if (!span.classList.contains('font-bold')) {
                            span.remove();
                        }
                    });

                    // 創建新的狀態徽章
                    let statusBadge = '';
                    if (order.status === 'cancelled') {
                        if (order.cancellation_type === 'rejected') {
                            statusBadge = '<span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium"><i class="fas fa-times mr-1"></i>退單</span>';
                        } else if (order.cancellation_type === 'abandoned') {
                            statusBadge = '<span class="bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded-full font-medium"><i class="fas fa-ban mr-1"></i>棄單</span>';
                        } else if (order.cancellation_type === 'customer_cancelled') {
                            statusBadge = '<span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded-full font-medium"><i class="fas fa-user-times mr-1"></i>客人取消</span>';
                        } else {
                            statusBadge = '<span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded-full font-medium"><i class="fas fa-times mr-1"></i>已取消</span>';
                        }
                    } else if (order.status === 'completed') {
                        statusBadge = '<span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-medium"><i class="fas fa-check mr-1"></i>已完成</span>';
                    }

                    if (statusBadge) {
                        statusContainer.insertAdjacentHTML('beforeend', statusBadge);
                    }
                }

                // 更新取消原因（如果有的話）
                const existingReason = existingCard.querySelector('.mt-2.p-2.bg-red-50');
                if (existingReason) {
                    existingReason.remove();
                }

                const reasonHtml = order.cancellation_reason ? `
                    <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                        <i class="fas fa-info-circle mr-1"></i>
                        ${order.cancellation_reason}
                    </div>
                ` : '';

                if (reasonHtml) {
                    const itemsContainer = existingCard.querySelector('.space-y-1.text-sm');
                    if (itemsContainer) {
                        itemsContainer.insertAdjacentHTML('afterend', reasonHtml);
                    }
                }

                // 添加視覺反饋動畫
                existingCard.style.transition = 'all 0.3s ease';
                existingCard.style.backgroundColor = '#fef3c7'; // yellow-100
                setTimeout(() => {
                    existingCard.style.backgroundColor = '';
                }, 1000);

                console.log('歷史記錄中的訂單狀態已更新');
            } catch (error) {
                console.error('更新歷史記錄中的訂單失敗:', error);
            }
        }

        // 更新歷史記錄頁籤的計數
        function updateHistoryTabCount() {
            console.log('開始更新歷史記錄計數');

            // 方法1: 直接計算所有歷史記錄卡片
            const allHistoryCards = document.querySelectorAll('#history-tab .order-card');
            const totalHistoryOrders = allHistoryCards.length;

            console.log('方法1 - 直接計算歷史訂單卡片:', totalHistoryOrders);

            // 方法2: 計算每個訂單容器中的訂單（匹配原始結構）
            const orderContainers = document.querySelectorAll('#history-tab .border-t .p-2.space-y-2.bg-white');
            let countFromContainers = 0;

            orderContainers.forEach(container => {
                const ordersInContainer = container.querySelectorAll('.order-card').length;
                countFromContainers += ordersInContainer;
                console.log(`容器中有 ${ordersInContainer} 筆訂單`);
            });

            console.log('方法2 - 從容器計算:', countFromContainers);

            // 使用兩種方法的最大值作為最終計數
            const finalCount = Math.max(totalHistoryOrders, countFromContainers);
            console.log('最終歷史記錄計數:', finalCount);

            // 找到歷史記錄頁籤按鈕
            const historyTabBtn = document.querySelector('.tab-btn[data-tab="history"]');
            if (historyTabBtn) {
                const badge = historyTabBtn.querySelector('.px-2');
                if (badge) {
                    const oldCount = parseInt(badge.textContent) || 0;
                    badge.textContent = finalCount;
                    console.log(`歷史記錄頁籤計數已更新: ${oldCount} → ${finalCount}`);

                    // 添加視覺反饋
                    badge.style.transition = 'all 0.3s ease';
                    badge.style.transform = 'scale(1.2)';
                    badge.style.backgroundColor = finalCount > oldCount ? '#dcfce7' : '#fef2f2'; // green-100 or red-100
                    setTimeout(() => {
                        badge.style.transform = 'scale(1)';
                        badge.style.backgroundColor = '';
                    }, 300);
                } else {
                    console.error('找不到歷史記錄頁籤的計數徽章元素');
                    console.log('historyTabBtn HTML:', historyTabBtn.innerHTML);
                }
            } else {
                console.error('找不到歷史記錄頁籤按鈕');
                console.log('所有 tab-btn:', document.querySelectorAll('.tab-btn'));
            }

            // 更新日期組中的計數
            const dateGroups = document.querySelectorAll('[data-history-date]');
            dateGroups.forEach(dateGroup => {
                const countSpan = dateGroup.querySelector('.history-count');
                if (countSpan) {
                    const ordersInGroup = dateGroup.querySelectorAll('.order-card').length;
                    countSpan.textContent = ordersInGroup;
                    console.log(`日期組 ${dateGroup.getAttribute('data-history-date')} 計數更新為: ${ordersInGroup}`);
                }
            });

            console.log('歷史記錄計數更新完成');
        }

        // 更新統計數據
        async function updateStats() {
            console.log('更新統計數據...');
            try {
                const url = `/store/${storeSlug}/manage/stats`;
                console.log('統計 API URL:', url);

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                console.log('統計 API 回應狀態:', response.status);

                if (response.ok) {
                    const stats = await response.json();
                    console.log('收到統計數據:', stats);

                    // 更新今日營收
                    const revenueElement = document.getElementById('today-revenue');
                    if (revenueElement) {
                        if (stats.today_revenue !== undefined) {
                            const newRevenue = '$' + parseInt(stats.today_revenue || 0).toLocaleString();
                            revenueElement.textContent = newRevenue;
                            console.log('更新今日營收:', newRevenue);
                        } else {
                            console.warn('統計資料中沒有 today_revenue 欄位');
                        }

                        // 添加視覺反饋動畫
                        revenueElement.style.transition = 'all 0.3s ease';
                        revenueElement.style.transform = 'scale(1.1)';
                        revenueElement.style.color = '#16a34a'; // green-600
                        setTimeout(() => {
                            revenueElement.style.transform = 'scale(1)';
                            revenueElement.style.color = ''; // 恢復原色
                        }, 300);
                    } else {
                        console.error('找不到營收元素 #today-revenue');
                    }

                    // 也可以更新其他統計數據（如果有需要的話）
                    console.log('統計數據完整更新完成');
                } else {
                    console.error('統計 API 請求失敗:', response.status);
                    const errorText = await response.text();
                    console.error('錯誤內容:', errorText);
                }
            } catch (error) {
                console.error('更新統計數據失敗:', error);
            }
        }

        // 更新待取貨訂單的等待時間顯示
        function updateWaitTimes() {
            document.querySelectorAll('#ready-orders-container .order-card').forEach(card => {
                const badge = card.querySelector('[data-updated-at]');
                if (!badge) return;

                const updatedAtTimestamp = parseInt(badge.dataset.updatedAt);
                const updatedAt = new Date(updatedAtTimestamp * 1000);
                const now = new Date();
                const waitMinutes = Math.floor((now - updatedAt) / 60000);

                // 更新徽章樣式和文字
                let badgeClass = 'bg-green-100 text-green-800';
                let statusText = '正常';
                if (waitMinutes >= 30) {
                    badgeClass = 'bg-red-100 text-red-800';
                    statusText = '請聯繫顧客';
                } else if (waitMinutes >= 15) {
                    badgeClass = 'bg-yellow-100 text-yellow-800';
                    statusText = '等待中';
                }

                // 移除舊的樣式類
                badge.classList.remove('bg-green-100', 'text-green-800', 'bg-yellow-100', 'text-yellow-800', 'bg-red-100', 'text-red-800');
                // 添加新的樣式類
                badge.classList.add(...badgeClass.split(' '));

                // 更新文字
                const textSpan = badge.querySelector('.wait-time-text');
                if (textSpan) {
                    const timeDisplay = formatWaitTime(waitMinutes);
                    textSpan.textContent = `${statusText} (${timeDisplay})`;
                }

                // 更新 data-wait-time
                badge.dataset.waitTime = waitMinutes;
            });
        }

        // ============ 新訂單通知功能 ============

        // 檢查瀏覽器通知權限
        function checkNotificationPermission() {
            if (!("Notification" in window)) {
                console.log('此瀏覽器不支援通知功能');
                return false;
            }

            if (Notification.permission === "granted") {
                notificationPermissionGranted = true;
                return true;
            } else if (Notification.permission === "denied") {
                return false;
            } else {
                // 顯示權限請求提示
                setTimeout(() => {
                    document.getElementById('notification-permission-prompt').classList.remove('hidden');
                }, 5000); // 5秒後顯示
                return false;
            }
        }

        // 請求通知權限
        function requestNotificationPermission() {
            if (!("Notification" in window)) {
                alert('您的瀏覽器不支援推播通知');
                return;
            }

            Notification.requestPermission().then(permission => {
                if (permission === "granted") {
                    notificationPermissionGranted = true;
                    closePermissionPrompt();
                    showBrowserNotification('通知已開啟', '您將即時收到新訂單通知');
                }
            });
        }

        // 關閉權限請求提示
        function closePermissionPrompt() {
            document.getElementById('notification-permission-prompt').classList.add('hidden');
        }

        // 顯示瀏覽器推播通知
        function showBrowserNotification(title, body, icon = null) {
            if (!notificationPermissionGranted) return;

            const notification = new Notification(title, {
                body: body,
                icon: icon || '/images/logo.png',
                badge: '/images/badge.png',
                tag: 'new-order',
                requireInteraction: true, // 需要用戶互動才會關閉
                vibrate: [200, 100, 200], // 震動模式（手機）
            });

            notification.onclick = function() {
                window.focus();
                notification.close();
                viewNewOrders();
            };
        }

        // 顯示頁面通知彈窗
        function showNotification(message, orderCount = 1) {
            const notificationEl = document.getElementById('new-order-notification');
            const messageEl = document.getElementById('notification-message');

            if (orderCount > 1) {
                messageEl.textContent = `您有 ${orderCount} 筆新訂單需要處理！`;
            } else {
                messageEl.textContent = message || '您有新的訂單需要處理';
            }

            notificationEl.classList.remove('hidden');

            // 播放音效
            playNotificationSound();

            // 10秒後自動隱藏
            setTimeout(() => {
                notificationEl.classList.add('hidden');
            }, 10000);
        }

        // 關閉通知
        function closeNotification() {
            document.getElementById('new-order-notification').classList.add('hidden');
        }

        // 查看新訂單（滾動到新訂單區）
        function viewNewOrders() {
            closeNotification();
            const pendingZone = document.querySelector('.pending-zone');
            if (pendingZone) {
                pendingZone.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // 輪詢檢查新訂單
        async function checkForNewOrders() {
            try {
                const response = await fetch(`/store/${storeSlug}/manage/orders/check-new?last_check_time=${encodeURIComponent(lastCheckTime)}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                if (!response.ok) {
                    console.error('檢查新訂單失敗:', response.status);
                    return;
                }

                const data = await response.json();

                // 更新最後檢查時間
                lastCheckTime = data.current_time;

                // 更新統計數據
                if (data.stats) {
                    document.getElementById('pending-count').textContent = data.stats.pending_count;
                    document.getElementById('today-revenue').textContent = '$' + parseInt(data.stats.today_revenue || 0).toLocaleString();
                    updateCounts();
                }

                // 如果有新訂單
                if (data.has_new_orders && data.new_orders.length > 0) {
                    console.log(`收到 ${data.new_orders.length} 筆新訂單`);

                    // 顯示頁面通知
                    showNotification(null, data.new_orders.length);

                    // 顯示瀏覽器推播
                    if (data.new_orders.length === 1) {
                        const order = data.new_orders[0];
                        showBrowserNotification(
                            '🔔 新訂單通知',
                            `訂單 #${order.order_number.substring(order.order_number.length - 6)} - $${order.total_amount}\n${order.customer_name}`
                        );
                    } else {
                        showBrowserNotification(
                            '🔔 新訂單通知',
                            `您有 ${data.new_orders.length} 筆新訂單待處理`
                        );
                    }

                    // 添加新訂單到頁面
                    data.new_orders.forEach(order => {
                        addNewOrderToPage(order);
                    });
                }

            } catch (error) {
                console.error('檢查新訂單時發生錯誤:', error);
            }
        }

        // 添加新訂單到頁面
        function addNewOrderToPage(order) {
            // 檢查訂單狀態，只添加 pending 狀態的訂單
            if (order.status !== 'pending') {
                console.log(`訂單 ${order.order_number} 狀態為 ${order.status}，不添加到新訂單區`);
                return;
            }

            const container = document.getElementById('pending-orders-container');

            // 檢查訂單是否已經存在（避免重複添加）
            const existingCard = container.querySelector(`[data-order-number="${order.order_number}"]`);
            if (existingCard) {
                console.log(`訂單 ${order.order_number} 已存在，跳過添加`);
                return;
            }

            const emptyMessage = container.querySelector('.text-center.py-8');
            if (emptyMessage) {
                emptyMessage.remove();
            }

            const cardHtml = createOrderCard(order, 'pending', 'reject', 'confirm');
            container.insertAdjacentHTML('afterbegin', cardHtml);

            const newCard = container.firstElementChild;
            initSwipeGesture(newCard);

            // 添加閃爍動畫
            newCard.style.animation = 'pulse 1s ease-in-out 3';
            setTimeout(() => {
                newCard.style.animation = '';
            }, 3000);
        }

        // 啟動輪詢
        function startPolling() {
            // 立即執行一次
            checkForNewOrders();

            // 每15秒檢查一次
            pollingInterval = setInterval(checkForNewOrders, 15000);
            console.log('已啟動新訂單輪詢 (每15秒)');
        }

        // 停止輪詢
        function stopPolling() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
                console.log('已停止新訂單輪詢');
            }
        }

        // ============ 初始化 ============

        // 使用事件委托处理历史订单日期组的展开/折叠
        // 这样可以处理动态添加的日期组
        document.addEventListener('click', function(e) {
            // 检查是否点击了日期组按钮或其子元素
            const button = e.target.closest('button[data-toggle="collapse"]');
            if (button && button.closest('#history-tab')) {
                // 从 data-target 属性获取目标元素 ID
                const elementId = button.getAttribute('data-target');

                if (elementId) {
                    console.log('通过事件委托调用 toggleCollapse:', elementId);
                    toggleCollapse(elementId);
                }
            }
        });

        // 初始化所有訂單卡片的滑動手勢
        document.querySelectorAll('.order-card').forEach(card => {
            initSwipeGesture(card);
        });

        // 每分鐘更新一次等待時間
        setInterval(updateWaitTimes, 60000);

        // 檢查通知權限
        checkNotificationPermission();

        // 啟動新訂單輪詢
        startPolling();

        // 頁面離開時停止輪詢
        window.addEventListener('beforeunload', stopPolling);

        // 頁面載入完成
        console.log('訂單管理系統已就緒');
        console.log('歷史訂單展開功能已使用事件委托初始化');
    </script>
</body>
</html>
