<div class="order-card rounded-lg shadow-sm p-4">
    <!-- 訂單標題 -->
    <div class="flex items-start justify-between mb-3">
        <div class="flex-1">
            <div class="flex items-center space-x-2 mb-1">
                <span class="text-lg font-bold text-gray-900">#{{ substr($order->order_number, -6) }}</span>
                @if($order->status === 'completed')
                    <span class="bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded-full font-medium">
                        <i class="fas fa-check mr-1"></i>已完成
                    </span>
                @elseif($order->isRejected())
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-0.5 rounded-full font-medium">
                        <i class="fas fa-times mr-1"></i>退單
                    </span>
                @elseif($order->isAbandoned())
                    <span class="bg-orange-100 text-orange-800 text-xs px-2 py-0.5 rounded-full font-medium">
                        <i class="fas fa-ban mr-1"></i>棄單
                    </span>
                @endif
            </div>
            <div class="text-xs text-gray-500">
                <i class="fas fa-clock mr-1"></i>
                @if($order->status === 'completed' && $order->completed_at)
                    完成：{{ $order->completed_at->format('m/d H:i') }}
                @elseif($order->status === 'cancelled' && $order->cancelled_at)
                    取消：{{ $order->cancelled_at->format('m/d H:i') }}
                @else
                    {{ $order->created_at->format('m/d H:i') }}
                @endif
            </div>
        </div>
        <div class="text-right">
            <div class="text-xl font-bold text-gray-700">
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
                     class="w-8 h-8 rounded-full border border-gray-300">
            @else
                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
                    <i class="fas fa-user text-gray-400 text-xs"></i>
                </div>
            @endif
            <div class="flex-1">
                <div class="font-medium text-sm text-gray-700">
                    {{ $order->customer_name }}
                </div>
                @if($order->customer_phone)
                    <div class="text-xs text-gray-500">
                        {{ $order->customer_phone }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- 訂單商品摘要 -->
    <div class="border-t pt-3">
        <div class="text-xs text-gray-600">
            @foreach($order->orderItems->take(2) as $item)
                <div class="mb-1">
                    <span class="font-medium">{{ $item->quantity }}x</span>
                    {{ $item->menuItem->name ?? '商品已下架' }}
                </div>
            @endforeach
            @if($order->orderItems->count() > 2)
                <div class="text-gray-500">還有 {{ $order->orderItems->count() - 2 }} 項...</div>
            @endif
        </div>
    </div>

    <!-- 取消原因 -->
    @if($order->status === 'cancelled' && $order->cancellation_reason)
        <div class="border-t mt-3 pt-3">
            <div class="text-xs font-medium text-gray-700 mb-1">
                @if($order->isRejected())
                    退單原因
                @elseif($order->isAbandoned())
                    棄單原因
                @else
                    取消原因
                @endif
            </div>
            <div class="text-sm text-gray-600 bg-gray-50 rounded p-2">
                {{ $order->cancellation_reason }}
            </div>
        </div>
    @endif
</div>
