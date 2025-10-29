<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * PushSubscriptionController
 * 處理推播訂閱相關的 API 請求
 */
class PushSubscriptionController extends Controller
{
    /**
     * 訂閱推播通知
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function subscribe(Request $request): JsonResponse
    {
        try {
            // 驗證請求資料
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:customers,id',
                'subscription' => 'required|array',
                'subscription.endpoint' => 'required|string',
                'subscription.keys' => 'required|array',
                'subscription.keys.p256dh' => 'required|string',
                'subscription.keys.auth' => 'required|string',
            ], [
                'customer_id.required' => '顧客 ID 必填',
                'customer_id.exists' => '顧客不存在',
                'subscription.required' => '訂閱資訊必填',
                'subscription.endpoint.required' => '推播端點必填',
                'subscription.keys.p256dh.required' => '加密公鑰必填',
                'subscription.keys.auth.required' => '認證密鑰必填',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => '驗證失敗',
                    'errors' => $validator->errors()
                ], 422);
            }

            $customer = Customer::findOrFail($request->customer_id);
            $subscriptionData = $request->subscription;

            // 檢查是否已存在相同的訂閱
            $existing = PushSubscription::where('endpoint', $subscriptionData['endpoint'])
                ->first();

            if ($existing) {
                // 更新現有訂閱
                $existing->update([
                    'customer_id' => $customer->id,
                    'p256dh_key' => $subscriptionData['keys']['p256dh'],
                    'auth_key' => $subscriptionData['keys']['auth'],
                    'user_agent' => $request->userAgent(),
                    'is_active' => true,
                    'last_used_at' => now(),
                ]);

                Log::info('推播訂閱已更新', [
                    'customer_id' => $customer->id,
                    'subscription_id' => $existing->id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => '推播訂閱已更新',
                    'subscription_id' => $existing->id
                ]);
            } else {
                // 建立新訂閱
                $newSubscription = PushSubscription::create([
                    'customer_id' => $customer->id,
                    'endpoint' => $subscriptionData['endpoint'],
                    'p256dh_key' => $subscriptionData['keys']['p256dh'],
                    'auth_key' => $subscriptionData['keys']['auth'],
                    'user_agent' => $request->userAgent(),
                    'is_active' => true,
                    'last_used_at' => now(),
                ]);

                Log::info('推播訂閱已建立', [
                    'customer_id' => $customer->id,
                    'subscription_id' => $newSubscription->id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => '推播訂閱成功',
                    'subscription_id' => $newSubscription->id
                ], 201);
            }
        } catch (\Exception $e) {
            Log::error('推播訂閱失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '推播訂閱失敗：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 取消推播訂閱
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'endpoint' => 'required|string',
            ], [
                'endpoint.required' => '推播端點必填',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => '驗證失敗',
                    'errors' => $validator->errors()
                ], 422);
            }

            $subscription = PushSubscription::where('endpoint', $request->endpoint)
                ->first();

            if ($subscription) {
                $subscription->update(['is_active' => false]);

                Log::info('推播訂閱已取消', [
                    'subscription_id' => $subscription->id,
                    'customer_id' => $subscription->customer_id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => '推播訂閱已取消'
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => '找不到訂閱記錄'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('取消推播訂閱失敗', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '取消訂閱失敗：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 查詢推播訂閱狀態
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        try {
            // 從認證用戶取得顧客 ID
            $customerId = $request->user('customer')->id ?? null;

            if (!$customerId) {
                return response()->json([
                    'subscribed' => false,
                    'subscription_count' => 0,
                    'message' => '用戶未登入'
                ], 401);
            }

            $hasActiveSubscription = PushSubscription::where('customer_id', $customerId)
                ->where('is_active', true)
                ->exists();

            $subscriptionCount = PushSubscription::where('customer_id', $customerId)
                ->where('is_active', true)
                ->count();

            return response()->json([
                'subscribed' => $hasActiveSubscription,
                'subscription_count' => $subscriptionCount
            ]);
        } catch (\Exception $e) {
            Log::error('查詢推播狀態失敗', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'subscribed' => false,
                'subscription_count' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 取得用戶的所有訂閱
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $customerId = $request->user('customer')->id ?? null;

            if (!$customerId) {
                return response()->json([
                    'success' => false,
                    'message' => '用戶未登入'
                ], 401);
            }

            $subscriptions = PushSubscription::where('customer_id', $customerId)
                ->where('is_active', true)
                ->get()
                ->map(function ($subscription) {
                    return [
                        'id' => $subscription->id,
                        'user_agent' => $subscription->user_agent,
                        'last_used_at' => $subscription->last_used_at?->format('Y-m-d H:i:s'),
                        'created_at' => $subscription->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'success' => true,
                'subscriptions' => $subscriptions
            ]);
        } catch (\Exception $e) {
            Log::error('取得訂閱列表失敗', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '取得訂閱列表失敗：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 刪除特定訂閱
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        try {
            $customerId = $request->user('customer')->id ?? null;

            if (!$customerId) {
                return response()->json([
                    'success' => false,
                    'message' => '用戶未登入'
                ], 401);
            }

            $subscription = PushSubscription::where('id', $id)
                ->where('customer_id', $customerId)
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => '找不到訂閱記錄'
                ], 404);
            }

            $subscription->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => '訂閱已刪除'
            ]);
        } catch (\Exception $e) {
            Log::error('刪除訂閱失敗', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '刪除訂閱失敗：' . $e->getMessage()
            ], 500);
        }
    }
}
