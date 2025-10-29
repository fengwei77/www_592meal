<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetTwoFactorAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:reset-2fa {email : 使用者的電子郵件地址}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '重置使用者的雙重驗證設定（當驗證器遺失時使用）';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        // 尋找使用者
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("找不到電子郵件為 {$email} 的使用者！");
            return Command::FAILURE;
        }

        // 顯示使用者資訊
        $this->info("找到使用者：{$user->name} ({$user->email})");

        if (!$user->two_factor_enabled) {
            $this->warn('此使用者尚未啟用雙重驗證。');
            return Command::SUCCESS;
        }

        // 確認是否要重置
        if (!$this->confirm('確定要重置此使用者的雙重驗證設定嗎？')) {
            $this->info('已取消操作。');
            return Command::SUCCESS;
        }

        // 重置雙重驗證
        $user->two_factor_enabled = false;
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_temp_disabled_at = null;
        $user->save();

        $this->info('✓ 成功重置雙重驗證設定！');
        $this->info('使用者現在可以：');
        $this->info('  1. 使用帳號密碼登入（不需要驗證碼）');
        $this->info('  2. 在個人設定中重新啟用雙重驗證');

        return Command::SUCCESS;
    }
}
