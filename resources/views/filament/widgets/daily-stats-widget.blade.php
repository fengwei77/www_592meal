<div class="fi-wi-widget">
    <div class="fi-section">
        <div class="fi-section-content-ctn">
            <div class="fi-section-content">
                <div class="space-y-4">
                    <!-- 今日統計 -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">今日統計</h4>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="text-center">
                                <div class="text-lg font-bold text-blue-600">{{ $today_registrations }}</div>
                                <div class="text-xs text-gray-500">註冊</div>
                                @if($yesterday_registrations > 0)
                                    <div class="text-xs {{ $registration_trend === 'up' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $registration_trend === 'up' ? '↑' : '↓' }} {{ abs($registration_growth_rate) }}%
                                    </div>
                                @endif
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-purple-600">{{ $today_orders }}</div>
                                <div class="text-xs text-gray-500">訂單</div>
                                @if($yesterday_orders > 0)
                                    <div class="text-xs {{ $order_trend === 'up' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $order_trend === 'up' ? '↑' : '↓' }} {{ abs($order_growth_rate) }}%
                                    </div>
                                @endif
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-green-600">{{ $formatted_today_revenue }}</div>
                                <div class="text-xs text-gray-500">收入</div>
                                @if($yesterday_revenue > 0)
                                    <div class="text-xs {{ $revenue_trend === 'up' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $revenue_trend === 'up' ? '↑' : '↓' }} {{ abs($revenue_growth_rate) }}%
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- 本週統計 -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">本週統計</h4>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="text-center">
                                <div class="text-lg font-bold text-indigo-600">{{ $week_registrations }}</div>
                                <div class="text-xs text-gray-500">註冊</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-orange-600">{{ $week_orders }}</div>
                                <div class="text-xs text-gray-500">訂單</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-teal-600">{{ $formatted_week_revenue }}</div>
                                <div class="text-xs text-gray-500">收入</div>
                            </div>
                        </div>
                    </div>

                    <!-- 本月統計 -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">本月統計</h4>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="text-center">
                                <div class="text-lg font-bold text-gray-700">{{ $month_registrations }}</div>
                                <div class="text-xs text-gray-500">註冊</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-gray-700">{{ $month_orders }}</div>
                                <div class="text-xs text-gray-500">訂單</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-gray-700">{{ $formatted_month_revenue }}</div>
                                <div class="text-xs text-gray-500">收入</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>