<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PushSubscription;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * NotificationSettingsController
 * 用戶推播通知設定管理
 */
class NotificationSettingsController extends Controller
{
    protected PushNotificationService $pushService;

    public function __construct(PushNotificationService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * 檢查用戶是否已透過 LINE 登入
     *
     * @return Customer|null
     */
    private function getAuthenticatedCustomer(): ?Customer
    {
        // 使用 Laravel 認證系統
        if (!auth('customer')->check()) {
            return null;
        }

        return auth('customer')->user();
    }

    /**
     * 確保用戶已登入，否則重定向
     *
     * @return Customer|RedirectResponse
     */
    private function requireAuthentication()
    {
        $customer = $this->getAuthenticatedCustomer();

        if (!$customer) {
            return redirect()->route('login')
                ->with('error', '請先登入 LINE 帳號');
        }

        return $customer;
    }

    /**
     * 顯示通知設定頁面
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request)
    {
        $customer = $this->requireAuthentication();

        // 如果未登入，會返回 RedirectResponse
        if ($customer instanceof \Illuminate\Http\RedirectResponse) {
            return $customer;
        }

        // 取得用戶的推播訂閱狀態
        $subscriptions = PushSubscription::where('customer_id', $customer->id)
            ->where('is_active', true)
            ->orderBy('last_used_at', 'desc')
            ->get()
            ->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'user_agent' => $this->parseUserAgent($subscription->user_agent),
                    'device_type' => $this->detectDeviceType($subscription->user_agent),
                    'last_used_at' => $subscription->last_used_at ? $subscription->last_used_at->diffForHumans() : '從未使用',
                    'created_at' => $subscription->created_at->format('Y-m-d'),
                ];
            });

        return view('customer.notifications.settings', [
            'customer' => $customer,
            'subscriptions' => $subscriptions,
            'hasActiveSubscriptions' => $subscriptions->isNotEmpty(),
        ]);
    }

    /**
     * 顯示推播通知調試頁面
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function debug(Request $request)
    {
        $customer = $this->requireAuthentication();

        // 如果未登入，會返回 RedirectResponse
        if ($customer instanceof \Illuminate\Http\RedirectResponse) {
            return $customer;
        }

        return view('customer.notifications.debug', [
            'customer' => $customer,
        ]);
    }

    /**
     * 更新通知偏好設定
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        try {
            $customer = $this->getAuthenticatedCustomer();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => '請先登入 LINE 帳號'
                ], 401);
            }

            $request->validate([
                'notification_confirmed' => 'sometimes|boolean',
                'notification_preparing' => 'sometimes|boolean',
                'notification_ready' => 'sometimes|boolean',
            ]);

            $updates = [];

            if ($request->has('notification_confirmed')) {
                $updates['notification_confirmed'] = $request->boolean('notification_confirmed');
            }

            if ($request->has('notification_preparing')) {
                $updates['notification_preparing'] = $request->boolean('notification_preparing');
            }

            if ($request->has('notification_ready')) {
                $updates['notification_ready'] = $request->boolean('notification_ready');
            }

            if (!empty($updates)) {
                $customer->update($updates);

                Log::info('用戶更新通知偏好', [
                    'customer_id' => $customer->id,
                    'updates' => $updates
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => '通知偏好已更新',
                'preferences' => [
                    'notification_confirmed' => $customer->notification_confirmed,
                    'notification_preparing' => $customer->notification_preparing,
                    'notification_ready' => $customer->notification_ready,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('更新通知偏好失敗', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '更新失敗：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 取消特定裝置的推播訂閱
     *
     * @param Request $request
     * @param int $subscriptionId
     * @return JsonResponse
     */
    public function removeSubscription(Request $request, int $subscriptionId): JsonResponse
    {
        try {
            $customer = $this->getAuthenticatedCustomer();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => '請先登入 LINE 帳號'
                ], 401);
            }

            $subscription = PushSubscription::where('id', $subscriptionId)
                ->where('customer_id', $customer->id)
                ->firstOrFail();

            $subscription->update(['is_active' => false]);

            Log::info('用戶取消推播訂閱', [
                'customer_id' => $customer->id,
                'subscription_id' => $subscriptionId
            ]);

