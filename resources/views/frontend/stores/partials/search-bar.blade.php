<!-- 店家搜尋組件 -->
<div class="search-section bg-gradient-to-br from-blue-600 via-purple-600 to-pink-500 rounded-2xl shadow-xl p-8 mb-8 relative overflow-hidden">
    <!-- 背景裝飾 -->
    <div class="absolute inset-0 bg-black opacity-5"></div>
    <div class="absolute top-0 right-0 w-64 h-64 bg-white opacity-10 rounded-full -translate-y-32 translate-x-32"></div>
    <div class="absolute bottom-0 left-0 w-48 h-48 bg-white opacity-10 rounded-full translate-y-24 -translate-x-24"></div>

    <div class="relative max-w-4xl mx-auto">
        <!-- 搜尋標題 -->
        <div class="text-center mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-3">
                探索美食店家
            </h1>
            <p class="text-lg text-white text-opacity-90">
                發現您附近的精選餐廳，享受美味的用餐體驗
            </p>
        </div>

        <form method="GET" action="{{ route('frontend.stores.index') }}" class="relative">
            @if(request('city') || request('area') || request('type'))
                @if(request('city'))<input type="hidden" name="city" value="{{ request('city') }}">@endif
                @if(request('area'))<input type="hidden" name="area" value="{{ request('area') }}">@endif
                @if(request('type'))<input type="hidden" name="type" value="{{ request('type') }}">@endif
            @endif

            <!-- 搜尋輸入框 -->
            <div class="relative">
                <div class="flex bg-white rounded-2xl shadow-2xl overflow-hidden">
                    <div class="relative flex-1">
                        <input
                            type="text"
                            name="search"
                            id="search-input"
                            class="w-full px-6 py-5 pr-14 text-lg border-0 focus:outline-none focus:ring-0"
                            placeholder="搜尋店家名稱、菜系或地址..."
                            value="{{ request('search') }}"
                            autocomplete="off"
                            onkeyup="handleSearchInput(event)"
                            onfocus="showSuggestions()"
                            onblur="hideSuggestionsDelayed()"
                        >

                        <!-- 搜尋圖示 -->
                        <div class="absolute inset-y-0 right-0 flex items-center pr-5 pointer-events-none">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>

                        <!-- 搜尋建議下拉 -->
                        <div id="search-suggestions" class="absolute top-full left-0 right-0 z-50 mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 hidden overflow-hidden">
                            <div class="max-h-80 overflow-y-auto">
                                <!-- 載入狀態 -->
                                <div id="suggestions-loading" class="hidden py-6 text-center text-gray-500">
                                    <svg class="animate-spin h-6 w-6 mx-auto mb-3" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <div class="text-sm">正在搜尋店家...</div>
                                </div>

                                <!-- 建議列表 -->
                                <div id="suggestions-list" class="divide-y divide-gray-100">
                                    <!-- 搜尋建議將動態插入這裡 -->
                                </div>

                                <!-- 空狀態 -->
                                <div id="suggestions-empty" class="hidden py-8 text-center">
                                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <div class="text-gray-600 font-medium mb-1">找不到相關店家</div>
                                    <div class="text-sm text-gray-500">試試其他關鍵字</div>
                                </div>

                                <!-- 查看所有結果 -->
                                <div id="suggestions-footer" class="hidden p-3 bg-gray-50 border-t border-gray-100">
                                    <button
                                        type="submit"
                                        class="w-full flex items-center justify-center px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded-lg transition-colors duration-200"
                                    >
                                        <span>查看所有搜尋結果</span>
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="px-8 py-5 bg-gradient-to-r from-blue-500 to-purple-600 text-white font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-200 flex items-center space-x-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <span>搜尋店家</span>
                    </button>
                </div>
            </div>
        </form>

        <!-- 熱門搜尋標籤 -->
        <div class="mt-8">
            <div class="flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-white text-opacity-70 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M13.5 67.5c0-1.886-1.527-3.414-3.414-3.414S6.672 65.614 6.672 67.5s1.527 3.414 3.414 3.414 3.414-1.527 3.414-3.414zm0-10.286c0-1.886-1.527-3.414-3.414-3.414s-3.414 1.527-3.414 3.414 1.527 3.414 3.414 3.414 3.414-1.527 3.414-3.414zM24 57.214c0-1.886-1.527-3.414-3.414-3.414s-3.414 1.527-3.414 3.414 1.527 3.414 3.414 3.414 3.414-1.527 3.414-3.414z"/>
                </svg>
                <span class="text-white text-opacity-90 font-medium">熱門搜尋</span>
            </div>
            <div class="flex flex-wrap justify-center gap-3">
                <button onclick="quickSearch('早餐')" class="px-4 py-2 bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-900 text-sm rounded-full transition-all duration-200 hover:scale-105 shadow-md hover:shadow-lg">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    早餐
                </button>
                <button onclick="quickSearch('咖啡')" class="px-4 py-2 bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-900 text-sm rounded-full transition-all duration-200 hover:scale-105 shadow-md hover:shadow-lg">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6h-3a1 1 0 00-1 1v8a1 1 0 001 1h3a1 1 0 001-1V7a1 1 0 00-1-1zM7 6h8M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2M9 6v10a1 1 0 001 1h4a1 1 0 001-1V6"></path>
                    </svg>
                    咖啡
                </button>
                <button onclick="quickSearch('便當')" class="px-4 py-2 bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-900 text-sm rounded-full transition-all duration-200 hover:scale-105 shadow-md hover:shadow-lg">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    便當
                </button>
                <button onclick="quickSearch('小吃')" class="px-4 py-2 bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-900 text-sm rounded-full transition-all duration-200 hover:scale-105 shadow-md hover:shadow-lg">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    小吃
                </button>
                <button onclick="quickSearch('麵食')" class="px-4 py-2 bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-900 text-sm rounded-full transition-all duration-200 hover:scale-105 shadow-md hover:shadow-lg">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    麵食
                </button>
                <button onclick="quickSearch('飲料')" class="px-4 py-2 bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-900 text-sm rounded-full transition-all duration-200 hover:scale-105 shadow-md hover:shadow-lg">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    飲料
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let searchTimeout;
let currentSuggestions = [];

