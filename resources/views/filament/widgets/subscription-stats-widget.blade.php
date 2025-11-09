<div class="fi-wi-widget">
    <div class="fi-section">
        <div class="fi-section-content-ctn">
            <div class="fi-section-content">
                <div class="grid grid-cols-1 gap-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">總訂單</span>
                        <span class="text-lg font-semibold">{{ $total_orders }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">已付款</span>
                        <span class="text-lg font-semibold text-green-600">{{ $paid_orders }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">待付款</span>
                        <span class="text-lg font-semibold text-{{ $pending_orders_color }}">{{ $pending_orders }}</span>
                    </div>
                </div>

                @if($total_orders > 0)
                    <div class="mt-4 pt-4 border-t">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">完成率</span>
                            <span class="text-sm font-medium text-{{ $completion_rate_color }}">{{ $completion_rate }}%</span>
                        </div>
                        <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-{{ $completion_rate_color }} h-2 rounded-full transition-all duration-300" style="width: {{ $completion_rate }}%"></div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>