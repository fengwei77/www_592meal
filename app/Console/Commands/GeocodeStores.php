<?php

namespace App\Console\Commands;

use App\Services\StoreGeocodingService;
use Illuminate\Console\Command;

/**
 * 店家地址定位命令
 *
 * 為沒有經緯度的店家自動填充坐標資訊
 */
class GeocodeStores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stores:geocode
                            {--limit= : 限制處理的店家數量}
                            {--stats : 只顯示統計資訊，不執行定位}
                            {--store-id= : 指定單一店家 ID 進行定位}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '為沒有經緯度的店家自動填充坐標資訊';

    /**
     * Execute the console command.
     */
    public function handle(StoreGeocodingService $geocodingService): int
    {
        $this->info('🗺️ 店家地址定位服務');
        $this->info('==================');

        // 檢查 Google Geocoding API 設定
        $apiKey = config('services.google.geocoding_api_key');
        if (!$apiKey) {
            $this->warn('⚠️ Google Geocoding API Key 未設定');
            $this->info('請在 .env 檔案中設定 GOOGLE_GEOCODING_API_KEY');
            $this->info('或在 Google Cloud Console 中啟用 Geocoding API');
        } else {
            $this->info('✅ Google Geocoding API Key 已設定');
        }

        // 顯示統計資訊
        $stats = $geocodingService->getGeocodingStats();
        $this->displayStats($stats);

        // 如果只顯示統計，就結束
        if ($this->option('stats')) {
            return 0;
        }

        // 指定單一店家
        if ($storeId = $this->option('store-id')) {
            return $this->geocodeSingleStore($storeId, $geocodingService);
        }

        // 批量處理
        $limit = $this->option('limit');
        return $this->batchGeocode($limit, $geocodingService);
    }

    /**
     * 顯示統計資訊
     */
    private function displayStats(array $stats): void
    {
        $this->info('');
        $this->info('📊 店家坐標統計:');
        $this->info('總店家數: ' . $stats['total_stores']);
        $this->info('有地址的店家: ' . $stats['stores_with_address']);
        $this->info('已有坐標的店家: ' . $stats['stores_with_coordinates']);
        $this->info('需要定位的店家: ' . $stats['stores_needing_geocoding']);

        if ($stats['stores_with_address'] > 0) {
            $this->info('完成率: ' . $stats['completion_rate'] . '%');
        }

        // 顯示進度條
        if ($stats['stores_with_address'] > 0) {
            $progress = ($stats['stores_with_coordinates'] / $stats['stores_with_address']) * 100;
            $barLength = 50;
            $filledLength = intval(($progress / 100) * $barLength);
            $bar = str_repeat('█', $filledLength) . str_repeat('░', $barLength - $filledLength);

            $this->info('');
            $this->info('📈 進度: [' . $bar . '] ' . number_format($progress, 1) . '%');
        }
    }

    /**
     * 定位單一店家
     */
    private function geocodeSingleStore(int $storeId, StoreGeocodingService $geocodingService): int
    {
        $store = \App\Models\Store::find($storeId);

        if (!$store) {
            $this->error('找不到店家 ID: ' . $storeId);
            return 1;
        }

        $this->info('');
        $this->info('🎯 定位單一店家: ' . $store->name);
        $this->info('地址: ' . $store->address);

        $this->info('正在定位...');

        $result = $geocodingService->geocodeStore($store);

        if ($result['success']) {
            $this->info('✅ ' . $result['message']);
            $this->info('坐標: ' . $result['data']['latitude'] . ', ' . $result['data']['longitude']);

            if (isset($result['data']['source'])) {
                $this->info('來源: ' . ($result['data']['source'] === 'google' ? 'Google Maps' : 'OpenStreetMap'));
            }

            return 0;
        } else {
            $this->error('❌ ' . $result['message']);
            return 1;
        }
    }

    /**
     * 批量定位店家
     */
    private function batchGeocode(?int $limit, StoreGeocodingService $geocodingService): int
    {
        if ($limit) {
            $this->info('');
            $this->info('🚀 開始批量定位 (限制 ' . $limit . ' 家)...');
        } else {
            $this->info('');
            $this->info('🚀 開始批量定位所有需要定位的店家...');
        }

        $this->info('');

        // 建立進度條
        $stats = $geocodingService->getGeocodingStats();
        $totalToProcess = $limit ? min($limit, $stats['stores_needing_geocoding']) : $stats['stores_needing_geocoding'];

        if ($totalToProcess === 0) {
            $this->info('✅ 沒有需要定位的店家！');
            return 0;
        }

        $progressBar = $this->output->createProgressBar($totalToProcess);
        $progressBar->start();

        // 執行批量定位
        $results = $geocodingService->batchGeocodeStores($limit);

        $progressBar->finish();
        $this->info('');

        // 顯示結果
        $this->info('');
        $this->info('📋 處理結果:');
        $this->info('總共處理: ' . $results['processed'] . ' 家');
        $this->info('成功定位: ' . $results['success'] . ' 家');
        $this->info('定位失敗: ' . $results['failed'] . ' 家');

        // 顯示詳細結果
        if ($this->output->isVerbose()) {
            $this->info('');
            $this->info('📝 詳細結果:');

            foreach ($results['details'] as $detail) {
                $status = $detail['result']['success'] ? '✅' : '❌';
                $message = $detail['result']['message'];

                $this->info($status . ' ' . $detail['store_name'] . ': ' . $message);
            }
        }

        // 顯示失敗的店家
        if ($results['failed'] > 0) {
            $this->info('');
            $this->warn('⚠️ 失敗的店家:');

            $failedStores = array_filter($results['details'], fn($detail) => !$detail['result']['success']);

            foreach ($failedStores as $detail) {
                $this->warn('  - ' . $detail['store_name'] . ' (' . $detail['address'] . ')');
                $this->warn('    原因: ' . $detail['result']['message']);
            }
        }

        return $results['failed'] > 0 ? 1 : 0;
    }
}