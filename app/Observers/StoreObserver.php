<?php

namespace App\Observers;

use App\Models\Store;
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
    }

    /**
     * Handle the Store "updated" event.
     */
    public function updated(Store $store): void
    {
        // 清理媒體快取
        $this->clearMediaCache($store);
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
}