@extends('frontend.layouts.app')

@section('title', '我的訂單')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- 頁面標題 -->
    <div class="bg-white border-b">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 xl:px-12 py-8" style="max-width: 1400px;">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 frontend-title">
                        <i class="fas fa-receipt mr-2"></i>我的訂單
                    </h1>
                    @if(session('line_logged_in'))
                        <p class="mt-1 text-sm text-gray-500 frontend-content">
                            <i class="fab fa-line mr-1 text-green-600"></i>{{ session('line_user.display_name') }}
                        </p>
                    @endif
                </div>
                <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 bg-orange-500 text-white font-medium rounded-lg hover:bg-orange-600 transition-colors">
                    <i class="fas fa-home mr-2"></i>返回首頁
                </a>
            </div>
        </div>
    </div>

    <div class="mx-auto px-4 sm:px-6 lg:px-8 xl:px-12 py-8" style="max-width: 1400px;">
        @if($orders->count() > 0)
            <!-- 訂單列表 -->
            <div class="space-y-4">
                @foreach($orders as $order)
                    <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200">
                        <div class="p-6">
                            <!-- 訂單標題列 -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            訂單編號：{{ $order->order_number }}
                                        </h3>
                                        <!-- 訂單狀態標籤 -->
                                        <span class="px-3 py-1 rounded-full text-xs font-medium
                                            @if($order->status === 'pending') bg-yellow-100 text-yellow-800
                                            @elseif($order->status === 'confirmed') bg-blue-100 text-blue-800
                                            @elseif($order->status === 'preparing') bg-indigo-100 text-indigo-800
                                            @elseif($order->status === 'ready') bg-green-100 text-green-800
                                            @elseif($order->status === 'completed') bg-green-100 text-green-800
                                            @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ $order->status_label }}
                                        </span>
                                    </div>
                                    <div class="mt-2 flex items-center space-x-4 text-sm text-gray-600">
                                        <span>
                                            <i class="fas fa-store mr-1"></i>{{ $order->store->name }}
                                        </span>
                                        <span>
                                            <i class="fas fa-calendar mr-1"></i>{{ $order->created_at->format('Y/m/d H:i') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right ml-4">
                                    <div class="text-2xl font-bold text-green-700">
                                        ${{ number_format($order->total_amount, 0) }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        共 {{ $order->total_quantity }} 件商品
                                    </div>
                                </div>
                            </div>

                            <!-- 訂單商品列表（精簡顯示前3項） -->
                            <div class="border-t pt-4">
                                <div class="space-y-2">
                                    @foreach($order->orderItems->take(3) as $item)
                                        <div class="flex items-center justify-between text-sm">
                                            <div class="flex items-center space-x-2">
                                                <span class="w-6 h-6 bg-gray-100 rounded flex items-center justify-center text-xs text-gray-600">
                                                    {{ $item->quantity }}
                                                </span>
                                                <span class="text-gray-700">{{ $item->menuItem->name ?? '商品已下架' }}</span>
                                            </div>
                                            <span class="text-gray-600">${{ number_format($item->total_price, 0) }}</span>
                                        </div>
                                    @endforeach
                                    @if($order->orderItems->count() > 3)
                                        <div class="text-xs text-gray-500 text-center pt-2">
                                            還有 {{ $order->orderItems->count() - 3 }} 項商品...
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- 操作按鈕 -->
                            <div class="mt-4 pt-4 border-t flex items-center justify-between">
                                <div class="text-sm text-gray-500">
                                    @if($order->notes)
                                        <i class="fas fa-comment-dots mr-1"></i>備註：{{ Str::limit($order->notes, 30) }}
                                    @endif
                                </div>
                                <a href="{{ route('frontend.order.show', $order->order_number) }}"
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-eye mr-2"></i>查看詳情
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- 分頁 -->
            <div class="mt-6">
                {{ $orders->links() }}
            </div>
        @else
            <!-- 空狀態 -->
            <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                <i class="fas fa-receipt text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-900 mb-2">尚無訂單紀錄</h3>
                <p class="text-gray-500 mb-6">您還沒有建立任何訂單</p>
                <a href="{{ route('frontend.stores.index') }}"
                   class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-shopping-bag mr-2"></i>開始訂購
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
