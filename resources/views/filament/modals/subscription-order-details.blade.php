<div class="space-y-4">
    <!-- 訂閱訂單基本資訊 -->
    <div>
        <h4 class="font-medium text-gray-900 dark:text-white mb-2">訂閱訂單資訊</h4>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-2">
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">訂單編號：</span>
                <span class="font-medium">{{ $order->order_number ?? $order->id }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">用戶：</span>
                <span class="font-medium">
                    @if($order->user)
                        {{ $order->user->name }} ({{ $order->user->email }})
                    @else
                        未知用戶
                    @endif
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">訂閱月數：</span>
                <span class="font-medium">{{ $order->months }} 個月</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">單價：</span>
                <span class="font-medium">NT$ {{ number_format($order->unit_price) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">總金額：</span>
                <span class="font-medium text-green-600">NT$ {{ number_format($order->total_amount) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">訂單狀態：</span>
                <span class="px-2 py-1 text-xs font-medium rounded-full
                    @if($order->status === 'paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                    @elseif($order->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                    @elseif($order->status === 'failed') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                    @elseif($order->status === 'cancelled') bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                    @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                    @endif">
                    @if($order->status === 'paid') 已付款
                    @elseif($order->status === 'pending') 待付款
                    @elseif($order->status === 'failed') 失敗
                    @elseif($order->status === 'cancelled') 已取消
                    @else {{ $order->status }}
                    @endif
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">付款方式：</span>
                <span class="font-medium">
                    @if($order->payment_type === 'credit_card') 信用卡
                    @elseif($order->payment_type === 'bank_transfer') 銀行轉帳
                    @elseif($order->payment_type === 'line_pay') LINE Pay
                    @elseif($order->payment_type === 'manual') 手動
                    @else {{ $order->payment_type ?? '未指定' }}
                    @endif
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">創建時間：</span>
                <span class="font-medium">{{ $order->created_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</span>
            </div>
            @if($order->paid_at)
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">付款時間：</span>
                <span class="font-medium">{{ $order->paid_at->format('Y-m-d H:i:s') }}</span>
            </div>
            @endif
        </div>
    </div>

    <!-- 訂閱期間 -->
    <div>
        <h4 class="font-medium text-gray-900 dark:text-white mb-2">訂閱期間</h4>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-2">
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">開始日期：</span>
                <span class="font-medium">
                    {{ $order->subscription_start_date?->format('Y-m-d') ?? '未設定' }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">結束日期：</span>
                <span class="font-medium">
                    @if($order->subscription_end_date)
                        {{ $order->subscription_end_date->format('Y-m-d') }}
                        @php
                            $isExpired = \Carbon\Carbon::parse($order->subscription_end_date)->isPast();
                        @endphp
                        @if($isExpired)
                            <span class="ml-2 text-xs text-red-600">(已過期)</span>
                        @endif
                    @else
                        未設定
                    @endif
                </span>
            </div>
            @if($order->subscription_start_date && $order->subscription_end_date)
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">訂閱天數：</span>
                <span class="font-medium">
                    {{ \Carbon\Carbon::parse($order->subscription_start_date)->diffInDays($order->subscription_end_date) }} 天
                </span>
            </div>
            @endif
        </div>
    </div>

    <!-- 訂閱內容 -->
    <div>
        <h4 class="font-medium text-gray-900 dark:text-white mb-2">訂閱內容</h4>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="flex justify-between items-center">
                <div>
                    <div class="font-medium">592Meal 老闆訂閱服務</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        包含：店家管理、訂單處理、統計分析等功能
                    </div>
                </div>
                <div class="text-right">
                    <div class="font-medium">{{ $order->months }} 個月</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        NT$ {{ number_format($order->unit_price) }}/月
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 備註 -->
    @if($order->notes)
    <div>
        <h4 class="font-medium text-gray-900 dark:text-white mb-2">備註</h4>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <p class="text-gray-700 dark:text-gray-300">{{ $order->notes }}</p>
        </div>
    </div>
    @endif

    <!-- 相關付款日誌 -->
    @if($order->paymentLogs && $order->paymentLogs->count() > 0)
    <div>
        <h4 class="font-medium text-gray-900 dark:text-white mb-2">付款日誌</h4>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-2">
            @foreach($order->paymentLogs as $log)
            <div class="text-sm border-b border-gray-200 dark:border-gray-600 pb-2 last:border-0">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-300">{{ $log->created_at->format('Y-m-d H:i:s') }}</span>
                    <span class="font-medium px-2 py-1 text-xs rounded-full
                        @if($log->status === 'paid') bg-green-100 text-green-800
                        @elseif($log->status === 'pending') bg-yellow-100 text-yellow-800
                        @elseif($log->status === 'extension') bg-blue-100 text-blue-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        @if($log->status === 'paid') 已付款
                        @elseif($log->status === 'pending') 待付款
                        @elseif($log->status === 'extension') 延期
                        @else {{ $log->status }}
                        @endif
                    </span>
                </div>
                @if($log->amount > 0)
                <div class="text-gray-600 dark:text-gray-300 mt-1">
                    金額：NT$ {{ number_format($log->amount) }}
                </div>
                @endif
                @if($log->months > 0)
                <div class="text-gray-600 dark:text-gray-300 mt-1">
                    月數：{{ $log->months }} 個月
                </div>
                @endif
                @if($log->expires_at)
                <div class="text-gray-600 dark:text-gray-300 mt-1">
                    到期日：{{ $log->expires_at->format('Y-m-d') }}
                </div>
                @endif
                @if($log->notes)
                <div class="text-gray-600 dark:text-gray-300 mt-1">
                    備註：{{ $log->notes }}
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- 用戶資訊 -->
    @if($order->user)
    <div>
        <h4 class="font-medium text-gray-900 dark:text-white mb-2">用戶資訊</h4>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-2">
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">姓名：</span>
                <span class="font-medium">{{ $order->user->name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">Email：</span>
                <span class="font-medium">{{ $order->user->email }}</span>
            </div>
            @if($order->user->subscription_ends_at)
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">目前訂閱到期日：</span>
                <span class="font-medium">
                    {{ $order->user->subscription_ends_at->format('Y-m-d') }}
                    @php
                        $remainingDays = \Carbon\Carbon::now()->diffInDays($order->user->subscription_ends_at, false);
                    @endphp
                    @if($remainingDays > 0)
                        <span class="ml-2 text-xs text-green-600">(剩餘 {{ $remainingDays }} 天)</span>
                    @else
                        <span class="ml-2 text-xs text-red-600">(已過期)</span>
                    @endif
                </span>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>