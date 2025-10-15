<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin 網域檢查中介層
 *
 * 安全架構：
 * - 前台網域 (oh592meal.test)：只能訪問前台功能，禁止任何 /admin 路徑
 * - 後台網域 (cms.oh592meal.test)：整個網域專用於後台管理，無 /admin 路徑暴露
 *
 * 這種設計的優勢：
 * 1. 後台入口不可被掃描工具發現
 * 2. 完全的網域隔離，降低攻擊面
 * 3. 符合安全最佳實踐
 */
class CheckAdminDomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 取得當前請求的主機名稱
        $currentHost = $request->getHost();

        // 允許的後台網域（從環境變數讀取）
        $adminDomain = parse_url(config('app.admin_url', 'https://cms.oh592meal.test'), PHP_URL_HOST);
        $frontDomain = parse_url(config('app.url', 'https://oh592meal.test'), PHP_URL_HOST);

        // 安全規則 1：前台網域禁止訪問 /admin 路徑
        if ($currentHost === $frontDomain && $request->is('admin*')) {
            abort(404, '此頁面不存在'); // 返回 404 而非重定向，避免暴露後台位置
        }

        // 安全規則 2：後台網域只能由後台網域訪問
        // 如果請求來自非後台網域，但嘗試訪問 Filament 路由（通過檢查路由名稱）
        if ($currentHost !== $adminDomain) {
            $routeName = $request->route()?->getName();
            if ($routeName && str_starts_with($routeName, 'filament.')) {
                abort(403, '禁止訪問');
            }
        }

        // 安全規則 3：在後台網域上，確保訪問的是 Filament 路由
        if ($currentHost === $adminDomain) {
            // 允許 Filament 相關路由通過
            // Filament 的路由會自動處理，這裡不需要額外邏輯
            return $next($request);
        }

        return $next($request);
    }
}
