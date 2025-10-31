<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 店家地址定位服務
 *
 * 提供店家地址經緯度自動填充功能
 */
class StoreGeocodingService
{
    /**
     * 為單一店家填充經緯度
     *
     * @param Store $store
     * @return array 結果包含 success, data, message
     */
    public function geocodeStore(Store $store): array
    {
        // 檢查店家是否有地址
        if (empty($store->address)) {
            return [
                'success' => false,
                'message' => '店家沒有地址資訊',
                'data' => null
            ];
        }

        // 檢查是否已經有經緯度
        if (!empty($store->latitude) && !empty($store->longitude)) {
            return [
                'success' => false,
                'message' => '店家已經有經緯度資訊',
                'data' => [
                    'latitude' => $store->latitude,
                    'longitude' => $store->longitude
                ]
            ];
        }

        // 組合完整地址
        $fullAddress = $this->buildFullAddress($store);

        try {
            // 使用 Google Geocoding API
            $result = $this->geocodeWithGoogle($fullAddress);

            if ($result) {
                // 更新店家經緯度
                $store->update([
                    'latitude' => $result['latitude'],
                    'longitude' => $result['longitude']
                ]);

                Log::info('店家地址定位成功', [
                    'store_id' => $store->id,
                    'store_name' => $store->name,
                    'address' => $fullAddress,
                    'coordinates' => $result
                ]);

                return [
                    'success' => true,
                    'message' => '地址定位成功',
                    'data' => array_merge($result, [
                        'store_id' => $store->id,
                        'store_name' => $store->name
                    ])
                ];
            }

            // 如果 Google API 失敗，嘗試其他服務
            $result = $this->geocodeWithNominatim($fullAddress);

            if ($result) {
                $store->update([
                    'latitude' => $result['latitude'],
                    'longitude' => $result['longitude']
                ]);

                Log::info('店家地址定位成功 (Nominatim)', [
                    'store_id' => $store->id,
                    'store_name' => $store->name,
                    'address' => $fullAddress,
                    'coordinates' => $result
                ]);

                return [
                    'success' => true,
                    'message' => '地址定位成功 (OpenStreetMap)',
                    'data' => array_merge($result, [
                        'store_id' => $store->id,
                        'store_name' => $store->name,
                        'source' => 'nominatim'
                    ])
                ];
            }

            return [
                'success' => false,
                'message' => '無法定位地址，請檢查地址是否正確',
                'data' => null
            ];

        } catch (\Exception $e) {
            Log::error('店家地址定位失敗', [
                'store_id' => $store->id,
                'store_name' => $store->name,
                'address' => $fullAddress,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => '地址定位失敗: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 批量處理需要定位的店家
     *
     * @param int|null $limit 限制處理數量
     * @return array 處理結果
     */
    public function batchGeocodeStores(?int $limit = null): array
    {
        // 找出有地址但沒有經緯度的店家
        $query = Store::whereNotNull('address')
            ->where('address', '!=', '')
            ->where(function ($query) {
                $query->whereNull('latitude')
                      ->orWhereNull('longitude')
                      ->orWhere('latitude', '=', 0)
                      ->orWhere('longitude', '=', 0);
            })
            ->where('is_active', true);

        if ($limit) {
            $query->limit($limit);
        }

        $stores = $query->get();

        $results = [
            'total' => $stores->count(),
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];

        foreach ($stores as $store) {
            $result = $this->geocodeStore($store);
            $results['processed']++;

            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
            }

            $results['details'][] = [
                'store_id' => $store->id,
                'store_name' => $store->name,
                'address' => $store->address,
                'result' => $result
            ];

            // 避免觸發 API 限制，每次請求間隔
            usleep(100000); // 0.1 秒
        }

        Log::info('批量店家地址定位完成', $results);

        return $results;
    }

    /**
     * 取得需要定位的店家統計
     *
     * @return array
     */
    public function getGeocodingStats(): array
    {
        $total = Store::count();
        $withAddress = Store::whereNotNull('address')
            ->where('address', '!=', '')
            ->count();

        $withCoordinates = Store::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0)
            ->where('longitude', '!=', 0)
            ->count();

        $needsGeocoding = Store::whereNotNull('address')
            ->where('address', '!=', '')
            ->where(function ($query) {
                $query->whereNull('latitude')
                      ->orWhereNull('longitude')
                      ->orWhere('latitude', '=', 0)
                      ->orWhere('longitude', '=', 0);
            })
            ->count();

        return [
            'total_stores' => $total,
            'stores_with_address' => $withAddress,
            'stores_with_coordinates' => $withCoordinates,
            'stores_needing_geocoding' => $needsGeocoding,
            'completion_rate' => $withAddress > 0 ? round(($withCoordinates / $withAddress) * 100, 2) : 0
        ];
    }

    /**
     * 建立完整地址
     *
     * @param Store $store
     * @return string
     */
    private function buildFullAddress(Store $store): string
    {
        $addressParts = [];

        // 如果有城市和區域，優先使用
        if (!empty($store->city)) {
            $addressParts[] = $store->city;
        }

        if (!empty($store->area)) {
            $addressParts[] = $store->area;
        }

        // 加入主要地址
        if (!empty($store->address)) {
            $addressParts[] = $store->address;
        }

        // 組合地址，並加上台灣
        $fullAddress = implode(', ', $addressParts);

        // 確保包含台灣以增加準確性
        if (!str_contains($fullAddress, '台灣') && !str_contains($fullAddress, 'Taiwan')) {
            $fullAddress .= ', 台灣';
        }

        return $fullAddress;
    }

    /**
     * 使用 Google Geocoding API
     *
     * @param string $address
     * @return array|null
     */
    private function geocodeWithGoogle(string $address): ?array
    {
        $apiKey = config('services.google.geocoding_api_key');

        if (!$apiKey) {
            Log::warning('Google Geocoding API Key 未設定');
            return null;
        }

        try {
            $response = Http::timeout(10)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $address,
                    'region' => 'TW',
                    'language' => 'zh-TW',
                    'key' => $apiKey
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    $result = $data['results'][0];
                    $location = $result['geometry']['location'];

                    return [
                        'latitude' => $location['lat'],
                        'longitude' => $location['lng'],
                        'formatted_address' => $result['formatted_address'],
                        'confidence' => 1.0, // Google API 通常很準確
                        'source' => 'google'
                    ];
                }
            }

            Log::warning('Google Geocoding API 失敗', [
                'address' => $address,
                'response' => $response->body()
            ]);

        } catch (\Exception $e) {
            Log::error('Google Geocoding API 錯誤', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * 使用 OpenStreetMap Nominatim API (備用)
     *
     * @param string $address
     * @return array|null
     */
    private function geocodeWithNominatim(string $address): ?array
    {
        try {
            $response = Http::timeout(10)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'tw',
                    'addressdetails' => 1,
                    'accept-language' => 'zh-TW,zh'
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
                    $result = $data[0];

                    return [
                        'latitude' => (float) $result['lat'],
                        'longitude' => (float) $result['lon'],
                        'formatted_address' => $result['display_name'] ?? $address,
                        'confidence' => 0.7, // Nominatim 準確度較低
                        'source' => 'nominatim'
                    ];
                }
            }

            Log::warning('Nominatim Geocoding API 失敗', [
                'address' => $address,
                'response' => $response->body()
            ]);

        } catch (\Exception $e) {
            Log::error('Nominatim Geocoding API 錯誤', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }
}