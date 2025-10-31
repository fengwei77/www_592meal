<?php

namespace App\Console\Commands;

use App\Services\AddressGeocodingService;
use Illuminate\Console\Command;

class TestGeocoding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-geocoding';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '測試地址定位服務';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('測試地址定位服務...');

        $service = new AddressGeocodingService();

        // 測試地址
        $testAddresses = [
            '台北市大安區信義路四段1號',
            '台北市信義區信義路五段7號',
            '高雄市左營區博愛二路777號'
        ];

        foreach ($testAddresses as $address) {
            $this->line("\n測試地址: " . $address);

            try {
                $result = $service->geocodeAddress($address);

                if ($result) {
                    $this->info('✓ 定位成功');
                    $this->info('  緯度: ' . $result['latitude']);
                    $this->info('  經度: ' . $result['longitude']);
                    $this->info('  格式化地址: ' . $result['formatted_address']);
                    $this->info('  來源: ' . $result['source']);
                    $this->info('  準確度: ' . ($result['confidence'] * 100) . '%');
                } else {
                    $this->error('✗ 定位失敗');
                }
            } catch (\Exception $e) {
                $this->error('✗ 定位錯誤: ' . $e->getMessage());
            }
        }

        return 0;
    }
}
