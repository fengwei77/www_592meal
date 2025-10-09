<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * 臨時安全設定管理命令
 *
 * 在 Filament UI 修復前使用
 */
class ManageSecuritySettings extends Command
{
    protected $signature = 'security:manage
                            {action? : list, enable-ip, disable-ip, enable-2fa, disable-2fa}
                            {--user= : User email}';

    protected $description = '管理用戶安全設定 (IP白名單、2FA) - 臨時命令';

    public function handle()
    {
        $action = $this->argument('action');

        if (!$action) {
            return $this->showMenu();
        }

        return match ($action) {
            'list' => $this->listUsers(),
            'enable-ip' => $this->enableIpWhitelist(),
            'disable-ip' => $this->disableIpWhitelist(),
            'enable-2fa' => $this->enable2FA(),
            'disable-2fa' => $this->disable2FA(),
            default => $this->error('未知操作')
        };
    }

    protected function showMenu()
    {
        $this->info('🔒 安全設定管理工具');
        $this->newLine();

        $action = $this->choice(
            '請選擇操作',
            [
                'list' => '查看所有用戶及安全設定',
                'enable-ip' => '啟用 IP 白名單',
                'disable-ip' => '停用 IP 白名單',
                'enable-2fa' => '啟用 2FA',
                'disable-2fa' => '停用 2FA',
            ],
            'list'
        );

        $this->newLine();
        return $this->handle($action);
    }

    protected function listUsers()
    {
        $users = User::all();

        $this->table(
            ['ID', '名稱', 'Email', 'IP白名單', 'IP列表', '2FA', '角色'],
            $users->map(fn($user) => [
                $user->id,
                $user->name,
                $user->email,
                $user->ip_whitelist_enabled ? '✅' : '❌',
                $user->ip_whitelist ? implode(', ', $user->ip_whitelist) : '-',
                $user->two_factor_enabled ? '✅' : '❌',
                $user->roles->pluck('name')->implode(', ') ?: '-'
            ])
        );

        return 0;
    }

    protected function enableIpWhitelist()
    {
        $email = $this->option('user') ?: $this->ask('請輸入用戶 Email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("找不到用戶: {$email}");
            return 1;
        }

        $this->info("當前用戶: {$user->name} ({$user->email})");
        $this->info("當前 IP 白名單: " . ($user->ip_whitelist ? implode(', ', $user->ip_whitelist) : '無'));

        $ips = $this->ask('請輸入允許的 IP 位址 (多個用逗號分隔)');
        $ipList = array_map('trim', explode(',', $ips));

        $user->ip_whitelist_enabled = true;
        $user->ip_whitelist = $ipList;
        $user->save();

        $this->info("✅ 已為 {$user->email} 啟用 IP 白名單");
        $this->info("允許的 IP: " . implode(', ', $ipList));

        return 0;
    }

    protected function disableIpWhitelist()
    {
        $email = $this->option('user') ?: $this->ask('請輸入用戶 Email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("找不到用戶: {$email}");
            return 1;
        }

        $user->ip_whitelist_enabled = false;
        $user->save();

        $this->info("✅ 已為 {$user->email} 停用 IP 白名單");

        return 0;
    }

    protected function enable2FA()
    {
        $email = $this->option('user') ?: $this->ask('請輸入用戶 Email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("找不到用戶: {$email}");
            return 1;
        }

        $user->two_factor_enabled = true;
        $user->save();

        $this->info("✅ 已為 {$user->email} 啟用 2FA");
        $this->warn("⚠️  用戶需要到安全設定頁面掃描 QR Code 完成設定");

        return 0;
    }

    protected function disable2FA()
    {
        $email = $this->option('user') ?: $this->ask('請輸入用戶 Email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("找不到用戶: {$email}");
            return 1;
        }

        $user->disableTwoFactor();

        $this->info("✅ 已為 {$user->email} 停用 2FA");

        return 0;
    }
}
