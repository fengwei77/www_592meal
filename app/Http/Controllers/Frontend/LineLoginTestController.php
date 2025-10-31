<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LineLoginTestController extends Controller
{
    /**
     * 顯示 LINE 登入測試頁面
     */
    public function index(Request $request)
    {
        // 收集系統診斷信息
        $diagnostics = $this->getSystemDiagnostics();

        // 收集 LINE 配置信息
        $lineConfig = $this->getLineConfig();

        // 檢查當前登入狀態
        $authStatus = $this->getAuthStatus($request);

        // 檢查最近的錯誤日誌
        $recentErrors = $this->getRecentErrors();

        return view('frontend.line-login-test', compact(
            'diagnostics',
            'lineConfig',
            'authStatus',
            'recentErrors'
        ));
    }

    /**
     * 重定向到 LINE 登入頁面
     */
    public function redirectToLine(Request $request)
    {
        try {
            $config = $this->getLineConfig();

            // 檢查必要配置
            if (empty($config['channel_id']) || empty($config['channel_secret'])) {
                return back()->with('error', 'LINE 配置不完整，請檢查 .env 檔案');
            }

            // 生成 state 參數防止 CSRF 攻擊
            $state = Str::random(32);
            $request->session()->put('line_oauth_state', $state);
            $request->session()->put('line_oauth_timestamp', now());

            // 構建 LINE OAuth URL
            $baseUrl = 'https://access.line.me/oauth2/v2.1/authorize';
            $params = [
                'response_type' => 'code',
                'client_id' => $config['channel_id'],
                'redirect_uri' => $config['callback_url'],
                'state' => $state,
                'scope' => 'openid profile email',
                'nonce' => Str::random(32),
            ];

            $authUrl = $baseUrl . '?' . http_build_query($params);

            Log::info('LINE 登入測試 - 重定向到 LINE', [
                'auth_url' => $authUrl,
                'state' => $state,
                'client_id' => $config['channel_id'],
                'redirect_uri' => $config['callback_url'],
            ]);

            return redirect($authUrl);

        } catch (\Exception $e) {
            Log::error('LINE 登入測試 - 重定向失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', '重定向到 LINE 失敗：' . $e->getMessage());
        }
    }

    /**
     * 處理 LINE 登入回調
     */
    public function handleCallback(Request $request)
    {
        try {
            $code = $request->get('code');
            $state = $request->get('state');
            $error = $request->get('error');
            $errorDescription = $request->get('error_description');

            // 記錄回調信息
            Log::info('LINE 登入測試 - 收到回調', [
                'code' => $code ? substr($code, 0, 20) . '...' : 'null',
                'state' => $state,
                'error' => $error,
                'error_description' => $errorDescription,
                'session_state' => $request->session()->get('line_oauth_state'),
            ]);

            // 檢查是否有錯誤
            if ($error) {
                Log::error('LINE 登入測試 - LINE 返回錯誤', [
                    'error' => $error,
                    'error_description' => $errorDescription
                ]);

                return redirect('/line-login-test')
                    ->with('error', "LINE 登入失敗：{$error} - {$errorDescription}");
            }

            // 驗證 state 參數
            $sessionState = $request->session()->get('line_oauth_state');
            if (!$state || $state !== $sessionState) {
                Log::error('LINE 登入測試 - State 驗證失敗', [
                    'request_state' => $state,
                    'session_state' => $sessionState
                ]);

                return redirect('/line-login-test')
                    ->with('error', 'State 驗證失敗，可能有安全問題');
            }

            // 驗證 code 參數
            if (!$code) {
                Log::error('LINE 登入測試 - 缺少授權碼');

                return redirect('/line-login-test')
                    ->with('error', 'LINE 未返回授權碼');
            }

            // 使用授權碼獲取 access token
            $tokenResult = $this->exchangeCodeForToken($code);

            if (!$tokenResult['success']) {
                Log::error('LINE 登入測試 - 獲取 token 失敗', $tokenResult);

                return redirect('/line-login-test')
                    ->with('error', '獲取授權失敗：' . $tokenResult['message']);
            }

            // 獲取用戶資料
            $profileResult = $this->getUserProfile($tokenResult['access_token']);

            if (!$profileResult['success']) {
                Log::error('LINE 登入測試 - 獲取用戶資料失敗', $profileResult);

                return redirect('/line-login-test')
                    ->with('error', '獲取用戶資料失敗：' . $profileResult['message']);
            }

            // 記錄成功資訊
            Log::info('LINE 登入測試 - 成功', [
                'user_id' => $profileResult['user_id'],
                'display_name' => $profileResult['display_name'],
                'access_token' => substr($tokenResult['access_token'], 0, 20) . '...',
            ]);

            // 清除 session 中的 state
            $request->session()->forget('line_oauth_state');
            $request->session()->forget('line_oauth_timestamp');

            return redirect('/line-login-test')
                ->with('success', 'LINE 登入測試成功！')
                ->with('profile', $profileResult)
                ->with('token_info', $tokenResult);

        } catch (\Exception $e) {
            Log::error('LINE 登入測試 - 處理回調時發生錯誤', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect('/line-login-test')
                ->with('error', '處理回調時發生錯誤：' . $e->getMessage());
        }
    }

    /**
     * 使用授權碼交換 access token
     */
    private function exchangeCodeForToken($code)
    {
        try {
            $config = $this->getLineConfig();

            $response = Http::asForm()->post('https://api.line.me/oauth2/v2.1/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $config['callback_url'],
                'client_id' => $config['channel_id'],
                'client_secret' => $config['channel_secret'],
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'HTTP 錯誤：' . $response->status(),
                    'response' => $response->body(),
                ];
            }

            $data = $response->json();

            if (isset($data['error'])) {
                return [
                    'success' => false,
                    'message' => $data['error_description'] ?? $data['error'],
                    'response' => $data,
                ];
            }

            return [
                'success' => true,
                'access_token' => $data['access_token'] ?? null,
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_in' => $data['expires_in'] ?? null,
                'id_token' => $data['id_token'] ?? null,
                'response' => $data,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '網路錯誤：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 獲取用戶資料
     */
    private function getUserProfile($accessToken)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get('https://api.line.me/v2/profile');

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'HTTP 錯誤：' . $response->status(),
                    'response' => $response->body(),
                ];
            }

            $data = $response->json();

            return [
                'success' => true,
                'user_id' => $data['userId'] ?? null,
                'display_name' => $data['displayName'] ?? null,
                'picture_url' => $data['pictureUrl'] ?? null,
                'status_message' => $data['statusMessage'] ?? null,
                'response' => $data,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '網路錯誤：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 獲取系統診斷信息
     */
    private function getSystemDiagnostics()
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => config('app.env'),
            'debug_mode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'current_time' => now()->toDateTimeString(),
            'server_ip' => request()->server('SERVER_ADDR') ?? 'Unknown',
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
        ];
    }

    /**
     * 獲取 LINE 配置信息
     */
    private function getLineConfig()
    {
        $lineConfig = config('services.line');

        return [
            'channel_id' => $lineConfig['client_id'] ?? null,
            'channel_secret' => $lineConfig['client_secret'] ?? null,
            'callback_url' => config('app.url') . '/line-login-test/callback',
            'messaging_channel_id' => $lineConfig['messaging']['channel_id'] ?? null,
        ];
    }

    /**
     * 獲取當前登入狀態
     */
    private function getAuthStatus($request)
    {
        return [
            'is_authenticated' => auth()->check(),
            'guard' => auth()->getDefaultDriver(),
            'user' => auth()->user() ? [
                'id' => auth()->user()->id,
                'name' => auth()->user()->name ?? null,
                'email' => auth()->user()->email ?? null,
            ] : null,
            'session_id' => $request->session()->getId(),
            'has_line_session' => $request->session()->has('line_oauth_state'),
            'line_session_timestamp' => $request->session()->get('line_oauth_timestamp'),
        ];
    }

    /**
     * 獲取最近的錯誤日誌
     */
    private function getRecentErrors()
    {
        try {
            $logFile = storage_path('logs/laravel.log');

            if (!file_exists($logFile)) {
                return ['message' => '找不到日誌檔案'];
            }

            $content = file_get_contents($logFile);
            $lines = array_reverse(explode("\n", $content));

            $errors = [];
            $count = 0;
            $maxErrors = 10;

            foreach ($lines as $line) {
                if ($count >= $maxErrors) break;

                // 檢查是否包含 LINE 相關的錯誤
                if (stripos($line, 'line') !== false &&
                    (stripos($line, 'error') !== false ||
                     stripos($line, 'exception') !== false ||
                     stripos($line, 'failed') !== false)) {

                    $errors[] = $line;
                    $count++;
                }
            }

            return $errors;

        } catch (\Exception $e) {
            return ['message' => '讀取日誌失敗：' . $e->getMessage()];
        }
    }
}