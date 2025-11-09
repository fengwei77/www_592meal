<div class="fi-wi-widget">
    <div class="fi-section">
        <div class="fi-section-content-ctn">
            <div class="fi-section-content">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">總註冊人數</span>
                            <span class="text-lg font-semibold text-blue-600">{{ $total_users }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">付費訂閱</span>
                            <span class="text-lg font-semibold text-green-600">{{ $paid_subscribers }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">試用期</span>
                            <span class="text-lg font-semibold text-orange-600">{{ $trial_users }}</span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">今日註冊</span>
                            <span class="text-lg font-semibold text-purple-600">{{ $today_registrations }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">總訂閱用戶</span>
                            <span class="text-lg font-semibold text-indigo-600">{{ $total_with_subscription }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">轉換率</span>
                            <span class="text-lg font-semibold text-teal-600">{{ $conversion_rate }}%</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">活躍用戶比例</span>
                        <div class="flex items-center space-x-2">
                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                @if($total_users > 0)
                                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                         style="width: {{ round(($total_with_subscription / $total_users) * 100) }}%"></div>
                                @endif
                            </div>
                            <span class="text-xs font-medium text-gray-700">
                                @if($total_users > 0)
                                    {{ round(($total_with_subscription / $total_users) * 100) }}%
                                @else
                                    0%
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>