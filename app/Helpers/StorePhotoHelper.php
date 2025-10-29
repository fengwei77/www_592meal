<?php

namespace App\Helpers;

use App\Models\Store;

class StorePhotoHelper
{
    /**
     * 安全地取得店家照片，包含錯誤處理
     *
     * @param Store $store
     * @return array
     */
    public static function getStorePhotos(Store $store): array
    {
        try {
            $photos = $store->getMedia('store-photos');

            if ($photos->isEmpty()) {
                return [];
            }

            return $photos->map(function ($media) {
                $originalUrl = $media->getUrl();
                $thumbUrl = $media->getUrl('thumb');

                // 檢查檔案是否實際存在
                $originalExists = self::fileExistsInStorage($originalUrl);
                $thumbExists = self::fileExistsInStorage($thumbUrl);

                return [
                    'id' => $media->id,
                    'original_url' => $originalExists ? $originalUrl : asset('images/default-store-cover.jpg'),
                    'thumb_url' => $thumbExists ? $thumbUrl : asset('images/default-store-cover.jpg'),
                    'medium_url' => $media->getUrl('medium'),
                    'file_name' => $media->file_name,
                    'exists' => $originalExists,
                ];
            })->toArray();
        } catch (\Exception $e) {
            // 如果發生任何錯誤，返回空陣列
            return [];
        }
    }

    /**
     * 檢查檔案是否存在於 storage 中
     *
     * @param string $url
     * @return bool
     */
    private static function fileExistsInStorage(string $url): bool
    {
        try {
            // 如果 URL 包含 /storage/，轉換為實際路徑
            if (strpos($url, '/storage/') === 0) {
                $path = str_replace('/storage/', '', $url);
                $fullPath = storage_path('app/public/' . $path);
                return file_exists($fullPath);
            }

            // 檢查是否為完整的 URL
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                // 對於外部 URL，直接返回 true（假設可訪問）
                return true;
            }

            // 檢查相對路徑
            $fullPath = public_path($url);
            return file_exists($fullPath);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 取得店家主要照片（第一張或預設）
     *
     * @param Store $store
     * @return string
     */
    public static function getPrimaryPhoto(Store $store): string
    {
        $photos = self::getStorePhotos($store);

        if (!empty($photos)) {
            return $photos[0]['thumb_url'];
        }

        return asset('images/default-store-cover.jpg');
    }

    /**
     * 取得所有有效的店家照片 URL
     *
     * @param Store $store
     * @return array
     */
    public static function getValidPhotoUrls(Store $store): array
    {
        $photos = self::getStorePhotos($store);
        return array_filter(array_column($photos, 'thumb_url'));
    }
}