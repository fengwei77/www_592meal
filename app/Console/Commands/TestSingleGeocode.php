<?php

namespace App\Console\Commands;

use App\Services\AddressGeocodingService;
use Illuminate\Console\Command;

class TestSingleGeocode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-single-geocode';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '測試單一地址定位';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('測試單一地址定位...');

        $service = new AddressGeocodingService();

        $testAddress = 'Taipei 101';

        $this->line("測試地址: {$testAddress}");

        try {
            $result = $service->geocodeAddress($testAddress);

            if ($result) {
                $this->info('✅ 地址定位成功');
                $this->info('  緯度: ' . $result['latitude']);
                $this->info('  經度: ' . $result['longitude']);
                $this->info('  格式化地址: ' . $result['formatted_address']);
                $this->info('  來源: ' . $result['source']);
                $this->info('  準確度: ' . ($result['confidence'] * 100) . '%');
            } else {
                $this->error('❌ 地址定位失敗 - 返回 null');

                // 嘗試直接測試各個服務
                $this->line('--- 直接測試 Nominatim 服務 ---');
                try {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, 'https://nominatim.openstreetmap.org/search?q=' . urlencode($testAddress) . '&format=json&limit=1&countrycodes=tw');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    curl_setopt($ch, CURLOPT_USERAGENT, '592Meal Geocoding Service');
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $error = curl_error($ch);
                    curl_close($ch);

                    $this->line('Nominatim HTTP Code: ' . $httpCode);
                    $this->line('Nominatim Error: ' . $error);
                    $this->line('Nominatim Response length: ' . strlen($response));
                    if ($response) {
                        $data = json_decode($response, true);
                        $this->line('Nominatim Response: ' . substr($response, 0, 200) . '...');
                        if (!empty($data)) {
                            $this->info('✅ Nominatim 直接測試成功: ' . $data[0]['lat'] . ', ' . $data[0]['lon']);
                        } else {
                            $this->error('❌ Nominatim 直接測試: 無結果');
                        }
                    }
                } catch (\Exception $directError) {
                    $this->error('❌ Nominatim 直接測試失敗: ' . $directError->getMessage());
                }
            }
        } catch (\Exception $e) {
            $this->error('❌ 地址定位錯誤: ' . $e->getMessage());
            $this->error('錯誤追蹤: ' . $e->getTraceAsString());

            // 嘗試直接測試各個服務
            $this->line('--- 直接測試 Nominatim 服務 ---');
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://nominatim.openstreetmap.org/search?q=' . urlencode($testAddress) . '&format=json&limit=1&countrycodes=tw');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_USERAGENT, '592Meal Geocoding Service');
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                $this->line('Nominatim HTTP Code: ' . $httpCode);
                $this->line('Nominatim Error: ' . $error);
                if ($response) {
                    $data = json_decode($response, true);
                    if (!empty($data)) {
                        $this->info('✅ Nominatim 直接測試成功: ' . $data[0]['lat'] . ', ' . $data[0]['lon']);
                    } else {
                        $this->error('❌ Nominatim 直接測試: 無結果');
                    }
                }
            } catch (\Exception $directError) {
                $this->error('❌ Nominatim 直接測試失敗: ' . $directError->getMessage());
            }
        }

        return 0;
    }
}
