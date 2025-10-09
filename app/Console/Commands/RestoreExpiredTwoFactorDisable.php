<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class RestoreExpiredTwoFactorDisable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'two-factor:restore-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自動恢復超過 24 小時的臨時關閉 2FA';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('檢查需要恢復的臨時關閉 2FA...');

        // 查找所有臨時關閉超過 24 小時的用戶
        $users = User::whereNotNull('two_factor_temp_disabled_at')
            ->where('two_factor_temp_disabled_at', '<=', now()->subHours(24))
            ->get();

        if ($users->isEmpty()) {
            $this->info('沒有需要恢復的 2FA');
            return 0;
        }

        $count = 0;
        foreach ($users as $user) {
            $disabledAt = $user->two_factor_temp_disabled_at;
            $user->restoreTwoFactor();

            $this->line("✅ 已恢復: {$user->name} ({$user->email}) - 關閉時間: {$disabledAt->format('Y-m-d H:i')}");
            $count++;
        }

        $this->info("✅ 成功恢復 {$count} 個臨時關閉的 2FA");
        return 0;
    }
}
