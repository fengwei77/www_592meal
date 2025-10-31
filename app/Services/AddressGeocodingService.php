<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * 地址定位服務
 *
 * 將地址轉換為經緯度坐標
 * 支援多種地理編碼服務
 */
class AddressGeocodingService
{
    /**
     * 使用 OpenStreetMap Nominatim API (免費)
     */
    private function geocodeWithNominatim(string $address): ?array
    {
        try {
            // 準備請求參數
            $params = [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
                'countrycodes' => 'tw', // 限制在台灣
                'addressdetails' => 1,
                'accept-language' => 'zh-TW,zh'
            ];

            // 使用 cURL 而不是 Laravel HTTP Client 以確保更好的相容性
            $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query($params);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, '592Meal Geocoding Service (contact@example.com)');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::warning('Nominatim cURL 錯誤', [
                    'address' => $address,
                    'error' => $error
                ]);
                return null;
            }

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);

                if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
                    $result = $data[0];

                    Log::info('Nominatim 地理編碼成功', [
                        'address' => $address,
                        'result' => $result
                    ]);

                    return [
                        'latitude' => (float) $result['lat'],
                        'longitude' => (float) $result['lon'],
                        'formatted_address' => $result['display_name'] ?? $address,
                        'confidence' => $result['importance'] ?? 0.5,
                        'source' => 'nominatim'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Nominatim 地理編碼失敗', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * 使用 Google Geocoding API (需要 API Key)
     */
    private function geocodeWithGoogle(string $address): ?array
    {
        $apiKey = config('services.google.geocoding_api_key');

        if (!$apiKey) {
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
        } catch (\Exception $e) {
            Log::warning('Google 地理編碼失敗', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * 使用台灣政府開放資料 (TWD) 地址定位服務
     */
    private function geocodeWithTaiwanGovernment(string $address): ?array
    {
        try {
            // 移除台灣地址中的常見前綴
            $cleanAddress = preg_replace('/^(台灣|臺灣|Taiwan)/i', '', $address);
            $cleanAddress = trim($cleanAddress);

            // 使用行政院開放平台 TWD 地址定位服務
            $response = Http::timeout(10)
                ->get('https://api.tgos.tw/TGOS_Map/tgos', [
                    'format' => 'json',
                    'address' => $cleanAddress,
                    'srs' => 'EPSG:4326' // WGS84 坐標系統
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['result']) && isset($data['result']['x']) && isset($data['result']['y'])) {
                    return [
                        'latitude' => (float) $data['result']['y'],
                        'longitude' => (float) $data['result']['x'],
                        'formatted_address' => $address,
                        'confidence' => 0.8, // 台灣政府資料通常較準確
                        'source' => 'tgos'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('TGOs 地理編碼失敗', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * 主要地理編碼方法
     * 按優先順序嘗試不同的服務
     */
    public function geocodeAddress(string $address): ?array
    {
        if (empty($address)) {
            return null;
        }

        // 檢查快取
        $cacheKey = 'geocode:' . md5($address);
        $cached = Cache::get($cacheKey);

        if ($cached) {
            return $cached;
        }

        Log::info('開始地址地理編碼', ['address' => $address]);

        // 1. 首先嘗試 Google API (如果有的話)
        $result = $this->geocodeWithGoogle($address);

        // 2. 如果 Google 失敗，嘗試台灣政府服務
        if (!$result) {
            $result = $this->geocodeWithTaiwanGovernment($address);
        }

        // 3. 最後嘗試 OpenStreetMap Nominatim
        if (!$result) {
            $result = $this->geocodeWithNominatim($address);
        }

        if ($result) {
            // 快取結果 24 小時
            Cache::put($cacheKey, $result, now()->addHours(24));

            Log::info('地址地理編碼成功', [
                'address' => $address,
                'result' => $result
            ]);
        } else {
            Log::warning('地址地理編碼失敗', ['address' => $address]);
        }

        return $result;
    }

    /**
     * 批量地理編碼多個地址
     */
    public function geocodeMultipleAddresses(array $addresses): array
    {
        $results = [];

        foreach ($addresses as $address) {
            $results[$address] = $this->geocodeAddress($address);

            // 添加延遲避免 API 限制
            usleep(100000); // 0.1 秒
        }

        return $results;
    }

    /**
     * 反向地理編碼：從經緯度獲取地址
     */
    public function reverseGeocode(float $latitude, float $longitude): ?string
    {
        try {
            $response = Http::timeout(10)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'format' => 'json',
                    'accept-language' => 'zh-TW,zh'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['display_name'] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning('反向地理編碼失敗', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * 驗證地址格式
     */
    public function validateAddress(string $address): bool
    {
        if (empty($address)) {
            return false;
        }

        // 檢查是否包含台灣地址的關鍵字
        $taiwanKeywords = ['台灣', '臺灣', '縣', '市', '鄉', '鎮', '區', '村', '里', '路', '街', '巷', '號'];

        foreach ($taiwanKeywords as $keyword) {
            if (strpos($address, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 清理和標準化地址
     */
    public function normalizeAddress(string $address): string
    {
        // 移除多餘的空白
        $address = preg_replace('/\s+/', ' ', trim($address));

        // 統一為繁體中文
        $address = str_replace([
            '台湾', '台灣', '臺灣',
            '县', '縣',
            '市', '巿',
            '乡', '鄉',
            '镇', '鎮',
            '区', '區',
            '村', '里',
            '路', '道路',
            '街', '街道',
            '巷', '弄',
            '号', '號'
        ], [
            '台灣', '台灣', '台灣',
            '縣', '縣',
            '市', '市',
            '鄉', '鄉',
            '鎮', '鎮',
            '區', '區',
            '村', '里',
            '路', '路',
            '街', '街',
            '巷', '巷',
            '號', '號'
        ], $address);

        return $address;
    }
}