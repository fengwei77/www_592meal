<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Store;

/**
 * 店家訪問驗證中介層
 * 確保只有店家擁有者或店員可以訪問店家管理功能
 * 支援兩種認證方式：
 * 1. 後台系統登入（Filament Admin）
 * 2. 店員密碼登入（簡易版）
 */
class VerifyStoreAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 取得路由參數中的店家代碼
        $storeSlug = $request->route('store_slug') ?? $request->route('storeSlug');

        if (!$storeSlug) {
            abort(400, '缺少店家參數');
        }

        // 查詢店家
        $store = Store::where('store_slug_name', $storeSlug)
                      ->where('is_active', true)
                      ->first();

        if (!$store) {
            abort(404, '店家不存在或已停用');
        }

        // === 方式一：檢查店員認證 ===
        if (session('staff_authenticated') && session('staff_store_slug') === $storeSlug) {
            // 店員認證通過
            $request->merge(['current_store' => $store]);
            $request->attributes->set('store', $store);
            $request->attributes->set('is_staff_access', true);
            return $next($request);
        }

        // === 方式二：檢查後台登入 ===
        // 1. 檢查是否已登入後台
        if (!auth()->check()) {
            // 未登入後台，嘗試引導到店員登入
            return redirect()
                ->route('admin.store.staff.login', $storeSlug)
                ->with('error', '請先登入以訪問訂單管理系統');
        }

        $user = auth()->user();

        // 2. 檢查用戶權限
        // Super Admin 可以訪問所有店家
        if ($user->hasRole('super_admin')) {
            $request->merge(['current_store' => $store]);
            $request->attributes->set('store', $store);
            return $next($request);
        }

        // 5. Store Owner 只能訪問自己的店家
        if ($user->hasRole('store_owner')) {
            // 檢查該店家是否屬於當前用戶
            $userOwnsStore = $user->stores()
                                  ->where('id', $store->id)
                                  ->exists();

            if (!$userOwnsStore) {
                abort(403, '您沒有權限訪問此店家');
            }

            // 授權通過
            $request->merge(['current_store' => $store]);
            $request->attributes->set('store', $store);
            return $next($request);
        }

        // 6. 其他角色無權訪問
        abort(403, '您沒有權限訪問店家管理功能');
    }
}
