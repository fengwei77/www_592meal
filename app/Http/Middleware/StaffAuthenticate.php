<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 店員認證中間件
 * 檢查店員是否已通過密碼驗證
 */
class StaffAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 從路由參數獲取店家 slug
        $storeSlug = $request->route('storeSlug');

        // 檢查是否已通過店員驗證
        if (!session('staff_authenticated')) {
            return redirect()
                ->route('admin.store.staff.login', $storeSlug)
                ->with('error', '請先登入以訪問訂單管理系統');
        }

        // 檢查登入的店家是否與當前訪問的店家一致（防止跨店訪問）
        if (session('staff_store_slug') !== $storeSlug) {
            // 清除當前 session
            session()->forget([
                'staff_authenticated',
                'staff_store_id',
                'staff_store_slug',
                'staff_login_time',
            ]);

            return redirect()
                ->route('admin.store.staff.login', $storeSlug)
                ->with('error', '請重新登入');
        }

        return $next($request);
    }
}
