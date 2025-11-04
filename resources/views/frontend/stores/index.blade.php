@extends('frontend.layouts.app')

@section('title', '店家清單 - 592美食訂餐平台')
@section('description', '發現附近最棒的美食店家，支援地圖瀏覽、地區篩選和關鍵字搜尋')

@section('styles')
<style>
    /* 統一的設計系統 */
    :root {
        --primary-color: #3b82f6;
        --primary-hover: #2563eb;
        --secondary-color: #8b5cf6;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --error-color: #ef4444;
        --gray-50: #f9fafb;
        --gray-100: #f3f4f6;
        --gray-200: #e5e7eb;
        --gray-300: #d1d5db;
        --gray-400: #9ca3af;
        --gray-500: #6b7280;
        --gray-600: #4b5563;
        --gray-700: #374151;
        --gray-800: #1f2937;
        --gray-900: #111827;
        --border-radius: 0.75rem;
        --border-radius-lg: 1rem;
        --border-radius-xl: 1.5rem;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    /* 全域樣式重置 */
    .store-grid {
        display: grid;
        gap: 1.5rem;
        grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    }

    /* 桌面模式增加間距 */
    @media (min-width: 1024px) {
        .store-grid {
            gap: 3rem;
            padding: 1rem 0;
        }
    }

    /* 美化滾動條 */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    ::-webkit-scrollbar-track {
        background: var(--gray-100);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: var(--gray-400);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--gray-500);
    }

    /* 動畫效果 */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    .animate-fadeIn {
        animation: fadeIn 0.6s ease-out;
    }

    .animate-slideDown {
        animation: slideDown 0.3s ease-out;
    }

    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    /* 視圖切換標籤 */
    .view-tabs {
        display: flex;
        background: white;
        border-radius: var(--border-radius-xl);
        padding: 0.25rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow);
        border: 1px solid var(--gray-200);
    }

    .view-tab {
        flex: 1;
        padding: 0.875rem 1.25rem;
        border: none;
        background: transparent;
        border-radius: var(--border-radius);
        font-weight: 600;
        color: var(--gray-600);
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .view-tab:hover {
        background: var(--gray-50);
        color: var(--gray-800);
    }

    .view-tab.active {
        background: var(--primary-color);
        color: white;
        box-shadow: var(--shadow-sm);
    }

    /* 統計資訊卡片 - 升級版設計 */
    .stats-section {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
        position: relative;
        /* 自動高度調整 */
        align-items: start;
        min-height: auto;
    }

    .stat-card {
        background: white;
        border-radius: var(--border-radius-xl);
        padding: 2rem 1.5rem;
        text-align: center;
        box-shadow: var(--shadow);
        border: 1px solid var(--gray-100);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        overflow: hidden;
        cursor: pointer;
        /* 確保卡片高度一致 */
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 140px;
    }

    /* 背景漸層裝飾 */
    .stat-card::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.05) 0%, transparent 70%);
        transition: all 0.4s ease;
        pointer-events: none;
    }

    /* 頂部漸層條 */
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        transform: scaleX(0);
        transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        transform-origin: left;
    }

    .stat-card:hover::before {
        transform: scaleX(1);
    }

    .stat-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: var(--shadow-xl);
        border-color: var(--primary-color);
    }

    .stat-card:hover::after {
        top: -30%;
        right: -30%;
    }

    /* 數字樣式 - 添加漸層和動畫 */
    .stat-number {
        font-size: 2.75rem;
        font-weight: 900;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 0.75rem;
        line-height: 1;
        position: relative;
        transition: all 0.3s ease;
        text-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);
    }

    .stat-card:hover .stat-number {
        transform: scale(1.05);
        filter: brightness(1.1);
    }

    /* 標籤樣式 - 更現代的排版 */
    .stat-label {
        color: var(--gray-600);
        font-size: 0.95rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        position: relative;
        transition: all 0.3s ease;
    }

    .stat-card:hover .stat-label {
        color: var(--gray-800);
        transform: translateY(-2px);
    }

    /* 圖標裝飾 */
    .stat-icon {
        position: absolute;
        top: 1.5rem;
        right: 1.5rem;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.25rem;
        opacity: 0.8;
        transition: all 0.3s ease;
        box-shadow: 0 4px 8px rgba(59, 130, 246, 0.2);
    }

    .stat-card:hover .stat-icon {
        opacity: 1;
        transform: rotate(10deg) scale(1.1);
        box-shadow: 0 6px 12px rgba(59, 130, 246, 0.3);
    }

    /* 特殊樣式變體 */
    .stat-card.featured {
        background: linear-gradient(135deg, #fff, #f8fafc);
    }

    .stat-card.featured::before {
        background: linear-gradient(90deg, var(--secondary-color), var(--success-color));
    }

    .stat-card.cities {
        background: linear-gradient(135deg, #fff, #fefce8);
    }

    .stat-card.cities::before {
        background: linear-gradient(90deg, var(--warning-color), var(--error-color));
    }

    .stat-card.filtered {
        background: linear-gradient(135deg, #fff, #f0f9ff);
    }

    .stat-card.filtered::before {
        background: linear-gradient(90deg, var(--success-color), var(--primary-color));
    }

    /* 微光動畫效果 */
    @keyframes shimmer {
        0% {
            transform: translateX(-100%);
        }
        100% {
            transform: translateX(100%);
        }
    }

    .stat-card.loading::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transform: translateX(-100%);
        animation: shimmer 2s infinite;
    }

    /* 空狀態設計 */
    .empty-state {
        text-align: center;
        padding: 5rem 2rem;
        color: var(--gray-600);
        background: white;
        border-radius: var(--border-radius-xl);
        box-shadow: var(--shadow);
        border: 1px solid var(--gray-100);
    }

    .empty-state__icon {
        font-size: 4rem;
        margin-bottom: 1.5rem;
        opacity: 0.4;
        display: block;
        margin-left: auto;
        margin-right: auto;
    }

    .empty-state__title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--gray-800);
        margin-bottom: 0.75rem;
    }

    .empty-state__description {
        margin-bottom: 2rem;
        line-height: 1.6;
        color: var(--gray-600);
    }

    /* 響應式設計 - 升級版 */
    @media (max-width: 1024px) {
        .stats-section {
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
        }

        .stat-card {
            padding: 1.75rem 1.25rem;
        }

        .stat-number {
            font-size: 2.25rem;
        }

        .stat-icon {
            width: 2.25rem;
            height: 2.25rem;
            font-size: 1.1rem;
            top: 1.25rem;
            right: 1.25rem;
        }
    }

    @media (max-width: 768px) {
        .store-grid {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .stats-section {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            padding: 1.5rem 1rem;
            min-height: 140px;
        }

        .stat-number {
            font-size: 2rem;
        }

        .stat-label {
            font-size: 0.85rem;
            letter-spacing: 0.025em;
        }

        .stat-icon {
            width: 2rem;
            height: 2rem;
            font-size: 1rem;
            top: 1rem;
            right: 1rem;
        }

        .stat-card:hover {
            transform: translateY(-4px) scale(1.01);
        }
    }

    @media (max-width: 640px) {
        .stats-section {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .stat-card {
            padding: 1.75rem 1.25rem;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .stat-number {
            font-size: 2.25rem;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
        }

        .stat-icon {
            position: relative;
            top: auto;
            right: auto;
            margin-bottom: 1rem;
            width: 3rem;
            height: 3rem;
            font-size: 1.5rem;
        }

        .view-tab {
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card:hover .stat-number {
            transform: scale(1.02);
        }
    }

    /* 超小螢幕優化 */
    @media (max-width: 480px) {
        .stats-section {
            gap: 0.875rem;
        }

        .stat-card {
            padding: 1.5rem 1rem;
            min-height: 100px;
        }

        .stat-number {
            font-size: 2rem;
        }

        .stat-label {
            font-size: 0.8rem;
        }

        .stat-icon {
            width: 2.5rem;
            height: 2.5rem;
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
        }
    }

    /* 載入骨架屏 */
    .skeleton {
        background: linear-gradient(90deg, var(--gray-200) 25%, var(--gray-300) 50%, var(--gray-200) 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
        border-radius: var(--border-radius);
    }

    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* 文字截斷工具類 */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* 焦點樣式優化 */
    *:focus {
        outline: none;
    }

    *:focus-visible {
        outline: 2px solid var(--primary-color);
        outline-offset: 2px;
        border-radius: var(--border-radius);
    }
</style>
@endsection

@section('content')
<div class="container mx-auto px-4 py-6 lg:py-8">
    <!-- 頁面標題 -->
    <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">發現美食店家</h1>
        <p class="text-xl text-gray-600">探索附近的優質餐廳，開始美食之旅</p>
    </div>

    <!-- 搜尋區域 -->
    @include('frontend.stores.partials.search-bar')

    <!-- 統計資訊 - 升級版設計 -->
    <div class="stats-section grid gap-4 grid-cols-2 lg:grid-cols-4 mb-6 lg:mb-8">

    <div class="stat-card bg-white p-6 rounded-lg shadow-md">
        <div class="flex items-baseline justify-center space-x-2">
            <div class="stat-icon text-xl">🏪</div>
            <div class="stat-number text-2xl font-bold" data-target="{{ $stats['total_stores'] ?? 0 }}">0</div>
            <div class="stat-label text-sm text-gray-500">店家總數</div>
        </div>
    </div>

    <div class="stat-card featured bg-blue-100 p-6 rounded-lg shadow-md">
        <div class="flex items-baseline justify-center space-x-2">
            <div class="stat-icon text-xl">⭐</div>
            <div class="stat-number text-2xl font-bold text-blue-800" data-target="{{ $stats['featured_stores'] ?? 0 }}">0</div>
            <div class="stat-label text-sm text-blue-600">推薦店家</div>
        </div>
    </div>

    <div class="stat-card cities bg-white p-6 rounded-lg shadow-md">
        <div class="flex items-baseline justify-center space-x-2">
            <div class="stat-icon text-xl">🏙️</div>
            <div class="stat-number text-2xl font-bold" data-target="{{ $stats['cities_count'] ?? 0 }}">0</div>
            <div class="stat-label text-sm text-gray-500">服務城市</div>
        </div>
    </div>

    <div class="stat-card filtered bg-white p-6 rounded-lg shadow-md">
        <div class="flex items-baseline justify-center space-x-2">
            <div class="stat-icon text-xl">🎯</div>
            <div class="stat-number text-2xl font-bold" data-target="{{ $stores->total() }}">0</div>
            <div class="stat-label text-sm text-gray-500">符合條件</div>
        </div>
    </div>

</div>

    <!-- 篩選器 -->
    @include('frontend.stores.partials.filters')

    <!-- 檢視模式切換 -->
    <div class="view-tabs mb-6 lg:mb-8">
        <button class="view-tab {{ $view == 'list' ? 'active' : '' }}" onclick="switchView('list')">
            📋 列表模式
        </button>
        <button class="view-tab {{ $view == 'map' ? 'active' : '' }}" onclick="switchView('map')">
            🗺️ 地圖模式
        </button>
        @if(config('app.debug'))
            <button class="view-tab" onclick="debugMapState()" title="檢查地圖狀態">
                🗺️
            </button>
            <button class="view-tab" onclick="debugReloadMapStores()" title="重新載入店家">
                🔄
            </button>
        @endif
    </div>

    <!-- 店家列表 -->
    <div id="list-view" class="{{ $view == 'map' ? 'hidden' : '' }}">
        @if($stores->count() > 0)
            <div class="store-grid" id="stores-container">
                @foreach($stores as $store)
                    @include('frontend.stores.partials.list-card', ['store' => $store])
                @endforeach
            </div>

            <!-- 分頁 -->
            @if($stores->hasPages())
                <div class="mt-8 lg:mt-12 flex justify-center">
                    {{ $stores->links() }}
                </div>
            @endif
        @else
            <div class="empty-state">
                <div class="empty-state__icon">🍽️</div>
                <h3 class="empty-state__title">找不到符合條件的店家</h3>
                <p class="empty-state__description">
                    試試調整篩選條件或使用其他關鍵字搜尋
                </p>
                <a href="{{ route('frontend.stores.index') }}" class="search-button">
                    清除篩選條件
                </a>
            </div>
        @endif
    </div>

    <!-- 地圖模式 -->
    <div id="map-view" class="{{ $view != 'map' ? 'hidden' : '' }}">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div id="store-map" style="height: 600px; width: 100%;">
                <!-- 地圖將在這裡載入 -->
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// 頁面狀態管理
const state = {
    currentView: '{{ $view }}',
    currentFilters: {
        city: '{{ request("city") }}',
        area: '{{ request("area") }}',
        type: '{{ request("type") }}',
        search: '{{ request("search") }}'
    },
    stores: [],
    map: null,
    markers: [],
    userLocation: null,
    userLocationMarker: null
};

// 切換檢視模式
function switchView(view) {
    state.currentView = view;

    // 更新分頁狀態
    document.querySelectorAll('.view-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    event.target.classList.add('active');

    // 顯示/隱藏內容
    if (view === 'list') {
        document.getElementById('list-view').classList.remove('hidden');
        document.getElementById('map-view').classList.add('hidden');
        updateURL({ view: null });
    } else {
        document.getElementById('list-view').classList.add('hidden');
        document.getElementById('map-view').classList.remove('hidden');
        updateURL({ view: 'map' });

        // 檢查 Leaflet 是否已載入，如果沒有則等待載入
        if (typeof L !== 'undefined') {
            console.log('Leaflet 已載入，直接初始化地圖');
            setTimeout(() => initMap(), 100);
        } else {
            console.log('Leaflet 尚未載入，等待載入完成後初始化地圖');
            // 等待 Leaflet 載入完成後會自動調用 initMap()
        }
    }
}

// 更新URL參數
function updateURL(params) {
    const url = new URL(window.location);

    // 清除現有參數
    Object.keys(state.currentFilters).forEach(key => {
        if (!state.currentFilters[key]) {
            url.searchParams.delete(key);
        }
    });

    // 設置新參數
    Object.entries(state.currentFilters).forEach(([key, value]) => {
        if (value) {
            url.searchParams.set(key, value);
        }
    });

    // 設置檢視模式
    if (params.view) {
        url.searchParams.set('view', params.view);
    } else {
        url.searchParams.delete('view');
    }

    // 更新瀏覽器歷史
    if (params.replace) {
        window.history.replaceState({}, '', url);
    } else {
        window.history.pushState({}, '', url);
    }
}

// 初始化地圖
function initMap() {
    // 檢查 Leaflet 是否已載入
    if (typeof L === 'undefined') {
        console.log('Leaflet 尚未載入，延遲初始化地圖');
        return;
    }
    if (state.map) return;

    // 使用 Leaflet.js (開源地圖庫)
    const mapElement = document.getElementById('store-map');
    if (!mapElement) return;

    // 初始化地圖 - 台灣中心
    state.map = L.map('store-map').setView([23.8, 121.0], 8);

    // 加入地圖圖層
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(state.map);

    // 載入店家資料並標記
    loadMapStores();

    // 加入定位控制按鈕
    addLocationControl();

    
    // 監聽地圖邊界變化
    if (state.map) {
        state.map.on('moveend', function() {
            // 當地圖移動結束時，可以根據新的邊界重新載入店家
            console.log('地圖移動結束');
        });

        // 監聽地圖拖曳結束
        state.map.on('dragend', function() {
            console.log('地圖拖曳結束，可以根據新邊界載入店家');
            // 這裡可以加入自動載入邊界內店家的邏輯
        });
    }
}

// 加入定位控制按鈕
function addLocationControl() {
    if (!state.map) return;

    // 建立定位控制按鈕
    const locationControl = L.control({ position: 'topright' });

    locationControl.onAdd = function(map) {
        const div = L.DomUtil.create('div', 'leaflet-bar');
        div.innerHTML = `
            <button id="location-btn"
                    onclick="getCurrentLocation()"
                    title="取得我的位置"
                    style="background: white; border: 2px solid rgba(0,0,0,0.2); border-radius: 4px; padding: 6px; cursor: pointer; font-size: 16px;">
                📍
            </button>
        `;

        // 防止點擊按鈕時觸發地圖事件
        L.DomEvent.disableClickPropagation(div);

        return div;
    };

    locationControl.addTo(state.map);
}

// 取得使用者目前位置
async function getCurrentLocation() {
    const btn = document.getElementById('location-btn');
    const originalText = btn.innerHTML;

    // 檢查瀏覽器是否支援地理定位
    if (!navigator.geolocation) {
        showToast('您的瀏覽器不支援地理定位功能', 'error');
        return;
    }

    try {
        // 顯示載入狀態
        btn.innerHTML = '⏳';
        btn.disabled = true;

        // 取得位置
        const position = await new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(resolve, reject, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000 // 5分鐘內的快取位置
            });
        });

        const { latitude, longitude } = position.coords;

        // 移除舊的使用者位置標記
        if (state.userLocationMarker) {
            state.map.removeLayer(state.userLocationMarker);
        }

        // 建立使用者位置標記
        const userIcon = L.divIcon({
            html: '<div style="background: #3b82f6; border: 3px solid white; border-radius: 50%; width: 16px; height: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>',
            iconSize: [16, 16],
            className: 'user-location-marker'
        });

        state.userLocationMarker = L.marker([latitude, longitude], { icon: userIcon })
            .addTo(state.map)
            .bindPopup('<strong>您的位置</strong>')
            .openPopup();

        // 更新地圖中心點並放大
        state.map.setView([latitude, longitude], 14);

        // 更新篩選條件中的使用者位置
        state.userLocation = { latitude, longitude };

        // 重新載入店家資料（按距離排序）
        loadMapStoresWithDistance();

        showToast('定位成功！已顯示附近店家', 'success');

    } catch (error) {
        let errorMessage = '無法取得您的位置';

        switch(error.code) {
            case error.PERMISSION_DENIED:
                errorMessage = '您拒絕了位置權限請求';
                break;
            case error.POSITION_UNAVAILABLE:
                errorMessage = '位置資訊暫時無法使用';
                break;
            case error.TIMEOUT:
                errorMessage = '定位請求超時';
                break;
        }

        showToast(errorMessage, 'error');
        console.error('定位錯誤:', error);

    } finally {
        // 恢復按鈕狀態
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// 載入地圖店家資料（包含距離計算）
async function loadMapStoresWithDistance() {
    if (!state.userLocation) {
        loadMapStores();
        return;
    }

    try {
        const params = new URLSearchParams();
        Object.entries(state.currentFilters).forEach(([key, value]) => {
            if (value) params.set(key, value);
        });

        // 加入使用者位置參數
        params.set('user_lat', state.userLocation.latitude);
        params.set('user_lng', state.userLocation.longitude);

        const response = await fetch(`/api/stores/map?${params}`);
        const data = await response.json();

        // 清除現有標記
        state.markers.forEach(marker => state.map.removeLayer(marker));
        state.markers = [];

        // 添加新標記（包含距離資訊）
        data.stores.forEach(store => {
            const popupContent = `
                <div style="min-width: 220px;">
                    <img src="${store.logo_url}" style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover;">
                    <h4 style="margin: 8px 0 4px 0;">${store.name}</h4>
                    ${store.distance ? `<p style="margin: 0 0 4px 0; color: #3b82f6; font-size: 14px; font-weight: bold;">📍 ${store.distance}</p>` : ''}
                    <p style="margin: 0 0 4px 0; color: #666; font-size: 14px;">${store.address}</p>
                    <p style="margin: 0 0 8px 0; color: ${store.is_open ? '#10b981' : '#6b7280'}; font-size: 13px;">
                        ${store.is_open ? '🟢 ' : '🔴 '}${store.open_hours_text}
                    </p>
                    <a href="${store.store_url}"
                       class="btn btn-primary btn-sm"
                       style="background: #3b82f6; color: white; padding: 4px 12px; border-radius: 4px; text-decoration: none; display: inline-block;">
                        進入店家
                    </a>
                    ${store.distance ? `
                        <button onclick="navigateToStore(${store.latitude}, ${store.longitude}, '${store.name}')"
                                class="btn btn-secondary btn-sm"
                                style="background: #6b7280; color: white; padding: 4px 12px; border-radius: 4px; text-decoration: none; display: inline-block; margin-left: 4px; border: none; cursor: pointer;">
                            🧭 導航
                        </button>
                    ` : ''}
                </div>
            `;

            const marker = L.marker([store.latitude, store.longitude])
                .addTo(state.map)
                .bindPopup(popupContent);

            state.markers.push(marker);
        });

        // 自動調整地圖範圍
        if (state.markers.length > 0) {
            const group = new L.featureGroup([...state.markers, state.userLocationMarker].filter(Boolean));
            state.map.fitBounds(group.getBounds().pad(0.15));
        }

    } catch (error) {
        console.error('載入地圖店家失敗:', error);
        // 降級到原始方法
        loadMapStores();
    }
}

// 導航到店家
function navigateToStore(lat, lng, storeName) {
    // 優先嘗試使用系統原生地圖應用
    if (isMobileDevice()) {
        // 行動裝置：嘗試開啟 Google Maps 或系統地圖
        const googleMapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
        window.open(googleMapsUrl, '_blank');
    } else {
        // 桌面裝置：開啟 Google Maps 網頁版
        const googleMapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
        window.open(googleMapsUrl, '_blank');
    }

    showToast(`正在開啟地圖導航至 ${storeName}`, 'info');
}

// 調試函數：手動重新載入地圖店家
function debugReloadMapStores() {
    console.log('🔄 手動重新載入地圖店家');
    if (state.map) {
        loadMapStores();
    } else {
        console.log('❌ 地圖尚未初始化');
        showToast('地圖尚未初始化，請先切換到地圖模式', 'error');
    }
}

// 調試函數：檢查地圖狀態
function debugMapState() {
    console.log('🗺️ 地圖狀態檢查:');
    console.log('state.map:', !!state.map);
    console.log('state.markers 數量:', state.markers.length);
    console.log('state.userLocation:', state.userLocation);
    console.log('state.userLocationMarker:', !!state.userLocationMarker);
    console.log('state.currentFilters:', state.currentFilters);

    if (state.map) {
        console.log('地圖中心:', state.map.getCenter());
        console.log('地圖縮放級別:', state.map.getZoom());
        console.log('地圖邊界:', state.map.getBounds());
    }
}

// 檢測是否為行動裝置
function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

// 載入地圖店家資料
async function loadMapStores() {
    try {
        const params = new URLSearchParams();
        Object.entries(state.currentFilters).forEach(([key, value]) => {
            if (value) params.set(key, value);
        });

        // 如果有使用者位置，加入位置參數
        if (state.userLocation) {
            params.set('user_lat', state.userLocation.latitude);
            params.set('user_lng', state.userLocation.longitude);
        }

        
        console.log('載入地圖店家資料，參數:', params.toString());
        const response = await fetch(`/api/stores/map?${params}`);
        const data = await response.json();

        console.log('地圖店家資料回應:', data);

        // 清除現有標記
        state.markers.forEach(marker => state.map.removeLayer(marker));
        state.markers = [];

        // 檢查是否有店家資料
        if (!data.stores || data.stores.length === 0) {
            console.log('沒有找到店家資料');
            // 顯示提示訊息
            if (state.map) {
                L.popup()
                    .setLatLng([23.8, 121.0])
                    .setContent('<div style="text-align: center; padding: 10px;">沒有符合條件的店家<br>請調整篩選條件或擴大地圖範圍</div>')
                    .openOn(state.map);
            }
            return;
        }

        console.log(`找到 ${data.stores.length} 家店家`);

        // 只處理有坐標的店家
        const storesWithCoordinates = [];

        data.stores.forEach(store => {
            if (store.has_coordinates && store.latitude && store.longitude) {
                storesWithCoordinates.push(store);
                console.log(`✅ 店家 ${store.name} 有坐標，加入地圖`);
            } else {
                console.log(`⚠️ 店家 ${store.name} 無坐標，跳過顯示`);
            }
        });

        console.log(`地圖將顯示 ${storesWithCoordinates.length} 家有坐標的店家`);

        // 先標記有坐標的店家
        storesWithCoordinates.forEach(store => {
            const popupContent = `
                <div style="min-width: 220px;">
                    <img src="${store.logo_url || '/images/default-store.svg'}" style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover;">
                    <h4 style="margin: 8px 0 4px 0;">${store.name}</h4>
                    ${store.distance ? `<p style="margin: 0 0 4px 0; color: #3b82f6; font-size: 14px; font-weight: bold;">📍 ${store.distance}</p>` : ''}
                    <p style="margin: 0 0 4px 0; color: #666; font-size: 14px;">${store.full_address || store.address}</p>
                    <p style="margin: 0 0 8px 0; color: ${store.is_open ? '#10b981' : '#6b7280'}; font-size: 13px;">
                        ${store.is_open ? '🟢 ' : '🔴 '}${store.open_hours_text}
                    </p>
                    <a href="${store.store_url}"
                       class="btn btn-primary btn-sm"
                       style="background: #3b82f6; color: white; padding: 4px 12px; border-radius: 4px; text-decoration: none; display: inline-block;">
                        進入店家
                    </a>
                    ${store.distance ? `
                        <button onclick="navigateToStore(${store.latitude}, ${store.longitude}, '${store.name}')"
                                class="btn btn-secondary btn-sm"
                                style="background: #6b7280; color: white; padding: 4px 12px; border-radius: 4px; text-decoration: none; display: inline-block; margin-left: 4px; border: none; cursor: pointer;">
                            🧭 導航
                        </button>
                    ` : ''}
                </div>
            `;

            const marker = L.marker([store.latitude, store.longitude])
                .addTo(state.map)
                .bindPopup(popupContent);

            state.markers.push(marker);
        });

        // 自動調整地圖範圍
        if (state.markers.length > 0) {
            const group = new L.featureGroup(state.markers);
            const bounds = group.getBounds();

            // 如果有使用者位置，也包含使用者位置
            if (state.userLocationMarker) {
                const userGroup = new L.featureGroup([state.userLocationMarker, ...state.markers]);
                state.map.fitBounds(userGroup.getBounds().pad(0.15));
            } else {
                state.map.fitBounds(bounds.pad(0.1));
            }

            console.log('地圖範圍已調整:', bounds);
        } else {
            // 如果沒有店家，但有篩選條件，顯示相應訊息
            const hasFilters = Object.values(state.currentFilters).some(value => value);
            if (hasFilters) {
                console.log('有篩選條件但沒有找到店家');
                // 可以在這裡加入「擴大範圍」的建議
            }
        }

    } catch (error) {
        console.error('載入地圖店家失敗:', error);
    }
}

// 縣市變更時更新區域選項
document.getElementById('city-filter')?.addEventListener('change', function(e) {
    const city = e.target.value;
    const areaFilter = document.getElementById('area-filter');

    if (!city) {
        // 清空區域選項
        areaFilter.innerHTML = '<option value="">全部區域</option>';
        return;
    }

    // 載入該縣市的區域
    fetch(`/api/stores/filters`)
        .then(response => response.json())
        .then(data => {
            const areas = data.areas.filter(area => {
                // 這裡可以添加邏輯過濾出該縣市的區域
                return true; // 暫時顯示所有區域
            });

            areaFilter.innerHTML = '<option value="">全部區域</option>';
            areas.forEach(area => {
                areaFilter.innerHTML += `<option value="${area}">${area}</option>`;
            });
        })
        .catch(error => console.error('載入區域失敗:', error));
});

// 數字動畫效果
function animateNumbers() {
    const statNumbers = document.querySelectorAll('.stat-number[data-target]');

    statNumbers.forEach(stat => {
        const target = parseInt(stat.getAttribute('data-target'));
        const duration = 2000; // 2秒動畫
        const start = 0;
        const increment = target / (duration / 16); // 60fps
        let current = start;

        const updateNumber = () => {
            current += increment;
            if (current < target) {
                stat.textContent = Math.floor(current).toLocaleString();
                requestAnimationFrame(updateNumber);
            } else {
                stat.textContent = target.toLocaleString();
            }
        };

        // 延遲啟動，創造連續效果
        setTimeout(() => {
            updateNumber();
        }, Array.from(statNumbers).indexOf(stat) * 100);
    });
}

// 頁面載入完成後初始化
document.addEventListener('DOMContentLoaded', function() {
    // 啟動數字動畫
    animateNumbers();

    // 處理附近篩選參數
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('nearby') === 'true' && urlParams.get('lat') && urlParams.get('lng')) {
        const lat = parseFloat(urlParams.get('lat'));
        const lng = parseFloat(urlParams.get('lng'));

        if (!isNaN(lat) && !isNaN(lng)) {
            state.userLocation = { latitude: lat, longitude: lng };

            // 更新附近按鈕狀態
            const nearbyBtn = document.getElementById('nearby-btn');
            const nearbyBtnText = document.getElementById('nearby-btn-text');
            if (nearbyBtn && nearbyBtnText) {
                nearbyBtn.classList.add('bg-gradient-to-r', 'from-blue-500', 'to-indigo-600');
                nearbyBtn.classList.remove('from-green-500', 'to-emerald-600');
                nearbyBtnText.textContent = '附近店家模式';
            }
        }
    }

    // 如果是地圖模式，初始化地圖
    if (state.currentView === 'map') {
        if (typeof L !== 'undefined') {
            console.log('Leaflet 已載入，初始化地圖');
            setTimeout(() => initMap(), 100);
        } else {
            console.log('Leaflet 尚未載入，等待載入完成');
        }
    }

    // 處理瀏覽器後退/前進
    window.addEventListener('popstate', function() {
        const params = new URLSearchParams(window.location.search);
        const view = params.get('view') || 'list';

        if (view !== state.currentView) {
            switchView(view);
        }
    });
});

// 載入 Leaflet.js 地圖庫 (如果尚未載入)
if (typeof L === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    script.onload = function() {
        console.log('Leaflet.js 載入完成');
        // 載入 Leaflet CSS
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);

        // 如果是地圖模式，初始化地圖
        if (state.currentView === 'map') {
            setTimeout(() => {
                console.log('開始初始化地圖');
                initMap();
            }, 100);
        }
    };
    document.head.appendChild(script);
} else {
    // Leaflet 已載入，直接初始化地圖
    console.log('Leaflet 已經載入，準備初始化地圖');

    // 如果是地圖模式，初始化地圖
    if (state.currentView === 'map') {
        setTimeout(() => {
            console.log('開始初始化地圖');
            initMap();
        }, 100);
    }
}
</script>
@endsection
