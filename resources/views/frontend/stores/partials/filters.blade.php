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

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
                >
                    <option value="">請先選擇縣市</option>
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
// 更新區域選項
function updateAreas(city) {
    const areaSelect = document.getElementById('area-filter');
    const currentArea = '{{ request("area") }}';

    // 重置區域選項
    areaSelect.innerHTML = '<option value="">請選擇區域</option>';

    if (!city) {
        areaSelect.disabled = true;
        return;
    }

    areaSelect.disabled = false;

    // 載入該縣市的區域
    fetch('/api/stores/filters')
        .then(response => response.json())
        .then(data => {
            // 這裡可以添加邏輯來根據縣市過濾區域
            // 目前顯示所有區域
            const areas = data.areas || [];

            areas.forEach(area => {
                const option = document.createElement('option');
                option.value = area;
                option.textContent = area;
                if (area === currentArea) {
                    option.selected = true;
                }
                areaSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('載入區域失敗:', error);
        });
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
    // 如果有選擇縣市，初始化區域選項
    const citySelect = document.getElementById('city-filter');
    if (citySelect.value) {
        updateAreas(citySelect.value);
    }
});
</script>