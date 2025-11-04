<!-- 店家篩選組件 -->
<div class="filters-section bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900">篩選店家</h3>
        </div>
        <a href="{{ route('frontend.stores.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium flex items-center transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            清除篩選
        </a>
    </div>

    <form method="GET" action="{{ route('frontend.stores.index') }}" class="space-y-4">
        @if(request('search'))
            <input type="hidden" name="search" value="{{ request('search') }}">
        @endif

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- 附近店家快速篩選 -->
            <div class="space-y-3">
                <label class="block text-sm font-semibold text-gray-900 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    附近店家
                </label>
                <div class="flex flex-col space-y-2">
                    <button
                        type="button"
                        onclick="filterNearbyStores()"
                        id="nearby-btn"
                        class="w-full px-4 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:from-green-600 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition-all duration-200 flex items-center justify-center shadow-lg hover:shadow-xl"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span id="nearby-btn-text">定位附近店家</span>
                    </button>
                    <div id="nearby-status" class="text-xs text-gray-500 text-center hidden">
                        <span class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-3 w-3 text-gray-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            定位中...
                        </span>
                    </div>
                </div>
            </div>

            <!-- 縣市篩選 -->
            <div class="space-y-3">
                <label for="city-filter" class="block text-sm font-semibold text-gray-900 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    縣市
                </label>
                <select
                    name="city"
                    id="city-filter"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 bg-white transition-all duration-200"
                    onchange="updateAreas(this.value)"
                >
                    <option value="">請選擇縣市</option>
                    @foreach($filters['cities'] ?? [] as $city)
                        <option value="{{ $city }}" {{ request('city') == $city ? 'selected' : '' }}>
                            {{ $city }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- 區域篩選 -->
            <div class="space-y-3">
                <label for="area-filter" class="block text-sm font-semibold text-gray-900 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    區域
                </label>
                <select
                    name="area"
                    id="area-filter"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 bg-white transition-all duration-200 disabled:bg-gray-50 disabled:text-gray-500"
                    {{ request('city') ? '' : 'disabled' }}
                    style="appearance: auto !important; -webkit-appearance: auto !important; -moz-appearance: auto !important;"
                >
                    <option value="">請選擇區域</option>
                    @if(request('city'))
                        @foreach($filters['areas'] ?? [] as $area)
                            <option value="{{ $area }}" {{ request('area') == $area ? 'selected' : '' }}>
                                {{ $area }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- 店家類型篩選 -->
            <div class="space-y-3">
                <label for="type-filter" class="block text-sm font-semibold text-gray-900 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                    </svg>
                    店家類型
                </label>
                <select
                    name="type"
                    id="type-filter"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 bg-white transition-all duration-200"
                >
                    <option value="">全部類型</option>
                    @foreach($filters['types'] ?? [] as $type)
                        <option value="{{ $type['value'] }}" {{ request('type') == $type['value'] ? 'selected' : '' }}>
                            {{ $type['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex items-center justify-between pt-6 border-t border-gray-100">
            <div class="flex items-center space-x-2">
                @if($stores->total() > 0)
                    <div class="flex items-center">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-sm text-gray-600">找到</span>
                        <span class="text-lg font-bold text-blue-600 mx-1">{{ $stores->total() }}</span>
                        <span class="text-sm text-gray-600">家店家</span>
                    </div>
                @else
                    <div class="flex items-center text-sm text-gray-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        沒有符合條件的店家
                    </div>
                @endif
            </div>

            <div class="flex space-x-3">
                <button
                    type="button"
                    onclick="clearFilters()"
                    class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 flex items-center"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    重設
                </button>
                <button
                    type="submit"
                    class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 border border-transparent rounded-xl hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 flex items-center shadow-lg hover:shadow-xl"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    套用篩選
                </button>
            </div>
        </div>
    </form>
</div>

<script>
// 台灣縣市區域資料
const taiwanCitiesAreas = {
    '台北市': ['中正區', '大同區', '中山區', '松山區', '大安區', '萬華區', '信義區', '士林區', '北投區', '內湖區', '南港區', '文山區'],
    '新北市': ['板橋區', '三重區', '中和區', '永和區', '新莊區', '新店區', '土城區', '蘆洲區', '樹林區', '鶯歌區', '三峽區', '淡水區', '汐止區', '瑞芳區', '五股區', '泰山區', '林口區', '深坑區', '石碇區', '坪林區', '三芝區', '石門區', '八里區', '平溪區', '雙溪區', '貢寮區', '金山区', '萬里區', '烏來區'],
    '桃園市': ['桃園區', '中壢區', '平鎮區', '八德區', '楊梅區', '蘆竹區', '大溪區', '龍潭區', '龜山區', '大園區', '觀音區', '新屋區'],
    '台中市': ['中西區', '東區', '南區', '西區', '北區', '北屯區', '西屯區', '南屯區', '太平區', '大里區', '霧峰區', '烏日區', '豐原區', '后里區', '石岡區', '東勢區', '和平區', '新社區', '潭子區', '大雅區', '神岡區', '大肚區', '沙鹿區', '龍井區', '梧棲區', '清水區', '大甲區', '外埔區', '大安區'],
    '台南市': ['中西區', '東區', '南區', '北區', '安平區', '安南區', '永康區', '歸仁區', '新化區', '左鎮區', '玉井區', '楠西區', '南化區', '仁德區', '關廟區', '龍崎區', '官田區', '麻豆區', '佳里區', '西港區', '七股區', '將軍區', '學甲區', '北門區', '新營區', '後壁區', '白河區', '東山區', '六甲區', '下營區', '柳營區', '鹽水區', '善化區', '大內區', '山上區', '新市區', '安定區'],
    '高雄市': ['楠梓區', '左營區', '鼓山區', '三民區', '鹽埕區', '前金區', '新興區', '苓雅區', '前鎮區', '旗津區', '小港區', '鳳山區', '林園區', '大寮區', '大樹區', '大社區', '仁武區', '鳥松區', '岡山區', '橋頭區', '燕巢區', '田寮區', '阿蓮區', '路竹區', '湖內區', '茄萣區', '永安區', '彌陀區', '梓官區', '旗山區', '美濃區', '六龜區', '甲仙區', '杉林區', '內門區', '茂林區', '桃源區', '那瑪夏區'],
    '基隆市': ['仁愛區', '信義區', '中正區', '中山區', '安樂區', '七堵區', '暖暖區', '中山区'],
    '新竹市': ['東區', '北區', '香山區'],
    '新竹縣': ['竹北市', '竹東鎮', '新埔鎮', '關西鎮', '湖口鄉', '新豐鄉', '芎林鄉', '橫山鄉', '北埔鄉', '寶山鄉', '峨眉鄉', '五峰鄉', '尖石鄉'],
    '嘉義市': ['東區', '西區'],
    '嘉義縣': ['太保市', '朴子市', '布袋鎮', '大林鎮', '民雄鄉', '溪口鄉', '新港鄉', '六腳鄉', '東石鄉', '義竹鄉', '鹿草鄉', '水上鄉', '中埔鄉', '竹崎鄉', '梅山鄉', '番路鄉', '大埔鄉', '阿里山鄉'],
    '宜蘭縣': ['宜蘭市', '羅東鎮', '蘇澳鎮', '頭城鎮', '礁溪鄉', '壯圍鄉', '員山鄉', '冬山鄉', '五結鄉', '三星鄉', '大同鄉', '南澳鄉'],
    '花蓮縣': ['花蓮市', '吉安鄉', '壽豐鄉', '秀林鄉', '玉里鎮', '新城鄉', '光復鄉', '豐濱鄉', '瑞穗鄉', '萬榮鄉', '鳳林鎮', '富里鄉', '卓溪鄉'],
    '台東縣': ['台東市', '成功鎮', '關山鎮', '卑南鄉', '鹿野鄉', '池上鄉', '東河鄉', '長濱鄉', '太麻里鄉', '大武鄉', '綠島鄉', '海端鄉', '延平鄉', '金峰鄉', '達仁鄉', '蘭嶼鄉'],
    '澎湖縣': ['馬公市', '湖西鄉', '白沙鄉', '西嶼鄉', '望安鄉', '七美鄉'],
    '金門縣': ['金城鎮', '金沙鎮', '金湖鎮', '金寧鄉', '烈嶼鄉', '烏坵鄉'],
    '連江縣': ['南竿鄉', '北竿鄉', '莒光鄉', '東引鄉']
};

// 更新區域選項
function updateAreas(city) {
    const areaSelect = document.getElementById('area-filter');

    if (!areaSelect) {
        return;
    }

    // 重置區域選項
    let optionsHtml = '<option value="">請選擇區域</option>';

    if (city && city.trim() !== '') {
        const areas = taiwanCitiesAreas[city];

        if (areas && Array.isArray(areas) && areas.length > 0) {
            areas.forEach(function(areaName) {
                optionsHtml += '<option value="' + areaName + '">' + areaName + '</option>';
            });
        } else {
            optionsHtml += '<option value="" disabled>此縣市暫無區域資料</option>';
        }
    }

    areaSelect.innerHTML = optionsHtml;

    // 更新 disabled 狀態
    areaSelect.disabled = !city || city.trim() === '';
}

// 篩選附近店家
async function filterNearbyStores() {
    const btn = document.getElementById('nearby-btn');
    const btnText = document.getElementById('nearby-btn-text');
    const statusDiv = document.getElementById('nearby-status');
    const originalText = btnText.textContent;

    // 檢查瀏覽器是否支援地理定位
    if (!navigator.geolocation) {
        showToast('您的瀏覽器不支援地理定位功能', 'error');
        return;
    }

    try {
        // 顯示載入狀態
        btn.disabled = true;
        btnText.textContent = '定位中...';
        statusDiv.classList.remove('hidden');

        // 取得位置
        const position = await new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(resolve, reject, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000 // 5分鐘內的快取位置
            });
        });

        const { latitude, longitude } = position.coords;

        // 更新 URL 參數
        const url = new URL(window.location);
        url.searchParams.delete('city');
        url.searchParams.delete('area');
        url.searchParams.set('nearby', 'true');
        url.searchParams.set('lat', latitude);
        url.searchParams.set('lng', longitude);

        window.location.href = url.toString();

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

    } finally {
        // 恢復按鈕狀態
        btn.disabled = false;
        btnText.textContent = originalText;
        statusDiv.classList.add('hidden');
    }
}

// 清除篩選條件
function clearFilters() {
    const form = document.querySelector('.filters-section form');
    const inputs = form.querySelectorAll('select');

    inputs.forEach(input => {
        input.value = '';
    });

    // 特別處理區域選項
    const areaSelect = document.getElementById('area-filter');
    areaSelect.disabled = true;
    areaSelect.innerHTML = '<option value="">請先選擇縣市</option>';

    // 提交表單
    form.submit();
}

// 初始化
document.addEventListener('DOMContentLoaded', function() {
    const citySelect = document.getElementById('city-filter');

    if (citySelect && citySelect.value) {
        updateAreas(citySelect.value);
    }
});
</script>