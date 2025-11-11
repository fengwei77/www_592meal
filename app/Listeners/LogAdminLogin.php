<?php

namespace App\Listeners;

use App\Models\AdminLoginLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;

/**
 * Log Admin Login Listener
 *
 * 監聽後台管理面板登入事件並記錄日誌
 */
class LogAdminLogin
{
    public function __construct(
        private Request $request
    ) {}

    /**
     * 處理登入成功事件
     */
    public function handleLogin(Login $event): void
    {
        // 只記錄來自 Filament Admin Panel 的登入
        if ($this->isAdminPanelRequest()) {
            AdminLoginLog::create([
                'user_id' => $event->user->id,
                'email' => $event->user->email,
                'ip_address' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
                'success' => true,
            ]);
        }
    }

    /**
     * 處理登入失敗事件
     */
    public function handleFailed(Failed $event): void
    {
        // 只記錄來自 Filament Admin Panel 的登入失敗
        if ($this->isAdminPanelRequest()) {
            AdminLoginLog::create([
                'user_id' => null,
                'email' => $event->credentials['email'] ?? 'unknown',
                'ip_address' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
                'success' => false,
                'failure_reason' => $event->user ? 'invalid_credentials' : 'user_not_found',
            ]);
        }
    }

    /**
     * 判斷是否為管理面板請求
     */
    private function isAdminPanelRequest(): bool
    {
        return str_contains($this->request->path(), 'admin') ||
               str_contains($this->request->header('referer', ''), 'admin');
    }

    /**
     * 註冊監聽器
     */
    public function subscribe(): array
    {
        return [
            Login::class => 'handleLogin',
            Failed::class => 'handleFailed',
        ];
    }
}