<x-filament-panels::page>
    <div class="fi-page-content space-y-6">
        <!-- 訂閱統計卡片 -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <!-- 總訂閱用戶數 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">總訂閱用戶數</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($subscriptionStats['total_subscribers'] ?? 0) }}</p>
                </div>
            </div>

            <!-- 活躍訂閱數 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">活躍訂閱數</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($subscriptionStats['active_subscriptions'] ?? 0) }}</p>
                </div>
            </div>

            <!-- 過期訂閱數 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">過期訂閱數</p>
                    <p class="text-2xl font-bold text-red-600">{{ number_format($subscriptionStats['expired_subscriptions'] ?? 0) }}</p>
                </div>
            </div>

            <!-- 試用期用戶數 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">試用期用戶數</p>
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($subscriptionStats['trial_users'] ?? 0) }}</p>
                </div>
            </div>

            <!-- 月收入 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">本月收入</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">NT$ {{ number_format($subscriptionStats['monthly_revenue'] ?? 0) }}</p>
                </div>
            </div>

            <!-- 總收入 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">總收入</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">NT$ {{ number_format($subscriptionStats['total_revenue'] ?? 0) }}</p>
                </div>
            </div>

            <!-- 即將到期 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">即將到期 (7天內)</p>
                    <p class="text-2xl font-bold text-orange-600">{{ number_format($subscriptionStats['expiring_soon'] ?? 0) }}</p>
                </div>
            </div>

            <!-- 平均訂閱月數 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">平均訂閱月數</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($subscriptionStats['avg_months'] ?? 0, 1) }}</p>
                </div>
            </div>
        </div>

        <!-- 活躍訂閱列表 -->
        <x-filament::section>
            <x-slot name="heading">
                活躍訂閱 (最新10筆)
            </x-slot>
            @if($activeSubscriptions->count() > 0)
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">用戶</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">開始時間</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">到期時間</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">剩餘天數</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">狀態</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($activeSubscriptions as $subscription)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $subscription->name ?? 'N/A' }}</div>
                                        <div class="text-sm text-gray-500">{{ $subscription->email ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        N/A
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $subscription->subscription_ends_at?->format('Y-m-d') ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $remainingDays = $subscription->subscription_ends_at ?
                                                \Carbon\Carbon::now()->diffInDays($subscription->subscription_ends_at, false) : 0;
                                        @endphp
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            @if($remainingDays > 30) bg-green-100 text-green-800
                                            @elseif($remainingDays > 7) bg-yellow-100 text-yellow-800
                                            @else bg-red-100 text-red-800
                                            @endif">
                                            {{ $remainingDays > 0 ? $remainingDays . ' 天' : '已過期' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            活躍
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    目前沒有活躍的訂閱
                </div>
            @endif
        </x-filament::section>

        <!-- 即將到期訂閱 -->
        <x-filament::section>
            <x-slot name="heading">
                <span class="text-orange-600">⚠️ 即將到期訂閱 (7天內)</span>
            </x-slot>
            @if($expiringSoonSubscriptions->count() > 0)
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-orange-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">用戶</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">到期時間</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">剩餘天數</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">累計訂閱月數</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">累計金額</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($expiringSoonSubscriptions as $subscription)
                                <tr class="bg-orange-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $subscription->name ?? 'N/A' }}</div>
                                        <div class="text-sm text-gray-500">{{ $subscription->email ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">
                                        {{ $subscription->subscription_ends_at?->format('Y-m-d') ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $remainingDays = $subscription->subscription_ends_at ?
                                                \Carbon\Carbon::now()->diffInDays($subscription->subscription_ends_at, false) : 0;
                                        @endphp
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                            {{ $remainingDays > 0 ? $remainingDays . ' 天' : '已過期' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        N/A
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        N/A
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-green-600">
                    ✓ 目前沒有即將到期的訂閱
                </div>
            @endif
        </x-filament::section>

        <!-- 最近訂閱訂單 -->
        <x-filament::section>
            <x-slot name="heading">
                最近訂閱訂單
            </x-slot>
            @if($recentSubscriptionOrders->count() > 0)
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">訂單編號</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">用戶</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">月數</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">金額</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">狀態</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">創建時間</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($recentSubscriptionOrders as $order)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $order->order_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $order->user->name ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $order->months }} 個月
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        NT$ {{ number_format($order->total_amount) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            @if($order->status === 'paid') bg-green-100 text-green-800
                                            @elseif($order->status === 'pending') bg-yellow-100 text-yellow-800
                                            @else bg-red-100 text-red-800
                                            @endif">
                                            {{ $order->status === 'paid' ? '已付款' : ($order->status === 'pending' ? '待付款' : '其他') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $order->created_at?->format('Y-m-d H:i') ?? 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    目前沒有訂閱訂單
                </div>
            @endif
        </x-filament::section>

        <!-- 訂閱訂單表格 -->
        <x-filament::section>
            <x-slot name="heading">
                所有訂閱訂單管理
            </x-slot>
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>