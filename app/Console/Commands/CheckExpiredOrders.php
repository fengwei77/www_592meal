<?php

namespace App\Console\Commands;

use App\Models\SubscriptionOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiredOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:check-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '檢查並更新過期的訂單紀錄';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('開始檢查過期訂單...');

        try {
            $subscriptionService = app(\App\Services\SubscriptionService::class);
            $expiredCount = $subscriptionService->checkExpiredOrders();

            $this->info("處理完成，共 {$expiredCount} 個訂單已標記為過期");

            Log::info('CheckExpiredOrders command executed', [
                'expired_count' => $expiredCount,
                'timestamp' => now()->toISOString(),
            ]);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('檢查過期訂單時發生錯誤: ' . $e->getMessage());
            Log::error('CheckExpiredOrders command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }
}