<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * PushNotificationService
 * 處理推播通知的發送
 */
class PushNotificationService
{
    private WebPush $webPush;

    public function __construct()
    {
        $this->initializeWebPush();
    }

    /**
     * 初始化 WebPush
     */
    private function initializeWebPush(): void
    {
        $vapidSubject = config('broadcasting.push.vapid.subject', 'mailto:admin@592meal.com');
        $vapidPublicKey = config('broadcasting.push.vapid.public_key', 'BD7y3xvsnG7PK4t2NRbIci5oBFSkB6-mniFjRxhywHQXi-ylnp1y4EO_es9Yx5CJYDo-KLWtw5fiEGHYHyKC_S4');
        $vapidPrivateKey = config('broadcasting.push.vapid.private_key', 'eyP8z6Nzk7PUidz6Ufkkzy5OJxI4Rge2MEnL7FGEsug');

        if (!$vapidPublicKey || !$vapidPrivateKey) {
            Log::warning('VAPID 金鑰未設定，推播通知將無法運作');
            return;
        }

        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => $vapidSubject ?: 'mailto:admin@592meal.com',
                'publicKey' => $vapidPublicKey,
                'privateKey' => $vapidPrivateKey,
            ],
        ]);
    }

    /**
     * 發送訂單狀態變更推播
     *
     * @param Order $order
     * @param string $status
     * @return int 成功發送的推播數量
     */
    public function sendOrderStatusNotification(Order $order, string $status): int
    {
        // 檢查訂單是否有關聯的顧客
        if (!$order->customer) {
            Log::info('訂單沒有關聯的顧客，無法發送推播', [
                'order_id' => $order->id,
                'order_number' => $order->order_number
            ]);
            return 0;
        }

        $customer = $order->customer;

        // 檢查顧客的通知偏好
        if (!$this->shouldSendNotification($customer, $status)) {
            Log::info('根據用戶偏好設定，不發送推播', [
                'customer_id' => $customer->id,
                'status' => $status
            ]);
            return 0;
        }

        // 取得顧客的所有啟用訂閱
        $subscriptions = $customer->pushSubscriptions()->active()->get();

        if ($subscriptions->isEmpty()) {
            Log::info('顧客沒有啟用的推播訂閱', [
                'customer_id' => $customer->id
            ]);
            return 0;
        }

        // 建立推播通知內容
        $notification = $this->createOrderNotification($order, $status);

        $successCount = 0;
        $failedSubscriptions = [];

        // 發送推播到所有訂閱
        foreach ($subscriptions as $subscription) {
            try {
                $webPushSubscription = Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->p256dh_key,
                    'authToken' => $subscription->auth_key,
                ]);

                $this->webPush->queueNotification(
                    $webPushSubscription,
                    json_encode($notification)
                );

                Log::debug('推播已加入佇列', [
                    'subscription_id' => $subscription->id,
                    'order_id' => $order->id
                ]);
            } catch (\Exception $e) {
                Log::error('建立推播訂閱失敗', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 批次發送所有推播
        try {
            foreach ($this->webPush->flush() as $report) {
                $endpoint = $report->getRequest()->getUri()->__toString();

                if ($report->isSuccess()) {
                    $successCount++;

                    // 更新訂閱最後使用時間
                    $subscription = PushSubscription::where('endpoint', $endpoint)->first();
                    if ($subscription) {
                        $subscription->touchLastUsed();
                    }

                    Log::info('推播發送成功', [
                        'endpoint' => substr($endpoint, 0, 50) . '...',
                        'order_id' => $order->id
                    ]);
                } else {
                    $reason = $report->getReason();
                    Log::warning('推播發送失敗', [
                        'endpoint' => substr($endpoint, 0, 50) . '...',
                        'reason' => $reason
                    ]);

                    // 如果訂閱已過期或是 410 Gone，標記為失效
                    if ($report->isSubscriptionExpired() || str_contains($reason, '410 Gone') || str_contains($reason, '404 Not Found')) {
                        $subscription = PushSubscription::where('endpoint', $endpoint)->first();
                        if ($subscription) {
                            $subscription->markAsInactive();
                            $failedSubscriptions[] = $subscription->id;
                            Log::info('訂閱已過期或失效，已標記為失效', [
                                'subscription_id' => $subscription->id
                            ]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('批次發送推播失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        Log::info('訂單推播發送完成', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $status,
            'total_subscriptions' => $subscriptions->count(),
            'success_count' => $successCount,
            'failed_subscriptions' => $failedSubscriptions
        ]);

        return $successCount;
    }

    /**
     * 檢查是否應該發送通知（根據用戶偏好）
     *
     * @param \App\Models\Customer $customer
     * @param string $status
     * @return bool
     */
    private function shouldSendNotification($customer, string $status): bool
    {
        return match ($status) {
            'confirmed' => $customer->notification_confirmed ?? true,
            'preparing' => $customer->notification_preparing ?? true,
            'ready' => $customer->notification_ready ?? true,
            default => true,
        };
    }

    /**
     * 建立訂單通知內容
     *
     * @param Order $order
     * @param string $status
     * @return array
     */
    private function createOrderNotification(Order $order, string $status): array
    {
        $messages = [
            'confirmed' => [
                'title' => '🎉 訂單已確認',
                'body' => "店家 {$order->store->name} 已接受您的訂單 #{$order->order_number}",
            ],
            'preparing' => [
                'title' => '👨‍🍳 訂單製作中',
                'body' => "您的訂單 #{$order->order_number} 正在熱騰騰製作中！",
            ],
            'ready' => [
                'title' => '✅ 訂單已完成',
                'body' => "您的訂單 #{$order->order_number} 已完成，請前往店家取餐",
            ],
        ];

        $baseMessage = $messages[$status] ?? [
            'title' => '訂單更新',
            'body' => '您的訂單狀態已更新'
        ];

        return array_merge($baseMessage, [
            'icon' => asset('images/icon-192x192.png'),
            'badge' => asset('images/badge-72x72.png'),
            'tag' => "order-{$order->id}",
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $status,
                'store_id' => $order->store_id,
                'store_name' => $order->store->name,
                'total_amount' => $order->total_amount,
                'url' => url("/orders/{$order->id}"),
            ],
        ]);
    }

    /**
     * 發送測試推播
     *
     * @param int $customerId
     * @param string $title
     * @param string $body
     * @return int
     */
    public function sendTestNotification(int $customerId, string $title = '測試通知', string $body = '這是一則測試推播通知'): int
    {
        $subscriptions = PushSubscription::active()
            ->forCustomer($customerId)
            ->get();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $notification = [
            'title' => $title,
            'body' => $body,
            'icon' => asset('images/icon-192x192.png'),
            'badge' => asset('images/badge-72x72.png'),
            'tag' => 'test-notification',
            'data' => [
                'type' => 'test',
                'timestamp' => now()->toISOString(),
            ],
        ];

        $successCount = 0;

        foreach ($subscriptions as $subscription) {
            // 檢測模擬訂閱（開發環境用）
            if (str_starts_with($subscription->endpoint, 'simulation://')) {
                Log::info('跳過模擬訂閱的實際推播發送', [
                    'subscription_id' => $subscription->id,
                    'endpoint' => $subscription->endpoint
                ]);
                // 模擬訂閱算作成功
                $successCount++;
                continue;
            }

            try {
                $webPushSubscription = Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->p256dh_key,
                    'authToken' => $subscription->auth_key,
                ]);

                $this->webPush->queueNotification(
                    $webPushSubscription,
                    json_encode($notification)
                );

                Log::debug('測試推播已加入佇列', [
                    'subscription_id' => $subscription->id
                ]);
            } catch (\Exception $e) {
                Log::error('建立測試推播失敗', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 批次發送非模擬訂閱的推播
        try {
            foreach ($this->webPush->flush() as $report) {
                $endpoint = $report->getRequest()->getUri()->__toString();

                if ($report->isSuccess()) {
                    $successCount++;
                    Log::info('測試推播發送成功', [
                        'endpoint' => substr($endpoint, 0, 50) . '...'
                    ]);
                } else {
                    $reason = $report->getReason();
                    Log::warning('測試推播發送失敗', [
                        'endpoint' => substr($endpoint, 0, 50) . '...',
                        'reason' => $reason
                    ]);

                    // 如果是 410 Gone 或 404 Not Found，自動標記訂閱為失效
                    if (str_contains($reason, '410 Gone') || str_contains($reason, '404 Not Found')) {
                        $this->deactivateSubscriptionByEndpoint($endpoint);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('批次發送測試推播失敗', [
                'error' => $e->getMessage()
            ]);
        }

        return $successCount;
    }

    /**
     * 根據 endpoint 停用失效的推播訂閱
     *
     * @param string $endpoint
     * @return void
     */
    private function deactivateSubscriptionByEndpoint(string $endpoint): void
    {
        try {
            $subscription = PushSubscription::where('endpoint', $endpoint)->first();

            if ($subscription && $subscription->is_active) {
                $subscription->update(['is_active' => false]);

                Log::info('已自動停用失效的推播訂閱', [
                    'subscription_id' => $subscription->id,
                    'customer_id' => $subscription->customer_id,
                    'endpoint' => substr($endpoint, 0, 50) . '...'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('停用失效推播訂閱失敗', [
                'endpoint' => substr($endpoint, 0, 50) . '...',
                'error' => $e->getMessage()
            ]);
        }
    }
}
