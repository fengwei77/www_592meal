<x-filament-panels::page>
    <div class="fi-page-content space-y-6">
        <!-- 訂單統計卡片 -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <!-- 今日訂單數 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">今日訂單數</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($todayStats['orders'] ?? 0) }}</p>
                </div>
            </div>

            <!-- 今日收入 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">今日收入</p>
                    <p class="text-2xl font-bold text-green-600">NT$ {{ number_format($todayStats['revenue'] ?? 0) }}</p>
                </div>
            </div>

            <!-- 待處理訂單 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">待處理訂單</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ number_format($orderStats['pending'] ?? 0) }}</p>
                </div>
            </div>

            <!-- 準備中訂單 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">準備中訂單</p>
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($orderStats['preparing'] ?? 0) }}</p>
                </div>
            </div>

            <!-- 已完成訂單 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">已完成訂單</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($orderStats['completed'] ?? 0) }}</p>
                </div>
            </div>

            <!-- 已取消訂單 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">已取消訂單</p>
                    <p class="text-2xl font-bold text-red-600">{{ number_format($orderStats['cancelled'] ?? 0) }}</p>
                </div>
            </div>

            <!-- 本月訂單數 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">本月訂單數</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($monthlyStats['orders'] ?? 0) }}</p>
                </div>
            </div>

            <!-- 本月收入 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">本月收入</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">NT$ {{ number_format($monthlyStats['revenue'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        <!-- 待處理訂單 -->
        <x-filament::section>
            <x-slot name="heading">
                <span class="text-yellow-600">待處理訂單 (最新10筆)</span>
            </x-slot>
            @if($pendingOrders->count() > 0)
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-yellow-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">訂單編號</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">店家</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">顧客</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">金額</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">下單時間</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($pendingOrders as $order)
                                <tr class="bg-yellow-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $order->id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $order->store?->name ?? '未指定' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $order->customer_name ?? $order->customer?->name ?? 'N/A' }}</div>
                                        <div class="text-sm text-gray-500">{{ $order->customer_phone ?? $order->customer?->phone ?? '' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        NT$ {{ number_format($order->total_amount) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $order->created_at?->format('Y-m-d H:i') ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-yellow-600 hover:text-yellow-900 mr-3" onclick="confirmOrder({{ $order->id }})">
                                            確認
                                        </button>
                                        <button class="text-red-600 hover:text-red-900" onclick="cancelOrder({{ $order->id }})">
                                            取消
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-green-600">
                    ✓ 目前沒有待處理的訂單
                </div>
            @endif
        </x-filament::section>

        <!-- 最近訂單 -->
        <x-filament::section>
            <x-slot name="heading">
                最近訂單 (最新10筆)
            </x-slot>
            @if($recentOrders->count() > 0)
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">訂單編號</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">店家</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">顧客</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">金額</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">狀態</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">創建時間</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($recentOrders as $order)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $order->id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $order->store?->name ?? '未指定' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $order->customer_name ?? $order->customer?->name ?? 'N/A' }}</div>
                                        <div class="text-sm text-gray-500">{{ $order->customer_phone ?? $order->customer?->phone ?? '' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        NT$ {{ number_format($order->total_amount) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            @if($order->status === 'completed') bg-green-100 text-green-800
                                            @elseif($order->status === 'pending') bg-yellow-100 text-yellow-800
                                            @elseif($order->status === 'preparing') bg-blue-100 text-blue-800
                                            @elseif($order->status === 'ready') bg-purple-100 text-purple-800
                                            @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ $order->status }}
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
                    目前沒有訂單
                </div>
            @endif
        </x-filament::section>

        <!-- 熱門店家 -->
        <x-filament::section>
            <x-slot name="heading">
                熱門店家 (30天內訂單數)
            </x-slot>
            @if($topStores->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($topStores as $store)
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200">
                            <div class="text-center">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $store->name }}</p>
                                <p class="text-lg font-bold text-blue-600">{{ $store->orders_count }} 筆訂單</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    目前沒有店家資料
                </div>
            @endif
        </x-filament::section>

        <!-- 所有訂單表格 -->
        <x-filament::section>
            <x-slot name="heading">
                所有餐點訂單管理
            </x-slot>
            {{ $this->table }}
        </x-filament::section>
    </div>

    <script>
        function confirmOrder(orderId) {
            if (confirm('確定要確認這個訂單嗎？')) {
                // 這裡可以添加AJAX請求來確認訂單
                window.location.reload();
            }
        }

        function cancelOrder(orderId) {
            if (confirm('確定要取消這個訂單嗎？')) {
                // 這裡可以添加AJAX請求來取消訂單
                window.location.reload();
            }
        }
    </script>
</x-filament-panels::page>