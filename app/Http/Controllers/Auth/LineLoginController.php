<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LineLoginController extends Controller
{
    /**
     * 重定向到 LINE Login 頁面
     */
    public function redirect(Request $request)
    {
        // 儲存回調後要返回的頁面
        if ($request->has('return_url')) {
            session(['line_login_return_url' => $request->input('return_url')]);
        }

        // 生成 state 防止 CSRF 攻擊
        $state = Str::random(40);
        session(['line_login_state' => $state]);

        // 生成 nonce 用於 ID token 驗證
        $nonce = Str::random(40);
        session(['line_login_nonce' => $nonce]);

        // 構建 LINE Login URL
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => config('line.channel_id'),
            'redirect_uri' => config('line.callback_url'),
            'state' => $state,
            'scope' => 'profile openid',
            'nonce' => $nonce,
        ]);

        return redirect(config('line.authorize_url') . '?' . $params);
    }

    /**
     * 處理 LINE Login 回調
     */
    public function callback(Request $request)
    {
        // 強制記錄回調被調用
        Log::info('LINE Login callback received', [
            'request_method' => $request->method(),
            'request_url' => $request->fullUrl(),
            'all_params' => $request->all(),
            'session_id' => session()->getId(),
            'state_in_request' => $request->input('state'),
            'state_in_session' => session('line_login_state'),
            'has_error' => $request->has('error'),
            'error_description' => $request->input('error_description'),
        ]);

        // 驗證 state
        if ($request->input('state') !== session('line_login_state')) {
            return redirect()->route('frontend.stores.index')
                ->with('error', 'LINE 登入驗證失敗，請重試');
        }

        // 檢查是否有錯誤
        if ($request->has('error')) {
            return redirect()->route('frontend.stores.index')
                ->with('error', 'LINE 登入失敗：' . $request->input('error_description'));
        }

        // 取得 authorization code
        $code = $request->input('code');

        try {
            // 交換 access token
            $tokenResponse = Http::asForm()->post(config('line.token_url'), [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => config('line.callback_url'),
                'client_id' => config('line.channel_id'),
                'client_secret' => config('line.channel_secret'),
            ]);

            if (!$tokenResponse->successful()) {
                throw new \Exception('無法取得 access token');
            }

            $tokenData = $tokenResponse->json();
            $accessToken = $tokenData['access_token'];

            // 取得用戶資料
            $profileResponse = Http::withToken($accessToken)
                ->get(config('line.profile_url'));

            if (!$profileResponse->successful()) {
                throw new \Exception('無法取得用戶資料');
            }

            $profile = $profileResponse->json();

            // 查找或創建 Customer 記錄
            $customer = \App\Models\Customer::where('line_id', $profile['userId'])->first();

            if (!$customer) {
                // 創建新顧客記錄
                $customer = \App\Models\Customer::create([
                    'name' => $profile['displayName'],
                    'line_id' => $profile['userId'],
                    'avatar_url' => $profile['pictureUrl'] ?? null,
                    'notification_confirmed' => true,
                    'notification_preparing' => true,
                    'notification_ready' => true,
                ]);
            } else {
                // 更新現有顧客資料
                $customer->update([
                    'name' => $profile['displayName'],
                    'avatar_url' => $profile['pictureUrl'] ?? null,
                ]);
            }

            // 真正登入用戶到 Laravel 認證系統
            \Illuminate\Support\Facades\Auth::guard('customer')->login($customer);

            // 儲存 LINE 用戶資訊到 session (向後相容)
            $lineUserData = [
                'line_user' => [
                    'user_id' => $profile['userId'],
                    'display_name' => $profile['displayName'],
                    'picture_url' => $profile['pictureUrl'] ?? null,
                    'status_message' => $profile['statusMessage'] ?? null,
                ],
                'line_logged_in' => true,
            ];

            Log::info('LINE Login 即將設定 session 資料', $lineUserData);
            session($lineUserData);

            Log::info('LINE Login session 設定完成', [
                'session_id' => session()->getId(),
                'line_logged_in' => session('line_logged_in'),
                'line_user_id' => session('line_user.user_id'),
                'customer_authenticated' => auth('customer')->check(),
                'customer_id' => auth('customer')->id(),
            ]);

            // 清除臨時 session 資料 (更精確地只清除特定項目)
            $tempSessionData = [
                'line_login_state' => session('line_login_state'),
                'line_login_nonce' => session('line_login_nonce'),
                'line_login_return_url' => session('line_login_return_url'),
            ];

            Log::info('LINE Login 即將清除的臨時 session 資料', $tempSessionData);
            session()->forget(['line_login_state', 'line_login_nonce', 'line_login_return_url']);

            // 驗證重要 session 資料是否仍然存在
            Log::info('LINE Login 清除後的 session 狀態', [
                'line_logged_in' => session('line_logged_in'),
                'line_user_id' => session('line_user.user_id'),
                'customer_authenticated' => auth('customer')->check(),
                'customer_id' => auth('customer')->id(),
                'session_id' => session()->getId(),
            ]);

            // 取得重定向URL (在清除之前)
            $returnUrl = session('line_login_return_url', route('frontend.stores.index'));

            return redirect($returnUrl)
                ->with('success', '歡迎，' . $profile['displayName'] . '！');

        } catch (\Exception $e) {
            \Log::error('LINE Login 失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('frontend.stores.index')
                ->with('error', 'LINE 登入失敗，請稍後再試');
        }
    }

    /**
     * 登出 LINE
     */
    public function logout(Request $request)
    {
        $userInfo = [
            'customer_authenticated' => \Illuminate\Support\Facades\Auth::guard('customer')->check(),
            'customer_id' => \Illuminate\Support\Facades\Auth::guard('customer')->id(),
            'line_logged_in' => session('line_logged_in'),
            'line_user_id' => session('line_user.user_id'),
            'request_method' => $request->method(),
            'referer' => $request->headers->get('referer'),
        ];

        \Log::info('LINE Logout started', $userInfo);

        // 登出 Laravel 認證系統
        \Illuminate\Support\Facades\Auth::guard('customer')->logout();

        // 清除所有 LINE 相關的 session 資料
        $sessionData = [
            'line_user' => session('line_user'),
            'line_logged_in' => session('line_logged_in'),
        ];

        session()->forget(['line_user', 'line_logged_in']);

        \Log::info('LINE Logout completed', [
            'cleared_session_data' => $sessionData,
            'session_id_after' => session()->getId(),
        ]);

        // 確保有安全的重定向目標
        $redirectTo = $request->headers->get('referer');
        if (!$redirectTo || str_contains($redirectTo, '/auth/line')) {
            $redirectTo = route('frontend.stores.index');
        }

        return redirect($redirectTo)
            ->with('success', '已成功登出 LINE');
    }

    /**
     * 檢查是否已登入 LINE
     */
    public function check()
    {
        $customer = \Illuminate\Support\Facades\Auth::guard('customer')->user();

        return response()->json([
            'logged_in' => \Illuminate\Support\Facades\Auth::guard('customer')->check(),
            'user' => $customer ? [
                'id' => $customer->id,
                'name' => $customer->name,
                'line_id' => $customer->line_id,
                'avatar_url' => $customer->avatar_url,
            ] : null,
            'legacy_session' => [
                'line_logged_in' => session('line_logged_in', false),
                'line_user' => session('line_user'),
            ],
        ]);
    }
}
