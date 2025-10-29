@php
    $storeUrl = $store->store_url;
    $isOpen = $store->isCurrentlyOpen();
    $openHoursText = $store->getOpenHoursText();

    // 取得店家類型對應的圖示
    $getIcon = function($type) {
        $icons = [
            'restaurant' => '<svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>',
            'cafe' => '<svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6h-3a1 1 0 00-1 1v8a1 1 0 001 1h3a1 1 0 001-1V7a1 1 0 00-1-1zM7 6h8M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2M9 6v10a1 1 0 001 1h4a1 1 0 001-1V6" /></svg>',
            'snack' => '<svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>',
            'bar' => '<svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>',
            'bakery' => '<svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>',
            'other' => '<svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>'
        ];
        return $icons[$type] ?? $icons['other'];
    };
@endphp

<div class="store-card group cursor-pointer bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100" onclick="window.location.href='{{ $storeUrl }}'">
    <!-- 店家圖片區域 -->
    <div class="relative h-48 overflow-hidden bg-gradient-to-br from-blue-50 to-purple-50">
        @if($store->logo_url)
            <img src="{{ $store->logo_url }}" alt="{{ $store->name }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
        @else
            <div class="w-full h-full flex items-center justify-center">
                {!! $getIcon($store->store_type) !!}
            </div>
        @endif

        <!-- 推薦標籤 -->
        @if($store->is_featured)
            <div class="absolute top-3 right-3 bg-amber-500 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-md">
                <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
                推薦
            </div>
        @endif

        <!-- 營業狀態 -->
        <div class="absolute top-3 left-3">
            @if($isOpen)
                <div class="bg-green-500 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-md flex items-center">
                    <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/>
                    </svg>
                    營業中
                </div>
            @else
                <div class="bg-gray-500 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-md flex items-center">
                    <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/>
                    </svg>
                    休息中
                </div>
            @endif
        </div>
    </div>

    <!-- 店家資訊區域 -->
    <div class="p-5">
        <!-- 店家名稱和類型 -->
        <div class="flex items-start justify-between mb-3">
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-900 mb-1 group-hover:text-blue-600 transition-colors">
                    {{ $store->name }}
                </h3>
                <div class="flex items-center text-sm text-gray-600">
                    <span class="mr-2">{!! $getIcon($store->store_type) !!}</span>
                    <span>{{ $store->getTypeLabel() }}</span>
                </div>
            </div>
        </div>

        <!-- 店家描述 -->
        @if($store->description)
            <p class="text-sm text-gray-600 line-clamp-2 mb-4 leading-relaxed">
                {{ $store->description }}
            </p>
        @endif

        <!-- 關鍵資訊 -->
        <div class="space-y-2 mb-4">
            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="truncate">{{ $store->address }}</span>
            </div>

            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                <span>{{ $store->phone }}</span>
            </div>

            <div class="flex items-center text-sm">
                <svg class="w-4 h-4 mr-2 {{ $isOpen ? 'text-green-500' : 'text-gray-400' }}" fill="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/>
                </svg>
                <span class="{{ $isOpen ? 'text-green-600 font-medium' : 'text-gray-500' }}">
                    {{ $openHoursText }}
                </span>
            </div>

            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>{{ $store->getServiceModeLabel() }}</span>
            </div>
        </div>

        <!-- 統計資訊 -->
        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
            <div class="flex items-center space-x-4">
                <div class="flex items-center">
                    <svg class="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-900">{{ number_format($store->getAverageRating(), 1) }}</span>
                </div>

                <div class="flex items-center">
                    <svg class="w-4 h-4 text-blue-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span class="text-sm text-gray-600">{{ $store->menu_items_count ?? 0 }} 項</span>
                </div>
            </div>

            <div class="text-xs text-gray-500">
                {{ $store->city ?? '' }} {{ $store->area ?? '' }}
            </div>
        </div>
    </div>
</div>