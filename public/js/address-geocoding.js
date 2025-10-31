/**
 * 592Meal - 地址地理編碼 JavaScript 服務
 *
 * 提供地址轉經緯度的前端功能
 * 支援自動偵測店家地址並標定坐標
 */

class AddressGeocodingService {
    constructor() {
        this.apiBaseUrl = '/api/stores';
        this.cache = new Map();
        this.batchSize = 5; // 批量處理的大小
        this.requestDelay = 200; // 請求延遲（毫秒）
    }

    /**
     * 地址地理編碼
     * @param {string} address - 要轉換的地址
     * @returns {Promise<Object>} - 地理編碼結果
     */
    async geocodeAddress(address) {
        if (!address || typeof address !== 'string') {
            throw new Error('地址必須是有效的字串');
        }

        // 檢查快取
        const cacheKey = `geocode_${address}`;
        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

        try {
            const response = await fetch(`${this.apiBaseUrl}/geocode`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({ address })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || '地理編碼失敗');
            }

            const result = await response.json();

            // 快取結果
            this.cache.set(cacheKey, result);

            return result;

        } catch (error) {
            console.error('地址地理編碼失敗:', error);
            throw error;
        }
    }

    /**
     * 批量地址地理編碼
     * @param {Array<string>} addresses - 要轉換的地址陣列
     * @returns {Promise<Array>} - 地理編碼結果陣列
     */
    async batchGeocodeAddresses(addresses) {
        if (!Array.isArray(addresses)) {
            throw new Error('地址必須是陣列');
        }

        if (addresses.length === 0) {
            return [];
        }

        try {
            const response = await fetch(`${this.apiBaseUrl}/batch-geocode`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({ addresses })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || '批量地理編碼失敗');
            }

            const result = await response.json();
            return result.data;

        } catch (error) {
            console.error('批量地址地理編碼失敗:', error);
            throw error;
        }
    }

    /**
     * 更新店家坐標
     * @param {number} storeId - 店家 ID
     * @param {number} latitude - 緯度
     * @param {number} longitude - 經度
     * @param {string} source - 坐標來源
     * @returns {Promise<Object>} - 更新結果
     */
    async updateStoreCoordinates(storeId, latitude, longitude, source = 'manual') {
        if (!storeId || !latitude || !longitude) {
            throw new Error('店家 ID、緯度和經度都是必需的');
        }

        try {
            const response = await fetch(`${this.apiBaseUrl}/${storeId}/coordinates`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({
                    latitude,
                    longitude,
                    source
                })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || '更新店家坐標失敗');
            }

            return await response.json();

        } catch (error) {
            console.error('更新店家坐標失敗:', error);
            throw error;
        }
    }

    /**
     * 自動地理編碼所有缺少坐標的店家
     * @returns {Promise<Object>} - 處理結果
     */
    async autoGeocodeStores() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/auto-geocode`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || '自動地理編碼失敗');
            }

            return await response.json();

        } catch (error) {
            console.error('自動地理編碼店家失敗:', error);
            throw error;
        }
    }

    /**
     * 獲取坐標統計資訊
     * @returns {Promise<Object>} - 統計資訊
     */
    async getCoordinatesStats() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/coordinates-stats`);

            if (!response.ok) {
                throw new Error('獲取坐標統計失敗');
            }

            return await response.json();

        } catch (error) {
            console.error('獲取坐標統計失敗:', error);
            throw error;
        }
    }

    /**
     * 驗證地址格式
     * @param {string} address - 要驗證的地址
     * @returns {boolean} - 是否為有效的台灣地址
     */
    validateAddress(address) {
        if (!address || typeof address !== 'string') {
            return false;
        }

        const taiwanKeywords = ['台灣', '臺灣', '縣', '市', '鄉', '鎮', '區', '村', '里', '路', '街', '巷', '號'];

        return taiwanKeywords.some(keyword => address.includes(keyword));
    }

    /**
     * 格式化地址顯示
     * @param {Object} geocodingResult - 地理編碼結果
     * @returns {string} - 格式化的地址字串
     */
    formatAddress(geocodingResult) {
        if (!geocodingResult || !geocodingResult.data) {
            return '地址不明';
        }

        const { latitude, longitude, formatted_address, confidence, source } = geocodingResult.data;

        let confidenceText = '';
        if (confidence >= 0.9) {
            confidenceText = ' (高精度)';
        } else if (confidence >= 0.7) {
            confidenceText = ' (中等精度)';
        } else {
            confidenceText = ' (低精度)';
        }

        const sourceText = source === 'google' ? 'Google Maps' :
                           source === 'nominatim' ? 'OpenStreetMap' :
                           source === 'tgos' ? '台灣政府資料' : '未知來源';

        return `${formatted_address || '地址已定位'}${confidenceText} (${sourceText})`;
    }

    /**
     * 顯示地理編碼結果的訊息
     * @param {Object} result - 地理編碼結果
     * @param {string} type - 訊息類型 (success, error, info)
     */
    showGeocodingMessage(result, type = 'info') {
        const messageElement = document.getElementById('geocoding-message');
        if (!messageElement) return;

        if (type === 'success') {
            messageElement.className = 'alert alert-success';
            messageElement.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                ${this.formatAddress(result)}
            `;
        } else if (type === 'error') {
            messageElement.className = 'alert alert-danger';
            messageElement.innerHTML = `
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${result.message || '地址定位失敗'}
            `;
        } else {
            messageElement.className = 'alert alert-info';
            messageElement.innerHTML = `
                <i class="fas fa-info-circle me-2"></i>
                正在處理地址定位...
            `;
        }

        messageElement.style.display = 'block';

        // 5秒後自動隱藏成功訊息
        if (type === 'success') {
            setTimeout(() => {
                messageElement.style.display = 'none';
            }, 5000);
        }
    }

    /**
     * 處理地圖載入店家時的地址定位
     * @param {Array} stores - 店家資料陣列
     * @param {Object} map - Leaflet 地圖實例
     * @returns {Promise<Array>} - 處理後的店家資料
     */
    async processMapStoresWithGeocoding(stores, map) {
        // 找出需要地理編碼的店家
        const storesNeedingGeocoding = stores.filter(store =>
            store.location_status === 'address_only' && store.can_be_geocoded
        );

        if (storesNeedingGeocoding.length === 0) {
            return stores;
        }

        console.log(`發現 ${storesNeedingGeocoding.length} 家需要地址定位的店家`);

        // 批量處理地址地理編碼
        const addresses = storesNeedingGeocoding.map(store => store.full_address);
        const geocodingResults = await this.batchGeocodeAddresses(addresses);

        // 更新店家坐標
        const updatedStores = [];

        for (let i = 0; i < storesNeedingGeocoding.length; i++) {
            const store = storesNeedingGeocoding[i];
            const geocodingResult = geocodingResults[i];

            if (geocodingResult.success && geocodingResult.data) {
                try {
                    // 更新店家坐標
                    await this.updateStoreCoordinates(
                        store.id,
                        geocodingResult.data.latitude,
                        geocodingResult.data.longitude,
                        geocodingResult.data.source
                    );

                    // 更新本地店家資料
                    store.latitude = geocodingResult.data.latitude;
                    store.longitude = geocodingResult.data.longitude;
                    store.location_status = 'has_coordinates';
                    store.coordinate_info.has_coordinates = true;
                    store.coordinate_info.latitude = geocodingResult.data.latitude;
                    store.coordinate_info.longitude = geocodingResult.data.longitude;

                    updatedStores.push(store);

                    console.log(`店家 "${store.name}" 地址定位成功:`, geocodingResult.data);

                } catch (error) {
                    console.error(`更新店家 "${store.name}" 坐標失敗:`, error);
                }
            }
        }

        return updatedStores;
    }

    /**
     * 為地圖添加地址定位控制按鈕
     * @param {Object} map - Leaflet 地圖實例
     */
    addGeocodingControl(map) {
        const geocodingControl = L.control({position: 'topright'});

        geocodingControl.onAdd = function(map) {
            const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');

            div.innerHTML = `
                <button id="geocode-stores-btn"
                        class="btn btn-sm btn-primary"
                        title="自動定位店家地址"
                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    定位地址
                </button>
            `;

            // 防止點擊地圖時觸發按鈕
            L.DomEvent.disableClickPropagation(div);

            return div;
        };

        geocodingControl.addTo(map);

        // 添加點擊事件
        document.getElementById('geocode-stores-btn')?.addEventListener('click', async () => {
            await this.performAutoGeocoding();
        });
    }

    /**
     * 執行自動地理編碼
     */
    async performAutoGeocoding() {
        const button = document.getElementById('geocode-stores-btn');
        const originalText = button.innerHTML;

        try {
            // 顯示處理中狀態
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>定位中...';
            this.showGeocodingMessage(null, 'info');

            // 執行自動地理編碼
            const result = await this.autoGeocodeStores();

            if (result && result.success) {
                // 安全地獲取統計數據，避免解構錯誤
                const data = result.data || {};
                const processed = data.processed || 0;
                const updated = data.updated || 0;
                const failed = data.failed || 0;

                if (updated > 0) {
                    this.showGeocodingMessage(result, 'success');

                    // 重新載入地圖以顯示新定位的店家
                    if (window.state && window.state.loadMapStores) {
                        await window.state.loadMapStores();
                    }
                } else {
                    this.showGeocodingMessage({
                        message: '沒有找到需要定位的店家'
                    }, 'info');
                }

                console.log('自動地理編碼完成:', result.data);

            } else {
                this.showGeocodingMessage(result, 'error');
            }

        } catch (error) {
            console.error('自動地理編碼失敗:', error);
            this.showGeocodingMessage({
                message: '自動地理編碼失敗，請稍後再試'
            }, 'error');
        } finally {
            // 恢復按鈕狀態
            button.disabled = false;
            button.innerHTML = originalText;
        }
    }

    /**
     * 顯示坐標統計資訊
     */
    async showCoordinatesStats() {
        try {
            const stats = await this.getCoordinatesStats();

            if (stats && stats.success && stats.data) {
                const data = stats.data;
                const statusText = data.coverage_status === 'excellent' ? '優秀' :
                                  data.coverage_status === 'good' ? '良好' :
                                  data.coverage_status === 'fair' ? '一般' : '待改善';

                const message = `
                    <div class="coordinates-stats">
                        <h6>店家坐標統計</h6>
                        <div class="stats-row">
                            <span>總店家數:</span>
                            <span class="badge bg-primary">${data.total_stores}</span>
                        </div>
                        <div class="stats-row">
                            <span>已有坐標:</span>
                            <span class="badge bg-success">${data.stores_with_coordinates}</span>
                        </div>
                        <div class="stats-row">
                            <span>需要定位:</span>
                            <span class="badge bg-warning">${data.stores_needing_geocoding}</span>
                        </div>
                        <div class="stats-row">
                            <span>覆蓋率:</span>
                            <span class="badge ${data.coverage_percentage >= 90 ? 'bg-success' : data.coverage_percentage >= 70 ? 'bg-info' : 'bg-warning'}">${data.coverage_percentage}%</span>
                        </div>
                        <div class="stats-row">
                            <span>狀態:</span>
                            <span class="badge ${data.coverage_status === 'excellent' ? 'bg-success' : data.coverage_status === 'good' ? 'bg-info' : 'bg-warning'}">${statusText}</span>
                        </div>
                    </div>
                `;

                // 在適當的位置顯示統計資訊
                const statsContainer = document.getElementById('coordinates-stats-container');
                if (statsContainer) {
                    statsContainer.innerHTML = message;
                    statsContainer.style.display = 'block';
                }

                return data;
            }
        } catch (error) {
            console.error('獲取坐標統計失敗:', error);
        }
    }
}

// 初始化全域地址地理編碼服務
window.addressGeocodingService = new AddressGeocodingService();

// 在地圖載入完成後添加相關控制（僅在店家地圖頁面）
document.addEventListener('DOMContentLoaded', function() {
    // 檢查是否在地圖頁面
    if (window.location && window.location.pathname && window.location.pathname.includes('stores')) {
        setTimeout(() => {
            try {
                if (window.state && window.state.map && window.addressGeocodingService) {
                    window.addressGeocodingService.addGeocodingControl(window.state.map);
                    console.log('✅ 地址定位控制已自動添加到地圖');
                }
            } catch (error) {
                console.error('❌ 添加地址定位控制失敗:', error);
            }
        }, 1500);
    }
});