// 處理搜尋輸入
function handleSearchInput(event) {
    const query = event.target.value.trim();

    // 清除之前的計時器
    clearTimeout(searchTimeout);

    if (query.length < 2) {
        hideSuggestions();
        return;
    }

    // 延遲 300ms 後執行搜尋
    searchTimeout = setTimeout(() => {
        fetchSuggestions(query);
    }, 300);
}

// 獲取搜尋建議
async function fetchSuggestions(query) {
    const suggestionsContainer = document.getElementById('search-suggestions');
    const loadingElement = document.getElementById('suggestions-loading');
    const listElement = document.getElementById('suggestions-list');
    const emptyElement = document.getElementById('suggestions-empty');

    // 顯示載入狀態
    loadingElement.classList.remove('hidden');
    emptyElement.classList.add('hidden');
    listElement.innerHTML = '';

    try {
        const response = await fetch(`/api/stores/search/suggestions?q=${encodeURIComponent(query)}`);
        const data = await response.json();

        loadingElement.classList.add('hidden');
        currentSuggestions = data.suggestions || [];

        if (currentSuggestions.length > 0) {
            // 顯示建議列表
            currentSuggestions.forEach((suggestion, index) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'w-full text-left hover:bg-blue-50 transition-colors duration-200 border-b border-gray-100 last:border-b-0';
                item.onclick = () => selectSuggestion(suggestion);

                item.innerHTML = `
                    <div class="flex items-center space-x-4 px-4 py-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-50 to-purple-50 rounded-xl flex items-center justify-center flex-shrink-0">
                            ${getStoreIcon(suggestion.type)}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">
                                ${highlightMatch(suggestion.name, query)}
                            </div>
                            <div class="text-sm text-gray-500 flex items-center mt-1">
                                <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                ${suggestion.location}
                                <span class="mx-2 text-gray-300">•</span>
                                ${suggestion.type_label}
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                `;

                // 鍵盤導航
                item.setAttribute('data-index', index);
                item.onmouseenter = () => highlightSuggestion(index);
                item.onmouseleave = () => unhighlightSuggestion(index);

                listElement.appendChild(item);
            });
        } else {
            // 顯示空狀態
            emptyElement.classList.remove('hidden');
        }
    } catch (error) {
        console.error('獲取搜尋建議失敗:', error);
        loadingElement.classList.add('hidden');
        emptyElement.classList.remove('hidden');
    }
}

