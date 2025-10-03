<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;
use Exception;

#[Layout('components.layouts.app')]
class SystemStatus extends Component
{
    public $services = [];

    public function mount()
    {
        $this->checkAllServices();
    }

    public function refresh()
    {
        $this->checkAllServices();
    }

    private function checkAllServices()
    {
        $this->services = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
            'line_login' => $this->checkLineLogin(),
            'line_messaging' => $this->checkLineMessaging(),
            'google' => $this->checkGoogle(),
        ];
    }

    private function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();
            return [
                'status' => 'OK',
                'message' => "已連線到資料庫: {$dbName}",
                'details' => [
                    'driver' => config('database.default'),
                    'host' => config('database.connections.pgsql.host'),
                    'database' => $dbName,
                ]
            ];
        } catch (Exception $e) {
            return [
                'status' => 'NG',
                'message' => '資料庫連線失敗',
                'details' => [
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    private function checkRedis()
    {
        try {
            Redis::ping();
            return [
                'status' => 'OK',
                'message' => 'Redis 運作正常',
                'details' => [
                    'host' => config('database.redis.default.host'),
                    'port' => config('database.redis.default.port'),
                ]
            ];
        } catch (Exception $e) {
            return [
                'status' => 'NG',
                'message' => 'Redis 連線失敗',
                'details' => [
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    private function checkStorage()
    {
        try {
            $disk = Storage::disk();
            $disk->put('health-check.txt', 'OK');
            $content = $disk->get('health-check.txt');
            $disk->delete('health-check.txt');

            return [
                'status' => 'OK',
                'message' => '儲存空間運作正常',
                'details' => [
                    'disk' => config('filesystems.default'),
                    'driver' => config('filesystems.disks.' . config('filesystems.default') . '.driver'),
                ]
            ];
        } catch (Exception $e) {
            return [
                'status' => 'NG',
                'message' => '儲存空間檢查失敗',
                'details' => [
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    private function checkQueue()
    {
        try {
            $connection = config('queue.default');
            $size = Queue::size();

            return [
                'status' => 'OK',
                'message' => '佇列系統運作正常',
                'details' => [
                    'connection' => $connection,
                    'queue_size' => $size,
                ]
            ];
        } catch (Exception $e) {
            return [
                'status' => 'NG',
                'message' => '佇列系統檢查失敗',
                'details' => [
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    private function checkLineLogin()
    {
        $channelId = config('services.line.client_id');
        $channelSecret = config('services.line.client_secret');
        $callbackUrl = config('services.line.redirect');

        if (empty($channelId) || empty($channelSecret)) {
            return [
                'status' => 'NG',
                'message' => 'LINE Login 未設定',
                'details' => [
                    'channel_id' => empty($channelId) ? '未設定' : '已設定',
                    'channel_secret' => empty($channelSecret) ? '未設定' : '已設定',
                    'callback_url' => $callbackUrl,
                ]
            ];
        }

        return [
            'status' => 'OK',
            'message' => 'LINE Login 設定完成',
            'details' => [
                'channel_id' => substr($channelId, 0, 8) . '...',
                'callback_url' => $callbackUrl,
            ]
        ];
    }

    private function checkLineMessaging()
    {
        $channelId = config('services.line.messaging.channel_id');
        $channelSecret = config('services.line.messaging.channel_secret');
        $accessToken = config('services.line.messaging.channel_access_token');

        // 檢查基本配置
        if (empty($channelId) || empty($channelSecret)) {
            return [
                'status' => 'NG',
                'message' => 'LINE Messaging API 未設定',
                'details' => [
                    'channel_id' => empty($channelId) ? '未設定' : '已設定',
                    'channel_secret' => empty($channelSecret) ? '未設定' : '已設定',
                    'access_token' => empty($accessToken) ? '未設定' : '已設定',
                ]
            ];
        }

        // 如果沒有 Access Token，顯示部分設定完成
        if (empty($accessToken)) {
            return [
                'status' => 'NG',
                'message' => 'LINE Messaging API 部分設定完成（缺少 Access Token）',
                'details' => [
                    'channel_id' => substr($channelId, 0, 8) . '...',
                    'channel_secret' => '已設定',
                    'access_token' => '⚠️ 未設定（需要在 LINE Developers Console 取得）',
                    'note' => 'Access Token 是用於發送訊息的必要憑證',
                ]
            ];
        }

        // 有完整配置才進行 API 測試
        try {
            $response = Http::timeout(5)->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get('https://api.line.me/v2/bot/info');

            if ($response->successful()) {
                return [
                    'status' => 'OK',
                    'message' => 'LINE Messaging API 運作正常',
                    'details' => [
                        'channel_id' => substr($channelId, 0, 8) . '...',
                        'api_status' => '✅ 已連線',
                        'bot_info' => $response->json(),
                    ]
                ];
            }

            return [
                'status' => 'NG',
                'message' => 'LINE Messaging API 驗證失敗',
                'details' => [
                    'error' => 'API 回應異常 (HTTP ' . $response->status() . ')',
                    'hint' => '請檢查 Access Token 是否正確或已過期',
                ]
            ];
        } catch (Exception $e) {
            return [
                'status' => 'NG',
                'message' => 'LINE Messaging API 連線失敗',
                'details' => [
                    'error' => $e->getMessage(),
                    'hint' => '可能是網路問題或 Access Token 無效',
                ]
            ];
        }
    }

    private function checkGoogle()
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect');

        if (empty($clientId) || empty($clientSecret)) {
            return [
                'status' => 'NG',
                'message' => 'Google OAuth 未設定',
                'details' => [
                    'client_id' => empty($clientId) ? '未設定' : '已設定',
                    'client_secret' => empty($clientSecret) ? '未設定' : '已設定',
                    'redirect_uri' => $redirectUri,
                ]
            ];
        }

        return [
            'status' => 'OK',
            'message' => 'Google OAuth 設定完成',
            'details' => [
                'client_id' => substr($clientId, 0, 20) . '...',
                'redirect_uri' => $redirectUri,
            ]
        ];
    }

    public function getServiceName($key)
    {
        $names = [
            'database' => '資料庫',
            'redis' => 'Redis',
            'storage' => '儲存空間',
            'queue' => '佇列系統',
            'line_login' => 'LINE Login',
            'line_messaging' => 'LINE Messaging API',
            'google' => 'Google OAuth',
        ];
        return $names[$key] ?? $key;
    }

    public function formatDetailKey($key)
    {
        $translations = [
            'driver' => '驅動程式',
            'host' => '主機',
            'database' => '資料庫名稱',
            'port' => '連接埠',
            'disk' => '磁碟',
            'connection' => '連線',
            'queue_size' => '佇列大小',
            'channel_id' => 'Channel ID',
            'channel_secret' => 'Channel Secret',
            'callback_url' => '回呼 URL',
            'access_token' => 'Access Token',
            'error' => '錯誤訊息',
            'api_status' => 'API 狀態',
            'bot_info' => 'Bot 資訊',
            'client_id' => 'Client ID',
            'client_secret' => 'Client Secret',
            'redirect_uri' => '重定向 URI',
        ];
        return $translations[$key] ?? $key;
    }

    public function render()
    {
        return view('livewire.system-status');
    }
}
