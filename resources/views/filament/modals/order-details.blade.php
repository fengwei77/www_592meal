<div class="space-y-4">
    <!-- 訂單基本資訊 -->
    <div>
        <h4 class="font-medium text-gray-900 dark:text-white mb-2">訂單資訊</h4>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-2">
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">訂單編號：</span>
                <span class="font-medium">{{ $order->id }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">用戶：</span>
                <span class="font-medium">
                    @if($order->user)
                        {{ $order->user->name }} ({{ $order->user->email }})
                    @elseif($order->customer)
                        {{ $order->customer->name ?? $order->customer_name ?? '未知用戶' }}
                        ({{ $order->customer->email ?? $order->customer_email ?? '無郵箱' }})
                    @else
                        {{ $order->customer_name ?? '未知用戶' }} ({{ $order->customer_email ?? '無郵箱' }})
                    @endif
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">金額：</span>
                <span class="font-medium">NT$ {{ number_format($order->total_amount) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">狀態：</span>
                <span class="px-2 py-1 text-xs font-medium rounded-full
                    @if($order->status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                    @elseif($order->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                    @elseif($order->status === 'failed') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                    @elseif($order->status === 'cancelled') bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                    @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                    @endif">
                    @if($order->status === 'completed') 已完成
                    @elseif($order->status === 'pending') 待處理
                    @elseif($order->status === 'paid') 已付款
                    @elseif($order->status === 'failed') 失敗
                    @elseif($order->status === 'cancelled') 已取消
                    @else {{ $order->status }}
                    @endif
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">付款方式：</span>
                <span class="font-medium">{{ $order->payment_method ?? '未指定' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">創建時間：</span>
                <span class="font-medium">{{ $order->created_at->format('Y-m-d H:i:s') }}</span>
            </div>
            @if($order->paid_at)
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">付款時間：</span>
                <span class="font-medium">{{ $order->paid_at->format('Y-m-d H:i:s') }}</span>
            </div>
            @endif
        </div>
    </div>

    <!-- 訂單項目 -->
    @if($order->items && $order->items->count() > 0)
    <div>
        <h4 class="font-medium text-gray-900 dark:text-white mb-2">訂單項目</h4>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            @foreach($order->items as $item)
            <div class="flex justify-between items-center mb-2 pb-2 border-b border-gray-200 dark:border-gray-600 last:border-0">
                <div>
                    <div class="font-medium">{{ $item->name ?? '訂閱服務' }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        數量：{{ $item->quantity ?? 1 }} × NT$ {{ number_format($item->price ?? $order->total_amount) }}
                    </div>
                </div>
                <div class="font-medium">
                    NT$ {{ number_format(($item->price ?? $order->total_amount) * ($item->quantity ?? 1)) }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <!-- 如果沒有items，顯示基本的訂閱資訊 -->
    <div>
        <h4 class="font-medium text-gray-900 dark:text-white mb-2">訂單內容</h4>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="flex justify-between">
                <div>
                    <div class="font-medium">592Meal 訂閱服務</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        月數：{{ (int)($order->total_amount / 50) }} 個月
                    </div>
                </div>
                <div class="font-medium">
                    NT$ {{ number_format($order->total_amount) }}
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- 備註 -->
    @if($order->payment_notes)
    <div>
        <h4 class="font-medium text-gray-900 dark:text-white mb-2">備註</h4>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <p class="text-gray-700 dark:text-gray-300">{{ $order->payment_notes }}</p>
        </div>
    </div>
    @endif

    <!-- 相關日誌 -->
    @if($order->paymentLogs && $order->paymentLogs->count() > 0)
    <div>
        <h4 class="font-medium text-gray-900 dark:text-white mb-2">付款日誌</h4>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-2">
            @foreach($order->paymentLogs as $log)
            <div class="text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-300">{{ $log->created_at->format('Y-m-d H:i:s') }}</span>
                    <span class="font-medium">{{ $log->status }}</span>
                </div>
                @if($log->notes)
                <div class="text-gray-600 dark:text-gray-300 mt-1">{{ $log->notes }}</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>