// 選擇搜尋建議
function selectSuggestion(suggestion) {
    const searchInput = document.getElementById('search-input');
    searchInput.value = suggestion.name;
    hideSuggestions();
    searchInput.closest('form').submit();
}

// 顯示搜尋建議
function showSuggestions() {
    const searchInput = document.getElementById('search-input');
    if (searchInput.value.trim().length >= 2) {
        document.getElementById('search-suggestions').classList.remove('hidden');
    }
}

// 隱藏搜尋建議
function hideSuggestions() {
    document.getElementById('search-suggestions').classList.add('hidden');
}

// 延遲隱藏（讓點擊建議的動作先執行）
function hideSuggestionsDelayed() {
    setTimeout(hideSuggestions, 200);
}

// 高亮比對文字
function highlightMatch(text, query) {
    const regex = new RegExp(`(${query})`, 'gi');
    return text.replace(regex, '<mark class="bg-yellow-200">$1</mark>');
}

// 取得店家類型圖示
function getStoreIcon(type) {
    const icons = {
        'restaurant': '<svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>',
        'cafe': '<svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6h-3a1 1 0 00-1 1v8a1 1 0 001 1h3a1 1 0 001-1V7a1 1 0 00-1-1zM7 6h8M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2M9 6v10a1 1 0 001 1h4a1 1 0 001-1V6" /></svg>',
        'snack': '<svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>',
        'bar': '<svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>',
        'bakery': '<svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>',
        'other': '<svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>'
    };
    return icons[type] || icons['other'];
}

// 快速搜尋
function quickSearch(keyword) {
    const searchInput = document.getElementById('search-input');
    searchInput.value = keyword;
    searchInput.closest('form').submit();
}

// 鍵盤導航支援
document.getElementById('search-input').addEventListener('keydown', function(event) {
    const suggestions = document.querySelectorAll('#suggestions-list button');

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        // 移動到下一個建議
        const highlighted = document.querySelector('#suggestions-list button.bg-blue-50');
        const currentIndex = highlighted ? parseInt(highlighted.getAttribute('data-index')) : -1;
        const nextIndex = Math.min(currentIndex + 1, suggestions.length - 1);
        highlightSuggestion(nextIndex);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        // 移動到上一個建議
        const highlighted = document.querySelector('#suggestions-list button.bg-blue-50');
        const currentIndex = highlighted ? parseInt(highlighted.getAttribute('data-index')) : suggestions.length;
        const prevIndex = Math.max(currentIndex - 1, 0);
        highlightSuggestion(prevIndex);
    } else if (event.key === 'Enter') {
        event.preventDefault();
        // 選擇高亮的建議
        const highlighted = document.querySelector('#suggestions-list button.bg-blue-50');
        if (highlighted) {
            highlighted.click();
        } else {
            this.closest('form').submit();
        }
    } else if (event.key === 'Escape') {
        hideSuggestions();
    }
});

// 高亮搜尋建議
function highlightSuggestion(index) {
    const suggestions = document.querySelectorAll('#suggestions-list button');
    suggestions.forEach((suggestion, i) => {
        if (i === index) {
            suggestion.classList.add('bg-blue-50');
        } else {
            suggestion.classList.remove('bg-blue-50');
        }
    });
}

// 取消高亮搜尋建議
function unhighlightSuggestion(index) {
    const suggestions = document.querySelectorAll('#suggestions-list button');
    suggestions.forEach((suggestion, i) => {
        if (i === index) {
            suggestion.classList.remove('bg-blue-50');
        }
    });
}

// 點擊外部關閉建議
document.addEventListener('click', function(event) {
    const searchContainer = document.querySelector('.search-section');
    if (!searchContainer.contains(event.target)) {
        hideSuggestions();
    }
});
</script>