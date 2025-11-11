<x-filament-panels::page>
    <div class="fi-page-content space-y-6">
        <!-- 總體統計卡片 -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <!-- 總註冊人數 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">總註冊人數</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($overallStats['total_users']) }}</p>
                </div>
            </div>

            <!-- 有訂閱人數 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">有訂閱人數</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($overallStats['subscribed_users']) }}</p>
                </div>
            </div>

            <!-- 總收入金額 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">總收入金額</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">NT$ {{ number_format($overallStats['total_revenue']) }}</p>
                </div>
            </div>

            <!-- 總訂單數量 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">總訂單數量</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($overallStats['total_orders']) }}</p>
                </div>
            </div>
        </div>

        <!-- 訂單狀態統計 -->
        <x-filament::section>
            <x-slot name="heading">
                訂單狀態統計
            </x-slot>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ number_format($orderStats['completed']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">已完成</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600">{{ number_format($orderStats['pending']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">待處理</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-red-600">{{ number_format($orderStats['failed']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">失敗</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-600">{{ number_format($orderStats['cancelled']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">已取消</div>
                </div>
            </div>
        </x-filament::section>

        <!-- 今日統計 -->
        <x-filament::section>
            <x-slot name="heading">
                今日統計
            </x-slot>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ number_format($todayStats['new_registrations']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">新註冊用戶</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ number_format($todayStats['website_logins']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">網站登入成功</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ number_format($todayStats['admin_logins_success']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">後台登入成功</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-red-600">{{ number_format($todayStats['admin_logins_failed']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">後台登入失敗</div>
                </div>
            </div>
        </x-filament::section>

        <!-- 訂閱統計 -->
        <x-filament::section>
            <x-slot name="heading">
                訂閱統計
            </x-slot>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ number_format($subscriptionStats['active_subscriptions']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">活躍訂閱</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-600">{{ number_format($subscriptionStats['expired_subscriptions']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">已過期訂閱</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600">{{ number_format($subscriptionStats['expiring_soon_7d']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">7天內到期</div>
                </div>
            </div>
        </x-filament::section>

        <!-- 收入統計 -->
        <x-filament::section>
            <x-slot name="heading">
                收入統計
            </x-slot>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">NT$ {{ number_format($revenueStats['this_month_revenue']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">本月收入</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-600">NT$ {{ number_format($revenueStats['last_month_revenue']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">上月收入</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">NT$ {{ number_format($revenueStats['total_revenue']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">總收入</div>
                </div>
            </div>
        </x-filament::section>

        <!-- 每日註冊趨勢（最近7天） -->
        <x-filament::section>
            <x-slot name="heading">
                每日註冊趨勢（最近7天）
            </x-slot>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">日期</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">新註冊</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">網站登入</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">後台登入成功</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">後台登入失敗</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($dailyStats as $stat)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $stat['date'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $stat['new_registrations'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $stat['website_logins'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $stat['admin_logins_success'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $stat['admin_logins_failed'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <!-- 訂單管理表格 -->
        <x-filament::section>
            <x-slot name="heading">
                訂單管理
            </x-slot>
            <x-slot name="description">
                管理所有訂單，可手動標記付款狀態
            </x-slot>
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>