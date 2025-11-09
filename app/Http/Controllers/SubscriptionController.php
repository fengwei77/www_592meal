<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionOrder;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Services\EcpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class SubscriptionController extends Controller
{
    private SubscriptionService $subscriptionService;
    private EcpayService $ecpayService;

    public function __construct(SubscriptionService $subscriptionService, EcpayService $ecpayService)
    {
        $this->subscriptionService = $subscriptionService;
        $this->ecpayService = $ecpayService;
    }

    /**
     * 訂閱服務首頁
     */
    public function index()
    {
        // 檢查用戶權限
        if (!Auth::user()->hasRole('store_owner')) {
            abort(403, '無權存取此頁面');
        }

        $user = Auth::user();
        $pendingOrder = $this->subscriptionService->getPendingOrder($user);
        $subscriptionStats = $this->subscriptionService->getUserSubscriptionStats($user);

        return view('subscription.index', compact('user', 'pendingOrder', 'subscriptionStats'));
    }

    /**
     * 建立訂單紀錄
     */
    public function createOrder(Request $request)
    {
        $request->validate([
            'months' => 'required|integer|min:1|max:18',
        ], [
            'months.required' => '請選擇訂閱月數',
            'months.min' => '訂閱月數至少為1個月',
            'months.max' => '訂閱月數最多為18個月',
        ]);

        $user = Auth::user();
        $months = (int)$request->input('months');

        $result = $this->subscriptionService->createSubscriptionOrder($user, $months);

        if (!$result['success']) {
            return back()
                ->with('error', $result['message'])
                ->withInput();
        }

        return redirect()
            ->route('subscription.order-created', $result['order'])
            ->with('success', '訂單建立成功');
    }

    /**
     * 訂單建立成功確認頁面
     */
    public function orderCreated($orderId)
    {
        $user = Auth::user();

        // 檢查用戶權限
        if (!$user->hasRole('store_owner')) {
            abort(403, '無權存取此頁面');
        }

        // 明確查找訂單
        $order = SubscriptionOrder::where('id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            abort(404, '訂單不存在');
        }

        // 檢查訂單狀態
        if ($order->status !== 'pending') {
            return redirect()
                ->route('subscription.history')
                ->with('info', '此訂單無法進行付款');
        }

        // 檢查訂單是否已過期
        if ($order->isExpired()) {
            return redirect()
                ->route('subscription.renew')
                ->with('error', '訂單已過期，請重新訂閱');
        }

        return view('subscription.order-created', compact('order'));
    }

    /**
     * Filament 管理頁面付款確認頁面
     */
    public function paymentConfirm($orderId)
    {
        $user = Auth::user();

        // 檢查用戶權限
        if (!$user->hasRole('store_owner')) {
            abort(403, '無權存取此頁面');
        }

        // 明確查找訂單
        $order = SubscriptionOrder::where('id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            abort(404, '訂單不存在');
        }

        // 檢查訂單狀態
        if ($order->status !== 'pending') {
            return redirect()
                ->route('subscription.history')
                ->with('info', '此訂單無法進行付款');
        }

        // 檢查訂單是否已過期
        if ($order->isExpired()) {
            return redirect()
                ->route('subscription.renew')
                ->with('error', '訂單已過期，請重新訂閱');
        }

        return view('subscription.payment-confirm', compact('order'));
    }

    /**
     * 續約頁面
     */
    public function renew()
    {
        $user = Auth::user();

        // 檢查是否有正在處理的訂單，如果有的話在訂閱頁面顯示提示
        $pendingOrder = $this->subscriptionService->getPendingOrder($user);

        // 訂閱方案 (1-18個月)
        $plans = [];
        $pricePerMonth = 50;

        for ($i = 1; $i <= 18; $i++) {
            $totalPrice = $i * $pricePerMonth;
            $description = "{$i}個月";

            // 添加特別標籤給常見選項
            switch ($i) {
                case 1:
                    $description .= " (月付)";
                    break;
                case 3:
                    $description .= " (季度)";
                    break;
                case 6:
                    $description .= " (半年)";
                    break;
                case 12:
                    $description .= " (年付)";
                    break;
                case 18:
                    $description .= " (最優惠)";
                    break;
            }

            $plans[$i] = [
                'months' => $i,
                'price' => $totalPrice,
                'description' => $description
            ];
        }

        // 取得訂閱統計
        $subscriptionStats = $this->subscriptionService->getUserSubscriptionStats($user);

        return view('subscription.renew', compact('plans', 'subscriptionStats', 'user', 'pendingOrder'));
    }

    /**
     * 確認訂單頁面
     */
    public function confirm($orderId)
    {
        $user = Auth::user();

        // 檢查用戶權限
        if (!$user->hasRole('store_owner')) {
            abort(403, '無權存取此頁面');
        }

        // 明確查找訂單
        $order = SubscriptionOrder::where('id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            abort(404, '訂單不存在');
        }

        // 檢查訂單狀態
        if ($order->status !== 'pending') {
            return redirect()
                ->route('subscription.history')
                ->with('error', '此訂單無法進行付款');
        }

        // 檢查訂單是否已過期
        if ($order->isExpired()) {
            return redirect()
                ->route('subscription.history')
                ->with('error', '訂單已過期，請重新訂閱');
        }

        try {
            // 產生付款表單HTML (使用新的統一方法)
            $paymentForm = $this->subscriptionService->generatePaymentForm($order);

            return view('subscription.confirm', compact('order', 'paymentForm'));
        } catch (\Exception $e) {
            \Log::error('Error generating payment form', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', '產生付款表單時發生錯誤，請稍後再試');
        }
    }

    /**
     * 前往綠界付款
     */
    public function pay($orderId, Request $request)
    {
        $user = Auth::user();

        // 明確查找訂單
        $order = SubscriptionOrder::where('id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            abort(404, '訂單不存在');
        }

        // 檢查訂單狀態
        if ($order->status !== 'pending') {
            return redirect()
                ->route('subscription.history')
                ->with('error', '此訂單無法進行付款');
        }

        // 檢查訂單是否已過期
        if ($order->isExpired()) {
            return redirect()
                ->route('subscription.history')
                ->with('error', '訂單已過期，請重新訂閱');
        }

        try {
            // 生成綠界付款參數
            $params = $this->subscriptionService->generateEcpayParams($order);

            // 產生付款表單HTML
            $formHtml = $this->ecpayService->generateSubmitForm($params);

            return response($formHtml);
        } catch (\Exception $e) {
            \Log::error('Error generating payment form', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', '產生付款表單時發生錯誤，請稍後再試');
        }
    }

    /**
     * 訂閱歷史紀錄
     */
    public function history()
    {
        $user = Auth::user();
        $orders = $user->subscriptionOrders()
            ->latest()
            ->paginate(10);

        $subscriptionStats = $this->subscriptionService->getUserSubscriptionStats($user);

        return view('subscription.history', compact('orders', 'subscriptionStats'));
    }

    /**
     * 重新繳費 (過期訂單)
     */
    public function repay($orderId)
    {
        $user = Auth::user();

        // 明確查找訂單
        $order = SubscriptionOrder::where('id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            abort(404, '訂單不存在');
        }

        // 檢查訂單是否可以重新繳費
        if (!$order->canRepay()) {
            return redirect()
                ->route('subscription.history')
                ->with('error', '此訂單無法重新繳費');
        }

        try {
            // 建立新的訂單
            $result = $this->subscriptionService->createSubscriptionOrder($user, $order->months);

            if (!$result['success']) {
                return back()
                    ->with('error', $result['message']);
            }

            return redirect()
                ->route('subscription.confirm', $result['order'])
                ->with('success', '新訂單建立成功');
        } catch (\Exception $e) {
            \Log::error('Error creating repay order', [
                'original_order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', '建立新訂單時發生錯誤，請稍後再試');
        }
    }

    /**
     * 顯示訂單詳情 - 重定向到後台 Filament 頁面
     */
    public function showOrder($orderId)
    {
        $user = Auth::user();

        // 明確查找訂單
        $order = SubscriptionOrder::where('id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            abort(404, '訂單不存在');
        }

        // 重定向到 Filament 後台訂單詳情頁
        return redirect()->route('filament.admin.resources.subscription-orders.view', $order);
    }

    /**
     * 取消訂單 (僅限待付款訂單)
     */
    public function cancel($orderId, Request $request)
    {
        $user = Auth::user();

        // 明確查找訂單
        $order = SubscriptionOrder::where('id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            abort(404, '訂單不存在');
        }

        // 只能取消待付款訂單
        if ($order->status !== 'pending') {
            return back()
                ->with('error', '只能取消待付款的訂單');
        }

        try {
            $order->status = 'cancelled';
            $order->notes = $request->input('reason', '用戶自行取消');
            $order->save();

            \Log::info('Order cancelled by user', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'reason' => $order->notes,
            ]);

            return redirect()
                ->route('subscription.history')
                ->with('success', '訂單已取消');
        } catch (\Exception $e) {
            \Log::error('Error cancelling order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', '取消訂單時發生錯誤，請稍後再試');
        }
    }

    /**
     * 取得訂閱狀態 (AJAX)
     */
    public function getStatus(Request $request)
    {
        $user = Auth::user();

        return response()->json([
            'status' => $user->getSubscriptionStatus(),
            'label' => $user->getSubscriptionStatusLabel(),
            'color' => $user->getSubscriptionStatusColor(),
            'remaining_days' => $user->getSubscriptionRemainingDays(),
            'expiry_date' => $user->getSubscriptionExpiryDate()?->format('Y-m-d'),
            'is_trial' => $user->isInTrialPeriod(),
        ]);
    }
}