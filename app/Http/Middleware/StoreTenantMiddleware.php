<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreTenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 獲取子域名
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0] ?? null;

        // 如果是主域名，跳過租戶檢查
        if ($subdomain === config('app.main_domain', 'oh592meal')) {
            return $next($request);
        }

        // 查找對應的店家
        $store = Store::where('subdomain', $subdomain)
                     ->where('is_active', true)
                     ->first();

        // 如果找不到店家或店家未啟用，返回 404
        if (!$store) {
            abort(404, '店家不存在或未啟用');
        }

        // 將店家資訊存入請求中，供後續使用
        $request->merge(['current_store' => $store]);

        // 設定全域變數供視圖使用
        view()->share('current_store', $store);

        return $next($request);
    }
}