<?php

namespace App\Listeners;

use App\Models\WebsiteLoginLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;

/**
 * Log Website Login Listener
 *
 * 監聽網站前台登入事件並記錄日誌
 */
class LogWebsiteLogin
{
    public function __construct(
        private Request $request
    ) {}

    /**
     * 處理登入成功事件
     */
    public function handleLogin(Login $event): void
    {
        WebsiteLoginLog::create([
            'email' => $event->user->email,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'success' => true,
        ]);
    }

    /**
     * 處理登入失敗事件
     */
    public function handleFailed(Failed $event): void
    {
        WebsiteLoginLog::create([
            'email' => $event->credentials['email'] ?? 'unknown',
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'success' => false,
            'failure_reason' => $event->user ? 'invalid_credentials' : 'user_not_found',
        ]);
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