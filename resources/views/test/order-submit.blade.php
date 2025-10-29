@extends('frontend.layouts.app')

@section('title', '訂單提交測試')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">訂單提交測試</h1>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">測試說明</h2>
        <p class="text-gray-600 mb-6">
            這個頁面用於測試訂單提交功能是否正常工作。請按照以下步驟進行測試：
        </p>
        <ol class="list-decimal list-inside space-y-2 text-gray-700 mb-6">
            <li>確保購物車中有商品</li>
            <li>填寫必要的資訊</li>
            <li>點擊提交按鈕</li>
            <li>觀察是否有成功頁面或錯誤訊息</li>
        </ol>

        @if(session('cart') && count(session('cart')) > 0)
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <h3 class="text-green-800 font-medium mb-2">✅ 購物車狀態正常</h3>
                <p class="text-green-700">購物車中有 {{ count(session('cart')) } 個商品</p>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h3 class="text-blue-800 font-medium mb-2">🔗 快速測試連結</h3>
                <p class="text-blue-700 mb-2">
                    <a href="{{ route('frontend.order.create', 's000004') }}" class="text-blue-600 hover:text-blue-800 underline">
                        前往結帳頁面 (s000004)
                    </a>
                </p>
                <p class="text-sm text-blue-600">
                    這會直接跳轉到結帳頁面，測試完整的訂單流程
                </p>
            </div>
        @else
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <h3 class="text-red-800 font-medium mb-2">❌ 購物車為空</h3>
                <p class="text-red-700">請先添加商品到購物車再進行測試</p>
                <p class="text-red-600 mt-2">
                    <a href="/store/s000004" class="text-red-600 hover:text-red-800 underline">
                        前往店家頁面添加商品
                    </a>
                </p>
            </div>
        @endif

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <h3 class="text-yellow-800 font-medium mb-2">⚠️ 注意事項</h3>
            <ul class="list-disc list-inside space-y-1 text-yellow-700">
                <li>訂單提交後會創建真實的訂單記錄</li>
                <li>店家會收到新訂單通知</li>
                <li>測試完成後可以在後台刪除測試訂單</li>
                <li>如果遇到問題，請檢查瀏覽器控制台的錯誤訊息</li>
            </ul>
        </div>
    </div>

    <!-- 最近訂單 -->
    <div class="mt-8 bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">最近訂單記錄</h2>
        @php
            $recentOrders = \App\Models\Order::with('store')->latest()->take(5)->get();
        @endphp
        @if($recentOrders->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">訂單編號</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">店家</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">顧客</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">金額</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">狀態</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">時間</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($recentOrders as $order)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $order->order_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $order->store->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $order->customer_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    ${{ number_format($order->total_amount, 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($order->status == 'pending') bg-yellow-100 text-yellow-800
                                        @elseif($order->status == 'confirmed') bg-green-100 text-green-800
                                        @elseif($order->status == 'cancelled') bg-red-100 text-red-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ $order->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $order->created_at->format('H:i:s') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500">暫無訂單記錄</p>
        @endif
    </div>
</div>
@endsection