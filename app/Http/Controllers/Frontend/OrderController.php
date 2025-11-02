<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\CustomerOrderLock;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderCancellationLog;
use App\Models\OrderItem;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * 顯示結帳頁面
     */
    public function create(Request $request, $store_slug)
    {
        // 檢查用戶是否被鎖定訂餐功能
        if (session('line_logged_in') && session('line_user')) {
            $lineUserId = session('line_user.user_id');
            if (CustomerOrderLock::isLocked($lineUserId)) {
                $lock = CustomerOrderLock::getLock($lineUserId);
                $lockedUntil = $lock->locked_until->format('Y-m-d H:i:s');
                return redirect()->route('frontend.store.detail', $store_slug)
                    ->with('error', "您因取消訂單次數過多，已被暫停訂餐功能至 {$lockedUntil}");
            }
        }

        // 從路由參數獲取店家
        $store = \App\Models\Store::where('store_slug_name', $store_slug)
                                    ->where('is_active', true)
                                    ->firstOrFail();

        // 生成表單時間戳記，用於防重複提交
        $formTimestamp = time();

        $cartService = new CartService();

        if ($cartService->isEmpty()) {
            return redirect()->route('frontend.store.detail', $store_slug)
                ->with('warning', '購物車是空的，請先添加商品');
        }

        // 使用 CartService 驗證並獲取購物車內容
        $validation = $cartService->validateCart($store->id);

        if (!$validation['is_valid']) {
            $warningMessage = '購物車中的商品有問題：';
            foreach ($validation['invalid_items'] as $invalidItem) {
                $warningMessage .= "\n• {$invalidItem['name']} - {$invalidItem['reason']}";
            }

            return redirect()->route('frontend.cart.index')
                ->with('warning', $warningMessage);
        }

        $cartItems = $validation['valid_items'];
        $total = $validation['total_amount'];

        return view('frontend.order.create', compact('store', 'cartItems', 'total', 'formTimestamp'));
    }

    /**
     * 建立訂單
     */
    public function store(Request $request, $store_slug)
    {
        $request->validate([
            'customer_name' => 'required|string|max:100',
            'customer_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
        ], [
            'customer_name.required' => '請填寫您的姓名',
            'customer_name.max' => '姓名不能超過 100 個字元',
            'customer_phone.max' => '電話不能超過 20 個字元',
            'notes.max' => '備註不能超過 500 個字元',
        ]);

        // 檢查用戶是否被鎖定訂餐功能
        if (session('line_logged_in') && session('line_user')) {
            $lineUserId = session('line_user.user_id');
            if (CustomerOrderLock::isLocked($lineUserId)) {
                $lock = CustomerOrderLock::getLock($lineUserId);
                $lockedUntil = $lock->locked_until->format('Y-m-d H:i:s');
                return back()->with('error', "您因取消訂單次數過多，已被暫停訂餐功能至 {$lockedUntil}");
            }
        }

        // 從路由參數獲取店家
        $store = \App\Models\Store::where('store_slug_name', $store_slug)
                                    ->where('is_active', true)
                                    ->firstOrFail();

        $cartService = new CartService();

        if ($cartService->isEmpty()) {
            return redirect()->route('frontend.store.detail', $store_slug)
                ->with('error', '購物車是空的');
        }

        try {
            DB::beginTransaction();

            // 驗證購物車
            $validation = $cartService->validateCart($store->id);

            if (!$validation['is_valid']) {
                DB::rollBack();
                return back()->with('error', '購物車中包含無效商品，請重新確認');
            }

            // 檢查是否為預訂單
            $scheduleInfo = $store->getOrderScheduleInfo();

            // 準備訂單資料
            $orderData = [
                'store_id' => $store->id,
                'order_number' => $this->generateOrderNumber(),
                'customer_name' => $request->input('customer_name'),
                'customer_phone' => $request->input('customer_phone'),
                'notes' => $request->input('notes'),
                'status' => 'pending',
                'total_amount' => $validation['total_amount'],
                'is_scheduled_order' => $scheduleInfo['is_scheduled'],
                'scheduled_for' => $scheduleInfo['scheduled_date'],
            ];

            // 如果有 LINE 登入資訊，添加到訂單
            if (session('line_logged_in') && session('line_user')) {
                $lineUser = session('line_user');
                $orderData['line_user_id'] = $lineUser['user_id'] ?? null;
                $orderData['line_display_name'] = $lineUser['display_name'] ?? null;
                $orderData['line_picture_url'] = $lineUser['picture_url'] ?? null;

                // 根據 LINE ID 查找或建立 Customer 記錄
                if (!empty($lineUser['user_id'])) {
                    $customer = \App\Models\Customer::firstOrCreate(
                        ['line_id' => $lineUser['user_id']],
                        [
                            'name' => $lineUser['display_name'] ?? '顧客',
                            'avatar_url' => $lineUser['picture_url'] ?? null,
                        ]
                    );
                    $orderData['customer_id'] = $customer->id;
                }
            }

            // 建立訂單
            $order = Order::create($orderData);

            // 建立訂單項目
            foreach ($validation['valid_items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'total_price' => $item['subtotal'],
                ]);
            }

            // 清空購物車
            $cartService->clear();

            DB::commit();

            // 準備成功訊息
            $successMessage = $scheduleInfo['is_scheduled']
                ? '訂單已送出！' . $scheduleInfo['message']
                : '訂單建立成功！';

            return redirect()->route('frontend.order.confirmed', ['store_slug' => $store_slug, 'order' => $order])
                ->with('success', $successMessage)
                ->with('schedule_info', $scheduleInfo);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('訂單建立失敗', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return back()->with('error', '訂單建立失敗，請稍後再試');
        }
    }

    /**
     * 舊的 index 方法 - 已停用
     */
    public function indexOld(Request $request)
    {
        // 此方法已停用
    }

    /**
     * 顯示用戶的訂單歷史列表 - 全新版本
     */
    public function index(Request $request)
    {
        // 檢查是否已登入 LINE
        if (!session('line_logged_in') || !session('line_user')) {
            return redirect()->route('login')
                ->with('error', '請先登入 LINE 以查看您的訂單');
        }

        $lineUserId = session('line_user.user_id');

        // 查詢該用戶的所有訂單，按建立時間倒序排列
        $orders = Order::where('line_user_id', $lineUserId)
            ->with(['store', 'orderItems.menuItem'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('frontend.order.index', compact('orders'));
    }

    /**
     * 舊的 show 方法 - 已停用
     */
    public function showOld(Request $request, $orderNumber)
    {
        // 此方法已停用
    }

    /**
     * 顯示訂單詳情 - 全新版本
     */
    public function show(Request $request, $orderNumber)
    {
        // 檢查是否已登入 LINE
        if (!session('line_logged_in') || !session('line_user')) {
            return redirect()->route('login')
                ->with('error', '請先登入 LINE');
        }

        $lineUserId = session('line_user.user_id');

        // 查詢訂單並確認屬於當前用戶
        $order = Order::where('order_number', $orderNumber)
            ->where('line_user_id', $lineUserId)
            ->with(['store', 'orderItems.menuItem'])
            ->firstOrFail();

        return view('frontend.order.show', compact('order'));
    }

    /**
     * 顯示訂單確認頁面
     */
    public function confirmed(Request $request, $store_slug, Order $order): View
    {
        // 從路由參數獲取店家
        $store = \App\Models\Store::where('store_slug_name', $store_slug)
                                    ->where('is_active', true)
                                    ->firstOrFail();

        // 確認訂單屬於當前店家
        if ($order->store_id !== $store->id) {
            abort(404, '訂單不存在');
        }

        // 載入訂單項目
        $order->load(['orderItems.menuItem']);

        return view('frontend.order.confirmed', compact('store', 'order'));
    }

    /**
     * 生成訂單編號
     */
    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD' . date('Ymd') . strtoupper(Str::random(6));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * 取消訂單
     */
    public function cancel(Request $request, $orderNumber)
    {
        // 檢查是否已登入 LINE
        if (!session('line_logged_in') || !session('line_user')) {
            return response()->json([
                'success' => false,
                'message' => '請先登入 LINE'
            ], 401);
        }

        $lineUserId = session('line_user.user_id');

        try {
            DB::beginTransaction();

            // 檢查用戶是否被鎖定
            if (CustomerOrderLock::isLocked($lineUserId)) {
                $lock = CustomerOrderLock::getLock($lineUserId);
                $lockedUntil = $lock->locked_until->format('Y-m-d H:i:s');

                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "您因取消訂單次數過多，已被暫停訂餐功能至 {$lockedUntil}",
                    'locked_until' => $lockedUntil
                ], 403);
            }

            // 查詢訂單並確認屬於當前用戶
            $order = Order::where('order_number', $orderNumber)
                ->where('line_user_id', $lineUserId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => '訂單不存在'
                ], 404);
            }

            // 檢查訂單是否可以取消 (只有 pending 或 confirmed 狀態可以取消)
            if (!in_array($order->status, ['pending', 'confirmed'])) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => '此訂單目前狀態無法取消'
                ], 400);
            }

            // 檢查30分鐘內的取消次數
            $recentCancellations = OrderCancellationLog::getCancellationCount($lineUserId, 30);
            if ($recentCancellations >= 3) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => '您在30分鐘內已取消3次訂單，請稍後再試'
                ], 429);
            }

            // 檢查今日的取消次數
            $todayCancellations = OrderCancellationLog::getTodayCancellationCount($lineUserId);
            if ($todayCancellations >= 10) {
                // 鎖定用戶24小時
                CustomerOrderLock::lockUser($lineUserId, 24, $todayCancellations + 1);

                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => '您今日已取消10次訂單，已被暫停訂餐功能24小時',
                    'locked' => true
                ], 429);
            }

            // 更新訂單狀態為已取消
            $order->update([
                'status' => 'cancelled',
                'cancellation_type' => 'customer_cancelled',
                'cancellation_reason' => '客戶取消訂單',
                'cancelled_at' => now(),
            ]);

            // 記錄取消動作
            OrderCancellationLog::logCancellation(
                $lineUserId,
                $order->id,
                $request->ip()
            );

            DB::commit();

            // 檢查是否需要警告用戶
            $newTodayCount = $todayCancellations + 1;
            $warningMessage = null;

            if ($newTodayCount >= 8) {
                $remainingCount = 10 - $newTodayCount;
                $warningMessage = "提醒：您今日已取消 {$newTodayCount} 次訂單，再取消 {$remainingCount} 次將被暫停訂餐功能24小時";
            } elseif ($recentCancellations + 1 >= 2) {
                $remainingCount = 3 - ($recentCancellations + 1);
                $warningMessage = "提醒：您在30分鐘內已取消 " . ($recentCancellations + 1) . " 次訂單，再取消 {$remainingCount} 次將無法繼續取消訂單";
            }

            return response()->json([
                'success' => true,
                'message' => '訂單已成功取消',
                'warning' => $warningMessage,
                'cancellation_stats' => [
                    'today_count' => $newTodayCount,
                    'recent_count' => $recentCancellations + 1
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('訂單取消失敗', [
                'error' => $e->getMessage(),
                'order_number' => $orderNumber,
                'line_user_id' => $lineUserId,
            ]);

            return response()->json([
                'success' => false,
                'message' => '訂單取消失敗，請稍後再試'
            ], 500);
        }
    }

    /**
     * API: 檢查訂單狀態
     */
    public function checkStatus(Request $request, $orderNumber)
    {
        $store = $request->get('current_store');

        $order = Order::where('order_number', $orderNumber)
                      ->where('store_id', $store->id)
                      ->first();

        if (!$order) {
            return response()->json([
                'error' => '訂單不存在'
            ], 404);
        }

        return response()->json([
            'order_number' => $order->order_number,
            'status' => $order->status,
            'total_amount' => $order->total_amount,
            'created_at' => $order->created_at->format('Y-m-d H:i:s'),
        ]);
    }
}