<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ECPayCallback
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 為綠界金流回饋請求設定特殊的 session 配置
        if ($request->is('ecpay/*')) {
            // 啟用 session，但不啟用 CSRF 保護（因為來自外部系統）
            config(['session.http_only' => true]);
            config(['session.same_site' => 'lax']);
        }

        return $next($request);
    }
}