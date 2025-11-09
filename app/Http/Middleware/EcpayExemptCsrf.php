<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Http\Request;
use Closure;
use Illuminate\Support\Facades\Log;

class EcpayExemptCsrf extends Middleware
{
    /**
     * 處理應用程式的請求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, Closure $next)
    {
        // 記錄所有請求，幫助調試
        Log::info('EcpayExemptCsrf middleware called', [
            'path' => $request->path(),
            'method' => $request->method(),
            'is_ecpay_callback' => $this->isEcpayCallback($request),
        ]);

        // 對於綠界回傳的 URL 排除 CSRF 檢查
        if ($this->isEcpayCallback($request)) {
            Log::info('CSRF check bypassed for ECPay callback', ['path' => $request->path()]);
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    /**
     * 檢查是否為綠界回傳請求
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function isEcpayCallback(Request $request): bool
    {
        $uri = $request->path();

        // 檢查是否為綠界回傳的 URL
        $ecpayUris = [
            'ecpay/test/return',
            'ecpay/test/payment-info',
            'ecpay/return',
            'ecpay/payment-info',
        ];

        foreach ($ecpayUris as $ecpayUri) {
            if (str_contains($uri, $ecpayUri)) {
                return true;
            }
        }

        return false;
    }
}