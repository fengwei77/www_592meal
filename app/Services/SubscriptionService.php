<?php

namespace App\Services;

use App\Models\SubscriptionOrder;
use App\Models\SubscriptionPaymentLog;
use App\Models\User;
use App\Mail\SubscriptionSuccessMail;
use App\Mail\SubscriptionExpiryReminderMail;
use App\Mail\TrialExpiryReminderMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SubscriptionService
{
    private EcpayService $ecpayService;

    public function __construct(EcpayService $ecpayService)
    {
        $this->ecpayService = $ecpayService;
    }

    /**
     * 初始化試用期
     */
    public function initializeTrial(User $user): bool
    {
        if ($user->is_trial_used) {
            Log::info('User already used trial', ['user_id' => $user->id]);
            return false;
        }

        try {
            DB::beginTransaction();

            $user->trial_ends_at = now()->addDays(config('ecpay.trial_days', 30));
            $user->is_trial_used = true;
            $user->save();

            DB::commit();

            Log::info('Trial period initialized', [
                'user_id' => $user->id,
                'trial_ends_at' => $user->trial_ends_at,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to initialize trial', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 建立訂單紀錄
     */
    public function createSubscriptionOrder(User $user, int $months, ?string $notes = null): array
    {
        try {
            // 檢查用戶是否可以建立新訂單（保留最後3筆待付款訂單）
            if (!$this->canCreateNewOrder($user)) {
                return [
                    'success' => false,
                    'message' => '您已有3筆待付款訂單，請先完成部分訂單的付款或等待訂單過期後再建立新訂單',
                ];
            }

            // 如果有超過3筆待付款訂單，取消最舊的訂單
            $cancelledCount = $this->cancelOldestPendingOrders($user);
            if ($cancelledCount > 0) {
                Log::info('Cancelled oldest pending orders to maintain 3-order limit', [
                    'user_id' => $user->id,
                    'cancelled_count' => $cancelledCount,
                ]);
            }

            // 計算金額
            $unitPrice = (int) config('ecpay.monthly_price', 50);
            $totalAmount = $months * $unitPrice;

            DB::beginTransaction();

            $order = SubscriptionOrder::createOrder($user, $months, $notes);

            DB::commit();

            Log::info('Subscription order created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $user->id,
                'months' => $months,
                'total_amount' => $totalAmount,
            ]);

            return [
                'success' => true,
                'message' => '訂單建立成功',
                'order' => $order,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create subscription order', [
                'user_id' => $user->id,
                'months' => $months,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => '訂單建立失敗，請稍後再試',
            ];
        }
    }

    /**
     * 生成綠界付款參數
     */
    public function generateEcpayParams(SubscriptionOrder $order): array
    {
        // 生成唯一的交易編號，避免重複
        $uniqueTradeNo = $this->generateUniqueTradeNo($order);

        $params = [
            'MerchantID'        => config('ecpay.merchant_id'),
            'MerchantTradeNo'   => $uniqueTradeNo,
            'MerchantTradeDate' => now()->format('Y/m/d H:i:s'),
            'PaymentType'       => 'aio',
            'TotalAmount'       => (int) $order->total_amount,
            'TradeDesc'         => '592Meal訂閱服務',
            'ItemName'          => "592Meal訂閱服務 {$order->months}個月 NT$" . number_format($order->total_amount),
            'ChoosePayment'     => 'ALL',
            'EncryptType'       => 1,
            'ReturnURL'         => route('ecpay.return'),
            'ClientBackURL'     => route('ecpay.clientReturn'),
            'OrderResultURL'    => route('ecpay.paymentInfo'),
            'CustomField1'      => 'subscription',
            'CustomField2'      => (string) $order->id, // 原始訂單ID
            'CustomField3'      => (string) $order->months,
        ];

        return $params;
    }

    /**
     * 生成唯一的交易編號
     */
    private function generateUniqueTradeNo(SubscriptionOrder $order): string
    {
        // 使用原始訂單編號 + 當前時間戳 + 隨機字串確保唯一性
        $timestamp = now()->format('ymdHis');
        $random = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

        // 確保總長度不超過20字元 (ECPay限制)
        $baseOrderNumber = substr($order->order_number, 3, 6); // 取中間6個字元 (跳過SUB前綴)
        $uniqueTradeNo = $baseOrderNumber . $timestamp . $random;

        // 如果還是太長，進一步截斷
        if (strlen($uniqueTradeNo) > 20) {
            $uniqueTradeNo = substr($uniqueTradeNo, 0, 20);
        }

        return $uniqueTradeNo;
    }

    /**
     * 產生綠界付款表單 HTML
     */
    public function generatePaymentForm(SubscriptionOrder $order): string
    {
        try {
            $params = $this->generateEcpayParams($order);

            // 記錄付款表單產生
            Log::info('Generating ECPay payment form', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'amount' => $order->total_amount,
                'params' => $params,
            ]);

            // 產生付款表單HTML
            $paymentForm = $this->ecpayService->generateSubmitForm($params);

            return $paymentForm;
        } catch (\Exception $e) {
            Log::error('Error generating ECPay payment form', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 處理綠界付款結果回傳
     */
    public function handlePaymentReturn(array $data): array
    {
        try {
            // 記錄原始資料
            $this->ecpayService->logEcpayResponse($data, 'return_url');

            // 驗證檢查碼
            if (!$this->ecpayService->verifyCheckMacValue($data)) {
                Log::error('CheckMacValue verification failed', $data);
                return ['success' => false, 'message' => '檢查碼驗證失敗'];
            }

            // 處理特殊字元
            $this->ecpayService->handleSpecialChars($data);

            $merchantTradeNo = $data['MerchantTradeNo'] ?? null;
            $orderId = $data['CustomField2'] ?? null;

            // 優先使用 CustomField2 (訂單ID) 查找訂單，因為現在使用動態交易編號
            if ($orderId) {
                $order = SubscriptionOrder::find($orderId);
            } else {
                // 降級方案：直接使用交易編號查找（相容舊資料）
                $order = SubscriptionOrder::where('order_number', $merchantTradeNo)->first();
            }

            if (!$order) {
                Log::error('Order not found', [
                    'MerchantTradeNo' => $merchantTradeNo,
                    'OrderId' => $orderId,
                    'data' => $data
                ]);
                return ['success' => false, 'message' => '訂單不存在'];
            }

            // 驗證金額
            if (!$this->ecpayService->verifyOrderAmount($order->total_amount, (int)($data['TradeAmt'] ?? 0))) {
                Log::error('Amount verification failed', [
                    'expected' => $order->total_amount,
                    'received' => $data['TradeAmt'] ?? 0,
                ]);
                return ['success' => false, 'message' => '金額驗證失敗'];
            }

            // 建立付款日誌
            $paymentLog = SubscriptionPaymentLog::createPaymentLog($order, $data);

            // 檢查是否為模擬付款
            if (($data['SimulatePaid'] ?? 0) == 1) {
                $paymentLog->markAsProcessed();
                return [
                    'success' => true,
                    'message' => '模擬付款通知已接收',
                    'simulate' => true,
                ];
            }

            // 處理付款成功
            if ($this->ecpayService->isPaymentSuccess($data)) {
                return $this->processPaymentSuccess($order, $data, $paymentLog);
            }

            // 處理付款失敗
            return $this->processPaymentFailure($order, $data, $paymentLog);
        } catch (\Exception $e) {
            Log::error('Error handling payment return', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return ['success' => false, 'message' => '處理付款結果時發生錯誤'];
        }
    }

    /**
     * 處理付款成功
     */
    private function processPaymentSuccess(SubscriptionOrder $order, array $data, SubscriptionPaymentLog $paymentLog): array
    {
        try {
            DB::beginTransaction();

            // 更新訂單狀態
            $order->markAsPaid($data);

            // 更新用戶訂閱
            $this->extendUserSubscription($order->user, $order->months);

            // 標記付款日誌為已處理
            $paymentLog->markAsProcessed();

            DB::commit();

            // 發送訂閱成功郵件
            try {
                Mail::to($order->user->email)->queue(new SubscriptionSuccessMail($order));
                Log::info('Subscription success email sent', [
                    'order_id' => $order->id,
                    'user_email' => $order->user->email,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to send subscription success email', [
                    'order_id' => $order->id,
                    'user_email' => $order->user->email,
                    'error' => $e->getMessage(),
                ]);
                // 不影響主要流程，只記錄警告
            }

            Log::info('Payment processed successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $order->user_id,
                'months' => $order->months,
                'payment_type' => $data['PaymentType'] ?? null,
            ]);

            return [
                'success' => true,
                'message' => '付款成功，訂閱已開通',
                'order' => $order->fresh(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process payment success', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => '處理付款成功時發生錯誤'];
        }
    }

    /**
     * 處理付款失敗
     */
    private function processPaymentFailure(SubscriptionOrder $order, array $data, SubscriptionPaymentLog $paymentLog): array
    {
        try {
            // 標記付款日誌為已處理
            $paymentLog->markAsProcessed();

            Log::warning('Payment failed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'rtn_code' => $data['RtnCode'] ?? null,
                'rtn_msg' => $data['RtnMsg'] ?? null,
            ]);

            return [
                'success' => false,
                'message' => '付款失敗：' . ($data['RtnMsg'] ?? '未知錯誤'),
                'rtn_code' => $data['RtnCode'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to process payment failure', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => '處理付款失敗時發生錯誤'];
        }
    }

    /**
     * 處理取號結果回傳 (ATM/CVS/BARCODE)
     */
    public function handlePaymentInfo(array $data): array
    {
        try {
            $this->ecpayService->logEcpayResponse($data, 'payment_info_url');

            // 驗證檢查碼
            if (!$this->ecpayService->verifyCheckMacValue($data)) {
                Log::error('PaymentInfo CheckMacValue verification failed', $data);
                return ['success' => false, 'message' => '檢查碼驗證失敗'];
            }

            $merchantTradeNo = $data['MerchantTradeNo'] ?? null;
            $orderId = $data['CustomField2'] ?? null;

            // 優先使用 CustomField2 (訂單ID) 查找訂單，因為現在使用動態交易編號
            if ($orderId) {
                $order = SubscriptionOrder::find($orderId);
            } else {
                // 降級方案：直接使用交易編號查找（相容舊資料）
                $order = SubscriptionOrder::where('order_number', $merchantTradeNo)->first();
            }

            if (!$order) {
                Log::error('Order not found for PaymentInfo', [
                    'MerchantTradeNo' => $merchantTradeNo,
                    'OrderId' => $orderId,
                    'data' => $data
                ]);
                return ['success' => false, 'message' => '訂單不存在'];
            }

            // 建立取號日誌
            $paymentLog = SubscriptionPaymentLog::createPaymentLog($order, $data);

            // 檢查取號是否成功
            if ($this->ecpayService->isNumberGeneratedSuccess($data)) {
                $paymentLog->markAsProcessed();

                Log::info('Payment number generated successfully', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'payment_type' => $data['PaymentType'] ?? null,
                ]);

                return [
                    'success' => true,
                    'message' => '取號成功',
                    'payment_info' => $paymentLog->getPaymentInfo(),
                ];
            }

            $paymentLog->markAsProcessed();

            Log::warning('Payment number generation failed', [
                'order_id' => $order->id,
                'rtn_code' => $data['RtnCode'] ?? null,
                'rtn_msg' => $data['RtnMsg'] ?? null,
            ]);

            return [
                'success' => false,
                'message' => '取號失敗：' . ($data['RtnMsg'] ?? '未知錯誤'),
            ];
        } catch (\Exception $e) {
            Log::error('Error handling payment info', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return ['success' => false, 'message' => '處理取號結果時發生錯誤'];
        }
    }

    /**
     * 延長用戶訂閱
     */
    public function extendUserSubscription(User $user, int $months): bool
    {
        try {
            DB::beginTransaction();

            $startDate = $user->hasActiveSubscription() || $user->isInTrialPeriod()
                ? $user->getSubscriptionExpiryDate()
                : now();

            $user->subscription_ends_at = $startDate->addDays($months * 30);
            $user->save();

            DB::commit();

            Log::info('User subscription extended', [
                'user_id' => $user->id,
                'months' => $months,
                'new_expiry_date' => $user->subscription_ends_at,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to extend user subscription', [
                'user_id' => $user->id,
                'months' => $months,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 管理員手動調整訂閱到期日
     */
    public function adjustExpiryDate(User $user, Carbon $newDate, string $reason): bool
    {
        try {
            DB::beginTransaction();

            $oldDate = $user->subscription_ends_at;
            $user->subscription_ends_at = $newDate;
            $user->save();

            // 記錄調整日誌
            Log::info('Subscription expiry date adjusted by admin', [
                'user_id' => $user->id,
                'admin_id' => auth()->id(),
                'old_date' => $oldDate,
                'new_date' => $newDate,
                'reason' => $reason,
            ]);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to adjust expiry date', [
                'user_id' => $user->id,
                'new_date' => $newDate,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 檢查過期訂單
     */
    public function checkExpiredOrders(): int
    {
        try {
            $expiredCount = SubscriptionOrder::where('status', 'pending')
                ->where('expire_date', '<', now())
                ->update(['status' => 'expired']);

            Log::info('Expired orders checked', [
                'expired_count' => $expiredCount,
                'timestamp' => now()->toISOString(),
            ]);

            return $expiredCount;
        } catch (\Exception $e) {
            Log::error('Failed to check expired orders', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * 發送到期提醒
     */
    public function sendExpiryReminders(): int
    {
        try {
            $users = User::whereHas('subscriptionOrders', function ($query) {
                $query->where('status', 'paid');
            })
            ->whereHas('subscription_ends_at')
            ->where('subscription_ends_at', '>', now())
            ->where('subscription_ends_at', '<=', now()->addDays(7))
            ->where(function ($query) {
                $query->whereNull('last_subscription_reminder_at')
                    ->orWhere('last_subscription_reminder_at', '<', now()->subDays(7));
            })
            ->get();

            $sentCount = 0;

            foreach ($users as $user) {
                $remainingDays = $user->getSubscriptionRemainingDays();

                // 發送到期提醒郵件
                try {
                    Mail::to($user->email)->queue(new SubscriptionExpiryReminderMail($user, $remainingDays));
                    Log::info('Subscription expiry reminder email sent', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'remaining_days' => $remainingDays,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to send subscription expiry reminder email', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'error' => $e->getMessage(),
                    ]);
                    // 繼續處理其他用戶
                }

                $user->markExpiryReminderSent();
                $sentCount++;

                Log::info('Subscription expiry reminder processed', [
                    'user_id' => $user->id,
                    'expiry_date' => $user->subscription_ends_at,
                    'remaining_days' => $remainingDays,
                ]);
            }

            Log::info('Subscription expiry reminders sent', [
                'sent_count' => $sentCount,
                'timestamp' => now()->toISOString(),
            ]);

            return $sentCount;
        } catch (\Exception $e) {
            Log::error('Failed to send expiry reminders', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * 發送試用期到期提醒
     */
    public function sendTrialExpiryReminders(): int
    {
        try {
            // 找到試用期即將結束的用戶（剩餘7天內）
            $users = User::where('is_trial_used', true)
                ->whereNotNull('trial_ends_at')
                ->where('trial_ends_at', '>', now())
                ->where('trial_ends_at', '<=', now()->addDays(7))
                ->where(function ($query) {
                    $query->whereNull('last_trial_reminder_at')
                        ->orWhere('last_trial_reminder_at', '<', now()->subDays(7));
                })
                ->get();

            $sentCount = 0;

            foreach ($users as $user) {
                $remainingDays = $user->getTrialRemainingDays();

                // 發送試用期到期提醒郵件
                try {
                    Mail::to($user->email)->queue(new TrialExpiryReminderMail($user, $remainingDays));
                    Log::info('Trial expiry reminder email sent', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'remaining_days' => $remainingDays,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to send trial expiry reminder email', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'error' => $e->getMessage(),
                    ]);
                    // 繼續處理其他用戶
                }

                // 更新最後提醒時間
                $user->last_trial_reminder_at = now();
                $user->save();
                $sentCount++;

                Log::info('Trial expiry reminder processed', [
                    'user_id' => $user->id,
                    'trial_ends_at' => $user->trial_ends_at,
                    'remaining_days' => $remainingDays,
                ]);
            }

            Log::info('Trial expiry reminders sent', [
                'sent_count' => $sentCount,
                'timestamp' => now()->toISOString(),
            ]);

            return $sentCount;
        } catch (\Exception $e) {
            Log::error('Failed to send trial expiry reminders', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * 取得用戶的待付款訂單
     */
    public function getPendingOrder(User $user): ?SubscriptionOrder
    {
        return $user->subscriptionOrders()
            ->where('status', 'pending')
            ->where('expire_date', '>', now())
            ->first();
    }

    /**
     * 取得用戶的所有待付款訂單
     */
    public function getPendingOrders(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return $user->subscriptionOrders()
            ->where('status', 'pending')
            ->where('expire_date', '>', now())
            ->latest()
            ->get();
    }

    /**
     * 檢查用戶是否可以建立新訂單（保留最後3筆待付款訂單）
     */
    public function canCreateNewOrder(User $user): bool
    {
        $pendingOrders = $this->getPendingOrders($user);
        return $pendingOrders->count() < 3;
    }

    /**
     * 取消最舊的待付款訂單以保留最新3筆
     */
    public function cancelOldestPendingOrders(User $user): int
    {
        $pendingOrders = $this->getPendingOrders($user);

        if ($pendingOrders->count() <= 3) {
            return 0;
        }

        $ordersToCancel = $pendingOrders->slice(3); // 保留前3筆，取消其餘的
        $cancelledCount = 0;

        foreach ($ordersToCancel as $order) {
            try {
                $order->status = 'cancelled';
                $order->notes = '系統自動取消：保留最新3筆待付款訂單';
                $order->save();

                Log::info('Oldest pending order cancelled to maintain limit', [
                    'cancelled_order_id' => $order->id,
                    'user_id' => $user->id,
                    'order_number' => $order->order_number,
                ]);

                $cancelledCount++;
            } catch (\Exception $e) {
                Log::error('Failed to cancel oldest pending order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $cancelledCount;
    }

    /**
     * 取得用戶訂閱統計
     */
    public function getUserSubscriptionStats(User $user): array
    {
        $orders = $user->subscriptionOrders;
        $paidOrders = $orders->where('status', 'paid');
        $totalAmount = $paidOrders->sum('total_amount');
        $totalMonths = $paidOrders->sum('months');

        // 取得訂閱開始日期
        $startDate = $this->getSubscriptionStartDate($user, $paidOrders);

        return [
            'total_orders' => $orders->count(),
            'paid_orders' => $paidOrders->count(),
            'total_amount' => $totalAmount,
            'total_months' => $totalMonths,
            'start_date' => $startDate?->format('Y-m-d'),
            'subscription_status' => $user->getSubscriptionStatus(),
            'subscription_label' => $user->getSubscriptionStatusLabel(),
            'remaining_days' => $user->getSubscriptionRemainingDays(),
            'expiry_date' => $user->getSubscriptionExpiryDate()?->format('Y-m-d'),
        ];
    }

    /**
     * 取得訂閱開始日期
     */
    private function getSubscriptionStartDate(User $user, $paidOrders): ?Carbon
    {
        // 如果在試用期，使用試用開始日期
        if ($user->isInTrialPeriod() && $user->trial_ends_at) {
            return $user->trial_ends_at->copy()->subDays(config('ecpay.trial_days', 30));
        }

        // 如果有付款訂單，使用第一個付款訂單的創建日期
        if ($paidOrders->count() > 0) {
            $firstPaidOrder = $paidOrders->sortBy('created_at')->first();
            return $firstPaidOrder->created_at;
        }

        // 如果有訂閱到期日，估算開始日期
        if ($user->subscription_ends_at) {
            return $user->subscription_ends_at->copy()->subDays($user->subscription_ends_at->diffInDays(now()));
        }

        return null;
    }
}