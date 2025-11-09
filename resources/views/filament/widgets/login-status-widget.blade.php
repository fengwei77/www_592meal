<div class="fi-wi-widget">
    <div class="fi-section">
        <div class="fi-section-content-ctn">
            <div class="fi-section-content">
                <div class="space-y-4">
                    <!-- 活躍用戶統計 -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">活躍用戶統計</h4>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-600">今日活躍</span>
                                <span class="text-sm font-medium text-blue-600">{{ $today_active_users }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-600">本週活躍</span>
                                <span class="text-sm font-medium text-purple-600">{{ $week_active_users }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-600">本月活躍</span>
                                <span class="text-sm font-medium text-green-600">{{ $month_active_users }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-600">7天內活躍</span>
                                <span class="text-sm font-medium text-orange-600">{{ $active_users }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- 活躍率 -->
                    @if($total_users > 0)
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">活躍率</h4>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-600">日活躍率</span>
                                    <span class="text-xs font-medium">{{ $daily_active_rate }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-300"
                                         style="width: {{ $daily_active_rate }}%"></div>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-600">週活躍率</span>
                                    <span class="text-xs font-medium">{{ $weekly_active_rate }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="bg-purple-600 h-1.5 rounded-full transition-all duration-300"
                                         style="width: {{ $weekly_active_rate }}%"></div>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-600">月活躍率</span>
                                    <span class="text-xs font-medium">{{ $monthly_active_rate }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="bg-green-600 h-1.5 rounded-full transition-all duration-300"
                                         style="width: {{ $monthly_active_rate }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- 最近登入 -->
                    @if($recent_logins->count() > 0)
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">最近登入</h4>
                            <div class="space-y-1">
                                @foreach($recent_logins as $login)
                                    <div class="flex items-center justify-between text-xs">
                                        <div class="flex-1 truncate">
                                            <span class="font-medium text-gray-700">{{ $login->name }}</span>
                                        </div>
                                        <div class="text-gray-500">
                                            {{ $login->last_login_at ? $login->last_login_at->format('m/d H:i') : '未知' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>