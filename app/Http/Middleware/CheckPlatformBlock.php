<?php

namespace App\Http\Middleware;

use App\Models\StoreCustomerBlock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlatformBlock
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 只檢查已登入的用戶 - 使用 Laravel 認證系統
        if (auth('customer')->check()) {
            $customer = auth('customer')->user();
            $lineUserId = $customer->line_id;

            // 檢查是否被平台鎖定（被3個或以上店家鎖定）
            if (StoreCustomerBlock::isPlatformBlocked($lineUserId)) {
                // 如果已經在封鎖頁面，則允許訪問
                if ($request->routeIs('platform.blocked')) {
                    return $next($request);
                }

                // 允許登出
                if ($request->routeIs('line.logout')) {
                    return $next($request);
                }

                // 否則重定向到封鎖頁面
                return redirect()->route('platform.blocked')
                    ->with('error', '您已被多個店家封鎖，無法使用平台。請聯絡系統管理員。');
            }
        }

        return $next($request);
    }
}
