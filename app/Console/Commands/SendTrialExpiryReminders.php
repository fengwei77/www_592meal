<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendTrialExpiryReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:send-trial-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '發送試用期到期提醒通知';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('開始發送試用期到期提醒...');

        try {
            $subscriptionService = app(SubscriptionService::class);
            $sentCount = $subscriptionService->sendTrialExpiryReminders();

            if ($sentCount > 0) {
                $this->info("成功發送 {$sentCount} 個試用期到期提醒");
            } else {
                $this->info('目前沒有需要發送試用期提醒的用戶');
            }

            Log::info('SendTrialExpiryReminders command executed', [
                'sent_count' => $sentCount,
                'timestamp' => now()->toISOString(),
            ]);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('發送試用期提醒時發生錯誤: ' . $e->getMessage());
            Log::error('SendTrialExpiryReminders command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }
}
