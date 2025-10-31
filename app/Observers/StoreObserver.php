<?php

namespace App\Observers;

use App\Models\Store;
use App\Services\StoreGeocodingService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class StoreObserver
{
    /**
     * Handle the Store "created" event.
     */
    public function created(Store $store): void
    {
        // 確保建立必要的媒體目錄
        $this->ensureMediaDirectories();

        // 自動進行地址定位
        $this->autoGeocodeStore($store, 'created');
    }

    /**
     * Handle the Store "updated" event.
     */
    public function updated(Store $store): void
    {
        // 清理媒體快取
        $this->clearMediaCache($store);

        // 檢查地址是否被修改
        if ($store->wasChanged(['address', 'city', 'area'])) {
            $this->autoGeocodeStore($store, 'updated');
        }

        // 如果經緯度被清空，且有地址，則重新定位
        if ($store->wasChanged(['latitude', 'longitude']) &&
            (empty($store->latitude) || empty($store->longitude) ||
             $store->latitude == 0 || $store->longitude == 0) &&
            !empty($store->address)) {
            $this->autoGeocodeStore($store, 'coordinates_cleared');
        }
    }

    /**
     * Handle the Store "deleted" event.
     */
    public function deleted(Store $store): void
    {
        // 清理相關的媒體檔案
        $this->cleanupStoreMedia($store);
    }

    /**
     * 確保媒體目錄存在
     */
    private function ensureMediaDirectories(): void
    {
        $directories = [
            storage_path('app/public/media'),
            storage_path('app/public/store-logos'),
            storage_path('app/public/store-covers'),
            storage_path('app/public/store-photos'),
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
        }
    }

    /**
     * 清理媒體快取
     */
    private function clearMediaCache(Store $store): void
    {
        try {
            // 清理媒體庫的快取
            $store->clearMediaCollection('store-logo');
            $store->clearMediaCollection('store-cover');
            $store->clearMediaCollection('store-photos');
        } catch (\Exception $e) {
            Log::error('清理媒體快取時發生錯誤', [
                'store_id' => $store->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 清理店家的媒體檔案
     */
    private function cleanupStoreMedia(Store $store): void
    {
        try {
            // 當店家被刪除時，Spatie Media Library 會自動處理媒體檔案
            // 這裡可以添加額外的清理邏輯
        } catch (\Exception $e) {
            Log::error('清理店家媒體時發生錯誤', [
                'store_id' => $store->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 自動為店家進行地址定位
     *
     * @param Store $store
     * @param string $event
     * @return void
     */
    private function autoGeocodeStore(Store $store, string $event): void
    {
        // 檢查是否需要進行地址定位
        if (!$this->shouldAutoGeocode($store)) {
            return;
        }

        try {
            // 使用非同步處理，避免阻塞主流程
            dispatch(function () use ($store, $event) {
                try {
                    $geocodingService = app(StoreGeocodingService::class);
                    $result = $geocodingService->geocodeStore($store);

                    if ($result['success']) {
                        Log::info('店家自動地址定位成功', [
                            'event' => $event,
                            'store_id' => $store->id,
                            'store_name' => $store->name,
                            'address' => $store->address,
                            'coordinates' => $result['data']
                        ]);
                    } else {
                        Log::warning('店家自動地址定位失敗', [
                            'event' => $event,
                            'store_id' => $store->id,
                            'store_name' => $store->name,
                            'address' => $store->address,
                            'error' => $result['message']
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('店家自動地址定位過程發生錯誤', [
                        'event' => $event,
                        'store_id' => $store->id,
                        'store_name' => $store->name,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            });

        } catch (\Exception $e) {
            Log::error('無法排程店家地址定位任務', [
                'event' => $event,
                'store_id' => $store->id,
                'store_name' => $store->name,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 判斷是否應該自動進行地址定位
     *
     * @param Store $store
     * @return bool
     */
    private function shouldAutoGeocode(Store $store): bool
    {
        // 檢查是否有地址
        if (empty($store->address)) {
            return false;
        }

        // 檢查是否已經有有效的經緯度
        if (!empty($store->latitude) && !empty($store->longitude) &&
            $store->latitude != 0 && $store->longitude != 0) {
            return false;
        }

        // 檢查是否啟用了自動地址定位功能
        if (!config('services.google.geocoding_api_key')) {
            return false;
        }

        // 檢查店家是否為活躍狀態
        if (!$store->is_active) {
            return false;
        }

        return true;
    }
}