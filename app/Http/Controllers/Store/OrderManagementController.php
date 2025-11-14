<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * 店家訂單管理控制器
 * 處理店家端的訂單接收、狀態更新、管理等功能
 */
class OrderManagementController extends Controller
{
    protected PushNotificationService $pushService;

    public function __construct(PushNotificationService $pushService)
    {
        $this->pushService = $pushService;
    }
    /**
     * 顯示訂單管理主頁面（手機優化）
     */
    public function index(Request $request): View
    {
        // TODO: 實作店家驗證中介層
        // 目前先用 session 或 URL 參數取得店家資訊
        $storeSlug = $request->route('store_slug') ?? $request->input('store');

        $store = Store::where('store_slug_name', $storeSlug)
                      ->where('is_active', true)
                      ->firstOrFail();

        // 獲取今日營業結束時間
        $todayClosingTime = $store->getTodayClosingTime();
        $cutoffTime = $todayClosingTime ?? now()->endOfDay();

        // 查詢今日營業時間內的訂單（活動訂單）
        $pendingOrders = Order::where('store_id', $store->id)
            ->where('status', 'pending')
            ->where('created_at', '<=', $cutoffTime)
            ->with(['orderItems.menuItem'])
            ->orderBy('created_at', 'asc')
            ->get();

        $confirmedOrders = Order::where('store_id', $store->id)
            ->where('status', 'confirmed')
            ->where('created_at', '<=', $cutoffTime)
            ->with(['orderItems.menuItem'])
            ->orderBy('created_at', 'asc')
            ->get();

        $readyOrders = Order::where('store_id', $store->id)
            ->where('status', 'ready')
            ->where('created_at', '<=', $cutoffTime)
            ->with(['orderItems.menuItem'])
            ->orderBy('updated_at', 'desc')
            ->get();

        // 查詢歷史訂單（超過今日營業時間 + 所有已完成/已取消的訂單）
        $historicalOrders = Order::where('store_id', $store->id)
            ->where(function($query) use ($cutoffTime) {
                // 超過今日營業時間的未完成訂單
                $query->where('created_at', '<', $cutoffTime)
                      ->whereIn('status', ['pending', 'confirmed', 'ready'])
                      // 或者已完成/已取消的訂單
                      ->orWhereIn('status', ['completed', 'cancelled']);
            })
            ->with(['orderItems.menuItem'])
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->groupBy(function($order) {
                // 按日期分組
                return $order->created_at->format('Y-m-d');
            });

        return view('store.orders.index', compact(
            'store',
            'pendingOrders',
            'confirmedOrders',
            'readyOrders',
            'historicalOrders',
            'cutoffTime'
        ));
    }

