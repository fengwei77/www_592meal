<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PreventDuplicateSubmission
{
    /**
     * 處理防重複提交請求
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 只檢查 POST, PUT, PATCH, DELETE 請求
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        // 生成唯一的提交標識符
        $submissionKey = $this->generateSubmissionKey($request);

        // 檢查是否已經有相同的請求在處理中
        if (Cache::has($submissionKey)) {
            Log::warning('重複提交被阻止', [
                'submission_key' => $submissionKey,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => '請勿重複提交',
                    'message' => '您的請求正在處理中，請稍候...'
                ], 429);
            }

            return back()
                ->with('error', '請勿重複提交，您的請求正在處理中...')
                ->withInput();
        }

        // 標記此請求正在處理中，設定 3 秒過期時間
        Cache::put($submissionKey, true, 3);

        try {
            $response = $next($request);

            // 請求成功完成後，立即清除標記
            Cache::forget($submissionKey);

            return $response;
        } catch (\Exception $e) {
            // 發生異常時清除標記，允許重新嘗試
            Cache::forget($submissionKey);
            throw $e;
        }
    }

    /**
     * 生成提交標識符
     */
    private function generateSubmissionKey(Request $request): string
    {
        // 基本資料
        $data = [
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
        ];

        // 對於特定的重要表單，包含更多資料以提高唯一性
        if ($request->routeIs('frontend.order.store')) {
            $data['customer_name'] = $request->input('customer_name');
            $data['total_amount'] = $request->input('total_amount', 0);
            $data['timestamp'] = floor($request->input('timestamp', time()) / 10); // 10秒內視為相同
        }

        // 對於購物車操作，包含購物車內容的雜湊
        if ($request->routeIs('cart.*')) {
            $cartItems = $request->input('cart_items', []);
            $data['cart_hash'] = md5(serialize($cartItems));
        }

        // 生成唯一鍵
        return 'duplicate_prevention:' . md5(serialize($data));
    }
}
