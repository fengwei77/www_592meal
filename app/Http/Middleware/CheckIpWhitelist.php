<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * IP 白名單檢查中介層
 *
 * 檢查當前登入用戶的 IP 是否在白名單內
 * 如果用戶啟用了 IP 白名單但當前 IP 不在白名單內，則拒絕訪問
 */
class CheckIpWhitelist
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 獲取當前登入的用戶 (User model, 不是 Customer)
        $user = Auth::user();

        // 如果用戶未登入，直接放行（由其他認證中介層處理）
        if (!$user) {
            return $next($request);
        }

        // 如果用戶沒有 ip_whitelist_enabled 屬性（例如 Customer model），直接放行
        if (!method_exists($user, 'isIpAllowed')) {
            return $next($request);
        }

        // 獲取客戶端 IP
        $clientIp = $request->ip();

        // 檢查 IP 是否允許訪問
        if (!$user->isIpAllowed($clientIp)) {
            // 記錄未授權訪問
            Log::warning('IP whitelist blocked access', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'client_ip' => $clientIp,
                'requested_url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
            ]);

            // 登出用戶
            Auth::logout();

            // 清除 session
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // 返回 403 Forbidden (Filament 會自動導向登入頁)
            abort(403, '您的 IP 位址不在允許的白名單內，無法訪問此資源。請聯繫系統管理員。');
        }

        return $next($request);
    }
}