    /**
     * 確認收單（pending -> confirmed）
     */
    public function confirm(Request $request, $storeSlug, $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->lockForUpdate()->firstOrFail();

        // 檢查訂單是否已被客戶取消
        if ($order->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => '此訂單已被客戶取消',
                'cancelled' => true,
                'order' => $order->load(['orderItems.menuItem'])
            ], 400);
        }

        // 驗證訂單狀態
        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => '訂單狀態不正確，無法確認'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $order->update([
                'status' => 'confirmed'
            ]);

            DB::commit();

            // 發送推播通知給客戶
            $this->sendPushNotification($order, 'confirmed');

            return response()->json([
                'success' => true,
                'message' => '已確認收單',
                'order' => $order->load(['orderItems.menuItem'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('確認訂單失敗', [
                'order_number' => $orderNumber,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '確認訂單失敗'
            ], 500);
        }
    }

    /**
     * 退單（pending/confirmed -> cancelled with rejected）
     * 允許新訂單和製作中訂單進行退單
     */
    public function reject(Request $request, $storeSlug, $orderNumber)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        // 驗證訂單狀態 - 允許 pending 和 confirmed 狀態的訂單退單
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => '訂單狀態不正確，無法退單'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $order->update([
                'status' => 'cancelled',
                'cancellation_type' => 'rejected',
                'cancellation_reason' => $request->input('reason', '店家無法接單'),
                'cancelled_at' => now()
            ]);

            DB::commit();

            // 發送退單通知給客戶
            $this->sendPushNotification($order, 'cancelled');

            return response()->json([
                'success' => true,
                'message' => '已退單',
                'order' => $order->load(['orderItems.menuItem'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('退單失敗', [
                'order_number' => $orderNumber,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '退單失敗'
            ], 500);
        }
    }

    /**
     * 完成製作（confirmed -> ready）
     */
    public function markReady(Request $request, $storeSlug, $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->lockForUpdate()->firstOrFail();

        // 檢查訂單是否已被客戶取消
        if ($order->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => '此訂單已被客戶取消',
                'cancelled' => true,
                'order' => $order->load(['orderItems.menuItem'])
            ], 400);
        }

        // 驗證訂單狀態
        if ($order->status !== 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => '訂單狀態不正確'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $order->update([
                'status' => 'ready'
            ]);

            DB::commit();

            // 發送取餐通知給客戶
            $this->sendPushNotification($order, 'ready');

            return response()->json([
                'success' => true,
                'message' => '訂單已完成製作，等待取餐',
                'order' => $order->load(['orderItems.menuItem'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('更新訂單狀態失敗', [
                'order_number' => $orderNumber,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '更新狀態失敗'
            ], 500);
        }
    }

    /**
     * 完成取餐（ready -> completed）
     */
    public function complete(Request $request, $storeSlug, $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        // 驗證訂單狀態
        if ($order->status !== 'ready') {
            return response()->json([
                'success' => false,
                'message' => '訂單狀態不正確'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $order->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '訂單已完成',
                'order' => $order->load(['orderItems.menuItem'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('完成訂單失敗', [
                'order_number' => $orderNumber,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '完成訂單失敗'
            ], 500);
        }
    }

    /**
     * 棄單（ready -> cancelled with abandoned）
     */
    public function abandon(Request $request, $storeSlug, $orderNumber)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        // 驗證訂單狀態
        if ($order->status !== 'ready') {
            return response()->json([
                'success' => false,
                'message' => '訂單狀態不正確，無法標記為棄單'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $order->update([
                'status' => 'cancelled',
                'cancellation_type' => 'abandoned',
                'cancellation_reason' => $request->input('reason', '客人未取餐'),
                'cancelled_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '已標記為棄單',
                'order' => $order->load(['orderItems.menuItem'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('棄單失敗', [
                'order_number' => $orderNumber,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '棄單失敗'
            ], 500);
        }
    }

    /**
     * 獲取訂單統計
     */
    public function getStats(Request $request, $storeSlug)
    {
        $store = Store::where('store_slug_name', $storeSlug)->firstOrFail();

        $stats = [
            'pending_count' => Order::where('store_id', $store->id)
                ->where('status', 'pending')
                ->count(),
            'confirmed_count' => Order::where('store_id', $store->id)
                ->where('status', 'confirmed')
                ->count(),
            'ready_count' => Order::where('store_id', $store->id)
                ->where('status', 'ready')
                ->count(),
            'today_completed' => Order::where('store_id', $store->id)
                ->where('status', 'completed')
                ->whereDate('completed_at', today())
                ->count(),
            'today_revenue' => Order::where('store_id', $store->id)
                ->where('status', 'completed')
                ->whereDate('completed_at', today())
                ->sum('total_amount'),
            // 新增詳細統計
            'today_cancelled' => Order::where('store_id', $store->id)
                ->where('status', 'cancelled')
                ->whereDate('cancelled_at', today())
                ->count(),
            'today_total_orders' => Order::where('store_id', $store->id)
                ->whereDate('created_at', today())
                ->count(),
            'completion_rate' => 0, // 會在下面計算
        ];

        // 計算完成率
        $todayTotal = $stats['today_total_orders'];
        if ($todayTotal > 0) {
            $stats['completion_rate'] = round(($stats['today_completed'] / $todayTotal) * 100, 1);
        }

        return response()->json($stats);
    }

    /**
     * 檢查新訂單（輪詢用）
     */
    public function checkNewOrders(Request $request, $storeSlug)
    {
        $store = Store::where('store_slug_name', $storeSlug)
                      ->where('is_active', true)
                      ->firstOrFail();

        // 獲取最後檢查時間（從請求參數）
        $lastCheckTime = $request->input('last_check_time');

        if ($lastCheckTime) {
            $lastCheckTime = \Carbon\Carbon::parse($lastCheckTime);
        } else {
            // 如果沒有提供，使用 30 秒前
            $lastCheckTime = now()->subSeconds(30);
        }

        // 查詢自上次檢查後的新訂單
        $newOrders = Order::where('store_id', $store->id)
            ->where('status', 'pending')
            ->where('created_at', '>', $lastCheckTime)
            ->with(['orderItems.menuItem'])
            ->orderBy('created_at', 'desc')
            ->get();

        // 獲取當前統計
        $stats = [
            'pending_count' => Order::where('store_id', $store->id)
                ->where('status', 'pending')
                ->count(),
            'confirmed_count' => Order::where('store_id', $store->id)
                ->where('status', 'confirmed')
                ->count(),
            'ready_count' => Order::where('store_id', $store->id)
                ->where('status', 'ready')
                ->count(),
            'today_revenue' => Order::where('store_id', $store->id)
                ->where('status', 'completed')
                ->whereDate('completed_at', today())
                ->sum('total_amount'),
        ];

        return response()->json([
            'has_new_orders' => $newOrders->count() > 0,
            'new_orders' => $newOrders,
            'new_orders_count' => $newOrders->count(),
            'stats' => $stats,
            'current_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * 發送推播通知（內部輔助方法）
     *
     * @param Order $order
     * @param string $status
     * @return void
     */
    private function sendPushNotification(Order $order, string $status): void
    {
        try {
            // 重新載入訂單關聯（確保有 customer 和 store）
            $order->load(['customer', 'store']);

            // 檢查訂單是否有關聯的顧客
            if (!$order->customer) {
                Log::info('訂單沒有關聯的顧客，無法發送推播通知', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'line_user_id' => $order->line_user_id
                ]);
                return;
            }

            // 發送推播通知
            $successCount = $this->pushService->sendOrderStatusNotification($order, $status);

            if ($successCount > 0) {
                Log::info('推播通知已發送', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $status,
                    'success_count' => $successCount
                ]);
            } else {
                Log::info('沒有發送推播通知', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $status,
                    'reason' => '沒有啟用的訂閱或用戶未啟用此類通知'
                ]);
            }
        } catch (\Exception $e) {
            // 推播失敗不應該影響訂單狀態變更
            Log::warning('推播通知發送失敗', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $status,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
