<div class="fi-wi-widget">
    <div class="fi-section">
        <div class="fi-section-content-ctn">
            <div class="fi-section-content">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">總收入</span>
                            <span class="text-lg font-semibold text-green-600">{{ $formatted_total_revenue }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">本月收入</span>
                            <span class="text-lg font-semibold text-blue-600">{{ $formatted_monthly_revenue }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">今日收入</span>
                            <span class="text-lg font-semibold text-purple-600">{{ $formatted_daily_revenue }}</span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">平均訂單金額</span>
                            <span class="text-lg font-semibold text-orange-600">{{ $formatted_average_order_value }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">預計下月收入</span>
                            <span class="text-lg font-semibold text-teal-600">{{ $formatted_projected_monthly_revenue }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">本月訂單數</span>
                            <span class="text-lg font-semibold text-indigo-600">{{ $monthly_orders }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">今日訂單數</span>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-medium text-gray-700">{{ $daily_orders }} 筆</span>
                            @if($daily_orders > 0)
                                <span class="text-xs text-green-600">
                                    (約 {{ $daily_revenue > 0 ? $formatted_daily_revenue : 'NT$ 0' }})
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>