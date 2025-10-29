<div class="order-card rounded-lg shadow-sm p-4 relative"
     data-order-number="{{ $order->order_number }}"
     data-left-action="{{ $leftSwipeAction ?? '' }}"
     data-right-action="{{ $rightSwipeAction ?? '' }}">

    <!-- 滑動指示器 -->
    @if(isset($leftSwipeAction))
        <div class="swipe-indicator left">
            @if($leftSwipeAction === 'reject')
                <i class="fas fa-times-circle"></i>
            @elseif($leftSwipeAction === 'abandon')
                <i class="fas fa-ban"></i>
            @endif
        </div>
    @endif

    @if(isset($rightSwipeAction))
        <div class="swipe-indicator right">
            @if($rightSwipeAction === 'confirm')
                <i class="fas fa-check-circle"></i>
            @elseif($rightSwipeAction === 'ready')
                <i class="fas fa-bell"></i>
            @elseif($rightSwipeAction === 'complete')
                <i class="fas fa-check-double"></i>
            @endif
        </div>
    @endif

    <!-- 訂單標題 -->
    <div class="flex items-start justify-between mb-3">
        <div class="flex-1">
            <div class="flex items-center space-x-2 mb-1">
                <span class="text-lg font-bold text-gray-900">#{{ substr($order->order_number, -6) }}</span>
                @if($zone === 'pending')
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-0.5 rounded-full font-medium new-order-pulse">
                        新訂單
                    </span>
                @elseif($zone === 'confirmed')
                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full font-medium">
                        <i class="fas fa-utensils mr-1"></i>製作中
                    </span>
                @elseif($zone === 'ready')
                    @php
                        $waitMinutes = $order->updated_at->diffInMinutes(now());
                        $badgeClass = 'bg-green-100 text-green-800';
                        $statusText = '正常';
                        if ($waitMinutes >= 30) {
                            $badgeClass = 'bg-red-100 text-red-800';
                            $statusText = '請聯繫顧客';
                        } elseif ($waitMinutes >= 15) {
                            $badgeClass = 'bg-yellow-100 text-yellow-800';
                            $statusText = '等待中';
                        }

                        // 格式化時間顯示
                        $hours = floor($waitMinutes / 60);
                        $mins = $waitMinutes % 60;
                        $timeDisplay = $hours > 0 ? "{$hours}小時{$mins}分" : "{$mins}分";
                    @endphp
                    <span class="{{ $badgeClass }} text-xs px-2 py-0.5 rounded-full font-medium"
                          data-wait-time="{{ $waitMinutes }}"
                          data-updated-at="{{ $order->updated_at->timestamp }}">
                        <i class="fas fa-hourglass-half mr-1"></i>
                        <span class="wait-time-text">{{ $statusText }} ({{ $timeDisplay }})</span>
                    </span>
                @endif
            </div>
            <div class="text-xs text-gray-500">
                <i class="fas fa-clock mr-1"></i>
                {{ $order->created_at->format('H:i') }}
                <span class="mx-1">·</span>
                {{ $order->created_at->diffForHumans() }}
            </div>
        </div>
        <div class="text-right">
            <div class="text-2xl font-bold text-green-600">
                ${{ number_format($order->total_amount, 0) }}
            </div>
            <div class="text-xs text-gray-500">
                {{ $order->total_quantity }} 件
            </div>
        </div>
    </div>

    <!-- 顧客資訊 -->
    <div class="border-t pt-3 mb-3">
        <div class="flex items-center space-x-2">
            @if($order->line_picture_url)
                <img src="{{ $order->line_picture_url }}" alt="LINE 頭像"
                     class="w-8 h-8 rounded-full border border-green-500">
            @else
                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
                    <i class="fas fa-user text-gray-400 text-xs"></i>
                </div>
            @endif
            <div class="flex-1">
                <div class="font-medium text-sm">
                    @if($order->line_display_name)
                        <i class="fab fa-line text-green-600 mr-1 text-xs"></i>
                    @endif
                    {{ $order->customer_name }}
                </div>
                @if($order->customer_phone)
                    <div class="text-xs text-gray-500">
                        <i class="fas fa-phone mr-1"></i>{{ $order->customer_phone }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- 訂單商品 -->
    <div class="border-t pt-3 space-y-2">
        @foreach($order->orderItems as $item)
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center space-x-2 flex-1">
                    <span class="w-6 h-6 bg-blue-100 text-blue-600 rounded flex items-center justify-center text-xs font-bold">
                        {{ $item->quantity }}
                    </span>
                    <span class="text-gray-700">{{ $item->menuItem->name ?? '商品已下架' }}</span>
                </div>
                <span class="text-gray-600">${{ number_format($item->total_price, 0) }}</span>
            </div>
        @endforeach
    </div>

    <!-- 備註 -->
    @if($order->notes)
        <div class="border-t mt-3 pt-3">
            <div class="flex items-start space-x-2">
                <i class="fas fa-comment-dots text-orange-500 mt-0.5"></i>
                <div class="flex-1">
                    <div class="text-xs font-medium text-gray-700 mb-1">顧客備註</div>
                    <div class="text-sm text-gray-600 bg-orange-50 rounded p-2">
                        {{ $order->notes }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- 操作按鈕 (所有裝置都顯示) -->
    <div class="flex border-t mt-3 pt-3 space-x-2">
        @if(isset($leftSwipeAction))
            @if($leftSwipeAction === 'reject')
                <button onclick="handleDesktopAction('{{ $order->order_number }}', 'reject')"
                        class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                    <i class="fas fa-times-circle mr-2"></i>退單
                </button>
            @elseif($leftSwipeAction === 'abandon')
                <button onclick="handleDesktopAction('{{ $order->order_number }}', 'abandon')"
                        class="flex-1 px-4 py-2 text-white rounded-lg font-medium transition-colors"
                        style="background-color: #546e7a !important;"
                        onmouseover="this.style.backgroundColor='#455a64 !important'"
                        onmouseout="this.style.backgroundColor='#546e7a !important'">
                    <i class="fas fa-ban mr-2"></i>棄單
                </button>
            @endif
        @endif

        @if(isset($rightSwipeAction))
            @if($rightSwipeAction === 'confirm')
                <button onclick="handleDesktopAction('{{ $order->order_number }}', 'confirm')"
                        class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors">
                    <i class="fas fa-check-circle mr-2"></i>接單
                </button>
            @elseif($rightSwipeAction === 'ready')
                <button onclick="handleDesktopAction('{{ $order->order_number }}', 'ready')"
                        class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                    <i class="fas fa-bell mr-2"></i>待取貨
                </button>
            @elseif($rightSwipeAction === 'complete')
                <button onclick="handleDesktopAction('{{ $order->order_number }}', 'complete')"
                        class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors">
                    <i class="fas fa-check-double mr-2"></i>完成
                </button>
            @endif
        @endif
    </div>
</div>
