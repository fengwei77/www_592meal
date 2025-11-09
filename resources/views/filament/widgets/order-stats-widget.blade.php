<div class="fi-wi-widget">
    <div class="fi-section">
        <div class="fi-section-content-ctn">
            <div class="fi-section-content">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">總訂單數</span>
                            <span class="text-lg font-semibold">{{ $total_orders }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">已完成</span>
                            <span class="text-lg font-semibold text-green-600">{{ $completed_orders }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">待付款</span>
                            <span class="text-lg font-semibold text-{{ $pending_color }}">{{ $pending_orders }}</span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">失敗/取消</span>
                            <span class="text-lg font-semibold text-red-600">{{ $failed_orders }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">已過期</span>
                            <span class="text-lg font-semibold text-gray-600">{{ $expired_orders }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">今日訂單</span>
                            <span class="text-lg font-semibold text-blue-600">{{ $today_orders }}</span>
                        </div>
                    </div>
                </div>

                @if($total_orders > 0)
                    <div class="mt-4 pt-4 border-t space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">總完成率</span>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm font-medium text-{{ $completion_color }}">{{ $completion_rate }}%</span>
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-{{ $completion_color }} h-2 rounded-full transition-all duration-300"
                                         style="width: {{ $completion_rate }}%"></div>
                                </div>
                            </div>
                        </div>

                        @if($today_orders > 0)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">今日完成率</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-{{ $completion_rate >= 80 ? 'success' : ($today_completion_rate >= 60 ? 'warning' : 'danger') }}">
                                        {{ $today_completion_rate }}%
                                    </span>
                                    <div class="w-16 bg-gray-200 rounded-full h-2">
                                        <div class="bg-{{ $today_completion_rate >= 80 ? 'success' : ($today_completion_rate >= 60 ? 'warning' : 'danger') }} h-2 rounded-full transition-all duration-300"
                                             style="width: {{ $today_completion_rate }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>