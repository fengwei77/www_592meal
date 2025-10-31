<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class TestAdminAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:admin-access';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '測試超級管理員的後台訪問權限';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 測試後台管理員訪問權限');
        $this->info('============================');

        // 查找所有超級管理員
        $superAdmins = User::whereHas('roles', function ($query) {
            $query->where('name', 'super_admin');
        })->get();

        if ($superAdmins->isEmpty()) {
            $this->error('❌ 沒有找到超級管理員用戶');
            return 1;
        }

        foreach ($superAdmins as $user) {
            $this->info("\n👤 用戶: " . $user->name);
            $this->info("📧 Email: " . $user->email);
            $this->info("🔑 Roles: " . $user->roles->pluck('name')->implode(', '));
            $this->info("📋 Permissions: " . $user->permissions->count() . " 個權限");

            // 測試 canAccessPanel 方法
            try {
                $panel = app(\Filament\Panel::class);
                $canAccess = $user->canAccessPanel($panel);
                $this->info("🔐 canAccessPanel(): " . ($canAccess ? '✅ 允許' : '❌ 拒絕'));
            } catch (\Exception $e) {
                $this->error("❌ canAccessPanel() 錯誤: " . $e->getMessage());
            }

            // 測試 isSuperAdmin 方法
            $isSuperAdmin = method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : 'N/A';
            $this->info("👑 isSuperAdmin(): " . ($isSuperAdmin === true ? '✅ 是' : ($isSuperAdmin === false ? '❌ 否' : '⚠️ 方法不存在')));
        }

        $this->info("\n🌐 後台 URL: " . config('app.admin_url'));
        $this->info("📝 請嘗試使用上述帳號登入後台");

        return 0;
    }
}