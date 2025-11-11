@extends('frontend.layouts.app')

@section('title', $store->name . ' - 592美食訂餐平台')
@section('description', $store->description ?: '探索' . $store->name . '的美味菜單，享受優質的美食體驗')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- 店家資訊 -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-8">
        <!-- 頂部橫幅 -->
        <div class="relative h-64 bg-gradient-to-br from-blue-50 to-purple-50">
            @if($store->primary_image_url)
                <img src="{{ $store->primary_image_url }}" alt="{{ $store->name }}"
                     class="w-full h-full object-cover">
            @endif

            <!-- 品牌名稱 -->
            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-6">
                <div class="max-w-4xl mx-auto">
                    <h1 class="text-4xl font-bold text-white mb-2">592Meal</h1>
                    <p class="text-white/90 text-lg">{{ $store->getTypeLabel() }}</p>
                </div>
            </div>
        </div>

        <!-- 基本資訊 -->
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- 左側資訊 -->
                <div class="space-y-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <div>
                            <p class="font-semibold text-gray-900">地址</p>
                            <p class="text-gray-600">{{ $store->address }}</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        <div>
                            <p class="font-semibold text-gray-900">電話</p>
                            <p class="text-gray-600">{{ $store->phone }}</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <div>
                            <p class="font-semibold text-gray-900">營業時間</p>
                            <p class="text-gray-600">{{ $store->getOpenHoursText() }}</p>
                        </div>
                    </div>
                </div>

                <!-- 右側資訊 -->
                <div class="space-y-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <div>
                            <p class="font-semibold text-gray-900">服務模式</p>
                            <p class="text-gray-600">{{ $store->getServiceModeLabel() }}</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                        <div>
                            <p class="font-semibold text-gray-900">評分</p>
                            <p class="text-gray-600">{{ number_format($store->getAverageRating(), 1) }} / 5.0</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <div>
                            <p class="font-semibold text-gray-900">菜單項目</p>
                            <p class="text-gray-600">{{ $store->menu_items_count ?? 0 }} 項</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 店家描述 -->
            @if($store->description)
            <div class="mt-8 pt-8 border-t border-gray-200">
                <h3 class="text-xl font-bold text-gray-900 mb-4">關於我們</h3>
                <p class="text-gray-600 leading-relaxed">{{ $store->description }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- 行動按鈕 -->
    @if($store->subdomain)
    <div class="bg-blue-600 text-white rounded-2xl p-8 text-center">
        <h3 class="text-2xl font-bold mb-4">準備開始點餐了嗎？</h3>
        <p class="text-blue-100 mb-6">前往店家專屬頁面，瀏覽完整菜單並下訂單</p>
        <a href="http://{{ $store->subdomain }}.{{ parse_url(config('app.url'), PHP_URL_HOST) }}"
           class="inline-flex items-center bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-blue-50 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
            </svg>
            進入店家
        </a>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    // 設置當前店家資訊供購物車使用
    window.currentStore = {
        slug: '{{ $store->store_slug_name }}',
        name: '{{ $store->name }}'
    };
</script>
@endsection