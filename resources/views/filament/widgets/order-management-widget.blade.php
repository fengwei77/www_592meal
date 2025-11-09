<div class="fi-wi-widget">
    <div class="fi-section">
        <div class="fi-section-content-ctn">
            <div class="fi-section-content">
                <div class="space-y-4">
                    <!-- 問題訂單統計 -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">問題訂單統計</h4>
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div>
                                <div class="text-lg font-bold text-yellow-600">{{ $pending_count }}</div>
                                <div class="text-xs text-gray-500">待付款</div>
                            </div>
                            <div>
                                <div class="text-lg font-bold text-red-600">{{ $failed_count }}</div>
                                <div class="text-xs text-gray-500">失敗/取消</div>
                            </div>
                            <div>
                                <div class="text-lg font-bold text-gray-600">{{ $expired_count }}</div>
                                <div class="text-xs text-gray-500">已過期</div>
                            </div>
                        </div>
                        @if($today_problematic_count > 0)
                            <div class="mt-2 text-center">
                                <span class="text-xs text-orange-600">今日新增 {{ $today_problematic_count }} 個問題訂單</span>
                            </div>
                        @endif
                    </div>

                    <!-- 有問題的訂單 -->
                    @if($problematic_orders->count() > 0)
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">需要處理的訂單</h4>
                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                @foreach($problematic_orders as $order)
                                    <div class="border rounded p-2 text-xs">
                                        <div class="flex items-center justify-between mb-1">
                                            <div>
                                                <span class="font-medium">{{ $order->user?->name ?? '未知用戶' }}</span>
                                                <span class="text-gray-500 ml-1">#{{ $order->id }}</span>
                                            </div>
                                            <span class="px-1.5 py-0.5 rounded text-xs text-{{ $this->getStatusColor($order->status) }} bg-{{ $this->getStatusColor($order->status) }}/10">
                                                {{ $this->getStatusText($order->status) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-600">{{ $order->months }}個月 / {{ $this->formatAmount($order->total_amount) }}</span>
                                            <span class="text-gray-500">{{ $this->formatDateTime($order->created_at) }}</span>
                                        </div>
                                        @if($order->notes)
                                            <div class="text-gray-500 mt-1 truncate" title="{{ $order->notes }}">
                                                {{ $order->notes }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- 最新訂單 -->
                    @if($recent_orders->count() > 0)
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">最新訂單</h4>
                            <div class="space-y-1 max-h-40 overflow-y-auto">
                                @foreach($recent_orders->take(3) as $order)
                                    <div class="flex items-center justify-between text-xs py-1 border-b last:border-0">
                                        <div class="flex items-center space-x-2">
                                            <span class="font-medium">{{ $order->user?->name ?? '未知' }}</span>
                                            <span class="px-1 py-0.5 rounded text-xs bg-{{ $this->getStatusColor($order->status) }}/10 text-{{ $this->getStatusColor($order->status) }}">
                                                {{ $this->getStatusText($order->status) }}
                                            </span>
                                        </div>
                                        <div class="text-gray-500">
                                            {{ $this->formatAmount($order->total_amount) }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- 快速操作提示 -->
                    <div class="text-xs text-gray-500 text-center">
                        💡 前往訂單管理頁面可手動處理問題訂單
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>