            return response()->json([
                'success' => true,
                'message' => '已取消此裝置的推播訂閱'
            ]);

        } catch (\Exception $e) {
            Log::error('取消推播訂閱失敗', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '取消訂閱失敗：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 取消所有推播訂閱
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removeAllSubscriptions(Request $request): JsonResponse
    {
        try {
            $customer = $this->getAuthenticatedCustomer();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => '請先登入 LINE 帳號'
                ], 401);
            }

            $count = PushSubscription::where('customer_id', $customer->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            Log::info('用戶取消所有推播訂閱', [
                'customer_id' => $customer->id,
                'count' => $count
            ]);

            return response()->json([
                'success' => true,
                'message' => "已取消 {$count} 個裝置的推播訂閱"
            ]);

        } catch (\Exception $e) {
            Log::error('取消所有推播訂閱失敗', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '取消訂閱失敗：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 模擬推播訂閱（開發環境專用）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function simulateSubscription(Request $request): JsonResponse
    {
        try {
            $customer = $this->getAuthenticatedCustomer();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => '請先登入 LINE 帳號'
                ], 401);
            }

            $request->validate([
                'customer_id' => 'required|integer',
                'device_info' => 'required|string',
                'platform' => 'nullable|string',
                'is_simulation' => 'required|boolean',
            ]);

            // 確認 customer_id 與當前用戶匹配
            if ($request->input('customer_id') !== $customer->id) {
                return response()->json([
                    'success' => false,
                    'message' => '用戶 ID 不匹配'
                ], 403);
            }

            // 建立模擬訂閱記錄（使用有效的 Base64 編碼）
            // 生成 88 字元的 p256dh key (Base64 URL 編碼)
            $p256dhKey = base64_encode(random_bytes(65));
            // 生成 24 字元的 auth key (Base64 URL 編碼)
            $authKey = base64_encode(random_bytes(16));

            $subscription = PushSubscription::create([
                'customer_id' => $customer->id,
                'endpoint' => 'simulation://' . uniqid() . '.dev',
                'p256dh_key' => $p256dhKey,
                'auth_key' => $authKey,
                'user_agent' => $request->input('device_info'),
                'is_active' => true,
                'last_used_at' => now(),
            ]);

            Log::info('模擬推播訂閱已建立', [
                'customer_id' => $customer->id,
                'subscription_id' => $subscription->id,
                'device_info' => $request->input('device_info'),
            ]);

            return response()->json([
                'success' => true,
                'message' => '模擬訂閱建立成功',
                'subscription_id' => $subscription->id,
                'is_simulation' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('模擬推播訂閱失敗', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '模擬訂閱失敗：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 發送測試推播通知
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendTestNotification(Request $request): JsonResponse
    {
        try {
            $customer = $this->getAuthenticatedCustomer();

            if (!$customer) {
                Log::warning('未登入用戶嘗試發送測試通知');
                return response()->json([
                    'success' => false,
                    'message' => '請先登入 LINE 帳號'
                ], 401);
            }

            Log::info('開始發送測試推播', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name
            ]);

            // 檢查是否有推播訂閱
            $subscriptionCount = PushSubscription::active()
                ->forCustomer($customer->id)
                ->count();

            Log::info('推播訂閱數量', [
                'customer_id' => $customer->id,
                'subscription_count' => $subscriptionCount
            ]);

            if ($subscriptionCount === 0) {
                Log::info('用戶沒有啟用的推播訂閱', [
                    'customer_id' => $customer->id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => '沒有可用的推播訂閱，請先在此裝置上啟用推播通知'
                ], 400);
            }

            $successCount = $this->pushService->sendTestNotification(
                $customer->id,
                '測試通知',
                '這是一則測試推播通知，如果您看到這個訊息，表示推播功能正常運作！'
            );

            if ($successCount > 0) {
                Log::info('測試推播通知已發送', [
                    'customer_id' => $customer->id,
                    'success_count' => $successCount
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "測試通知已發送到 {$successCount} 個裝置"
                ]);
            } else {
                Log::warning('測試推播發送失敗，成功計數為 0', [
                    'customer_id' => $customer->id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => '推播發送失敗，請稍後再試'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('發送測試推播失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => '發送測試通知失敗：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 解析 User Agent 字串以顯示更友善的裝置名稱
     *
     * @param string|null $userAgent
     * @return string
     */
    private function parseUserAgent(?string $userAgent): string
    {
        if (!$userAgent) {
            return '未知裝置';
        }

        // 簡單的 User Agent 解析
        if (str_contains($userAgent, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgent, 'Safari')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgent, 'Edge')) {
            $browser = 'Edge';
        } else {
            $browser = '未知瀏覽器';
        }

        return $browser;
    }

    /**
     * 偵測裝置類型
     *
     * @param string|null $userAgent
     * @return string
     */
    private function detectDeviceType(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'unknown';
        }

        if (preg_match('/mobile|android|iphone|ipad|ipod/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }
}
