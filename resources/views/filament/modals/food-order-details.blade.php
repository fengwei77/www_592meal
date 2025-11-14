<div class="space-y-4">
    <!-- 訂單基本資訊 -->
    <div>
        <h4 class="font-medium text-gray-900 dark:text-white mb-2">餐點訂單資訊</h4>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-2">
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">訂單編號：</span>
                <span class="font-medium">{{ $order->order_number ?? $order->id }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">店家：</span>
                <span class="font-medium">
                    {{ $order->store?->name ?? '未指定店家' }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">顧客：</span>
                <span class="font-medium">
                    @if($order->customer)
                        {{ $order->customer->name ?? $order->customer_name ?? '未知顧客' }}
                        ({{ $order->customer->phone ?? $order->customer_phone ?? '無電話' }})
                    @else
                        {{ $order->customer_name ?? '未知顧客' }}
                        ({{ $order->customer_phone ?? '無電話' }})
                    @endif
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">訂單金額：</span>
                <span class="font-medium text-green-600">NT$ {{ number_format($order->total_amount) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">訂單狀態：</span>
                <span class="px-2 py-1 text-xs font-medium rounded-full
                    @if($order->status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                    @elseif($order->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                    @elseif($order->status === 'confirmed') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                    @elseif($order->status === 'preparing') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200
                    @elseif($order->status === 'ready') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                    @elseif($order->status === 'cancelled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                    @elseif($order->status === 'failed') bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                    @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                    @endif">
                    @if($order->status === 'completed') 已完成
                    @elseif($order->status === 'pending') 待處理
                    @elseif($order->status === 'confirmed') 已確認
                    @elseif($order->status === 'preparing') 準備中
                    @elseif($order->status === 'ready') 待取餐
                    @elseif($order->status === 'cancelled') 已取消
                    @elseif($order->status === 'failed') 失敗
                    @else {{ $order->status }}
                    @endif
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">付款狀態：</span>
                <span class="px-2 py-1 text-xs font-medium rounded-full
                    @if($order->payment_status === 'paid') bg-green-100 text-green-800
                    @elseif($order->payment_status === 'pending') bg-yellow-100 text-yellow-800
                    @elseif($order->payment_status === 'failed') bg-red-100 text-red-800
                    @else bg-gray-100 text-gray-800
                    @endif">
                    @if($order->payment_status === 'paid') 已付款
                    @elseif($order->payment_status === 'pending') 待付款
                    @elseif($order->payment_status === 'failed') 付款失敗
                    @elseif($order->payment_status === 'refunded') 已退款
                    @else {{ $order->payment_status }}
                    @endif
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">付款方式：</span>
                <span class="font-medium">
                    @if($order->payment_method === 'cash') 現金
                    @elseif($order->payment_method === 'credit_card') 信用卡
                    @elseif($order->payment_method === 'bank_transfer') 銀行轉帳
                    @elseif($order->payment_method === 'mobile_payment') 行動支付
                    @else {{ $order->payment_method ?? '未指定' }}
                    @endif
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">訂單類型：</span>
                <span class="px-2 py-1 text-xs font-medium rounded-full
                    @if($order->order_type === 'delivery') bg-blue-100 text-blue-800
                    @elseif($order->order_type === 'pickup') bg-green-100 text-green-800
                    @elseif($order->order_type === 'dine_in') bg-orange-100 text-orange-800
                    @else bg-gray-100 text-gray-800
                    @endif">
                    @if($order->order_type === 'delivery') 外送
                    @elseif($order->order_type === 'pickup') 自取
                    @elseif($order->order_type === 'dine_in') 內用
                    @else {{ $order->order_type ?? '未指定' }}
                    @endif
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">下單時間：</span>
                <span class="font-medium">{{ $order->created_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</span>
            </div>
            @if($order->completed_at)
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-300">完成時間：</span>
                <span class="font-medium">{{ $order->completed_at->format('Y-m-d H:i:s') }}</span>
            </div>
            @endif
        </div>
    </div>

    <!-- 訂單項目 -->
    @if($order->items && $order->items->count() > 0)
    <div>
        <h4 class="font-medium text-gray-900 dark:text-white mb-2">餐點項目</h4>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            @foreach($order->items as $item)
            <div class="flex justify-between items-center mb-2 pb-2 border-b border-gray-200 dark:border-gray-600 last:border-0">
                <div>
                    <div class="font-medium">{{ $item->menuItem->name ?? $item->getItemNameAttribute() ?? '未知餐點' }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        數量：{{ $item->quantity ?? 1 }} × NT$ {{ number_format($item->unit_price ?? 0) }}
                        @if($item->special_instructions)
                        <br>
                        <span class="text-xs">備註：{{ $item->special_instructions }}</span>
                        @endif
                    </div>
                </div>
                <div class="font-medium">
                    NT$ {{ number_format($item->total_price ?? ($item->unit_price * $item->quantity) ?? 0) }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div>
        <h4 class="font-medium text-gray-900 dark:text-white mb-2">餐點內容</h4>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="text-gray-600 dark:text-gray-300">
                <div>
                    此訂單沒有詳細的餐點項目資料
                </div>
                <div class="text-sm mt-2">
                    可能原因：
                    <ul class="list-disc list-inside mt-1 text-xs">
                        <li>此為系統測試訂單</li>
                        <li>訂單項目資料可能已遺失</li>
                        <li>可能是手動創建的訂單記錄</li>
                    </ul>
                </div>
                <div class="mt-3 p-3 bg-yellow-100 dark:bg-yellow-900 rounded border border-yellow-300 dark:border-yellow-700">
                    <strong>訂單總金額：</strong> NT$ {{ number_format($order->total_amount) }}
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- 配送/取餐資訊 -->
    @if($order->order_type === 'delivery' || $order->delivery_address)
    <div>
        <h4 class="font-medium text-gray-900 dark:text-white mb-2">配送地址</h4>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <p class="text-gray-700 dark:text-gray-300">
                {{ $order->delivery_address ?? '未指定配送地址' }}
            </p>
            @if($order->delivery_notes)
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                備註：{{ $order->delivery_notes }}
            </p>
            @endif
        </div>
    </div>
    @endif

    <!-- 取餐時間 -->
    @if($order->pickup_time)
    <div>
        <h4 class="font-medium text-gray-900 dark:text-white mb-2">取餐時間</h4>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <p class="text-gray-700 dark:text-gray-300">
                {{ $order->pickup_time->format('Y-m-d H:i') }}
            </p>
        </div>
    </div>
    @endif

    <!-- 訂單備註 -->
    @if($order->notes)
    <div>
        <h4 class="font-medium text-gray-900 dark:text-white mb-2">訂單備註</h4>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <p class="text-gray-700 dark:text-gray-300">{{ $order->notes }}</p>
        </div>
    </div>
    @endif

    <!-- 付款備註 -->
    @if($order->payment_notes)
    <div>
        <h4 class="font-medium text-gray-900 dark:text-white mb-2">付款備註</h4>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <p class="text-gray-700 dark:text-gray-300">{{ $order->payment_notes }}</p>
        </div>
    </div>
    @endif

    <!-- 取消原因 -->
    @if($order->status === 'cancelled' && $order->cancellation_reason)
    <div>
        <h4 class="font-medium text-gray-900 dark:text-white mb-2">取消原因</h4>
        <div class="bg-red-50 dark:bg-red-900 rounded-lg p-4">
            <p class="text-red-700 dark:text-red-300">{{ $order->cancellation_reason }}</p>
        </div>
    </div>
    @endif
</div>