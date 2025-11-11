<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\SubscriptionOrder;
use App\Models\User;
use App\Models\Store;
use App\Services\ECPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ECPayPaymentController extends Controller
{
    private ECPayService $ecPayService;

    public function __construct(ECPayService $ecPayService)
    {
        $this->ecPayService = $ecPayService;
    }

    /**
     * 處理綠界金流回饋資訊
     */
    public function paymentInfo(Request $request)
    {
        Log::info('ECPay Payment Callback:', $request->all());

        // 格式化回饋資料
        $callbackData = $this->ecPayService->formatCallbackData($request->all());

        // 驗證回饋資料完整性
        $validationErrors = $this->ecPayService->validateCallbackData($callbackData);
        if (!empty($validationErrors)) {
            Log::error('ECPay Payment: Validation errors', $validationErrors);
            return $this->paymentError('資料驗證失敗: ' . implode(', ', $validationErrors), null);
        }

        // 驗證 CheckMacValue
        $ecPayConfig = $this->ecPayService->getECPayConfig();
        if (!$this->ecPayService->verifyCheckMacValue($callbackData, $ecPayConfig['hash_key'], $ecPayConfig['hash_iv'])) {
            Log::error('ECPay Payment: Invalid CheckMacValue');
            return $this->paymentError('資料驗證失敗', $callbackData['merchant_trade_no']);
        }

        // 記錄金流回饋
        $this->ecPayService->logPaymentCallback('info', 'ECPay payment callback received', $callbackData);

        $merchantTradeNo = $callbackData['merchant_trade_no'];
        $rtnCode = $callbackData['return_code'];
        $rtnMsg = $callbackData['return_message'];
        $tradeAmt = $callbackData['trade_amount'];
        $paymentDate = $callbackData['payment_date'];
        $paymentType = $callbackData['payment_type'];

        try {
            DB::beginTransaction();

            // 檢查是否為訂閱訂單（訂閱訂單通常以 SUB 開頭）
            if (str_starts_with($merchantTradeNo, 'SUB')) {
                $result = $this->processSubscriptionOrder($request);
            } else {
                $result = $this->processRegularOrder($request);
            }

            DB::commit();

            if ($result['success']) {
                // 設定 session 傳遞訂單資料到結果頁面
            session(['payment_result' => [
                'success' => true,
                'order' => $result['order'],
                'message' => '付款成功完成'
            ]]);

            return $this->paymentSuccess($result['order'], $request->all());
        } else {
            // 設定 session 傳遞錯誤資料到結果頁面
            session(['payment_result' => [
                'success' => false,
                'order_number' => $merchantTradeNo,
                'message' => $result['message']
            ]]);

            return $this->paymentError($result['message'], $merchantTradeNo);
        }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ECPay Payment Error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->paymentError('系統錯誤：' . $e->getMessage(), $merchantTradeNo);
        }
    }

    /**
     * 處理一般訂單
     */
    private function processRegularOrder(Request $request): array
    {
        $merchantTradeNo = $request->input('MerchantTradeNo');
        $rtnCode = $request->input('RtnCode');
        $tradeAmt = $request->input('TradeAmt');
        $paymentDate = $request->input('PaymentDate');

        // 尋找訂單
        $order = Order::where('order_number', $merchantTradeNo)->first();

        if (!$order) {
            return ['success' => false, 'message' => '找不到訂單'];
        }

        // 驗證金額
        if ((int)$order->total_amount !== (int)$tradeAmt) {
            return ['success' => false, 'message' => '金額不符'];
        }

        // 檢查訂單狀態，避免重複處理
        if ($order->payment_status === 'paid') {
            return ['success' => true, 'order' => $order];
        }

        // 根據回饋代碼更新訂單狀態
        if ($this->ecPayService->isPaymentSuccessful($rtnCode)) { // 交易成功
            $order->update([
                'payment_status' => 'paid',
                'payment_method' => $this->getPaymentMethodName($paymentType),
                'payment_date' => now(),
                'ecpay_trade_no' => $callbackData['trade_no'],
                'ecpay_payment_date' => $this->ecPayService->parsePaymentDate($paymentDate),
                'status' => 'completed'
            ]);

            $this->ecPayService->logPaymentCallback('info', 'Order payment successful', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'amount' => $tradeAmt
            ]);

            return ['success' => true, 'order' => $order];

        } else { // 交易失敗
            $order->update([
                'payment_status' => 'failed',
                'payment_method' => $this->getPaymentMethodName($paymentType),
                'ecpay_trade_no' => $callbackData['trade_no'],
                'ecpay_payment_date' => $this->ecPayService->parsePaymentDate($paymentDate),
                'failure_reason' => $rtnMsg,
                'status' => 'failed'
            ]);

            $this->ecPayService->logPaymentCallback('warning', 'Order payment failed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'rtn_code' => $rtnCode,
                'rtn_msg' => $rtnMsg
            ]);

            return ['success' => false, 'message' => $this->ecPayService->getPaymentStatusDescription($rtnCode, $rtnMsg)];
        }
    }

    /**
     * 處理訂閱訂單
     */
    private function processSubscriptionOrder(Request $request): array
    {
        $merchantTradeNo = $request->input('MerchantTradeNo');
        $rtnCode = $request->input('RtnCode');
        $tradeAmt = $request->input('TradeAmt');

        // 尋找訂閱訂單
        $subscriptionOrder = SubscriptionOrder::where('order_number', $merchantTradeNo)->first();

        if (!$subscriptionOrder) {
            return ['success' => false, 'message' => '找不到訂閱訂單'];
        }

        // 驗證金額
        if ((int)$subscriptionOrder->amount !== (int)$tradeAmt) {
            return ['success' => false, 'message' => '金額不符'];
        }

        // 檢查訂單狀態
        if ($subscriptionOrder->status === 'paid') {
            return ['success' => true, 'order' => $subscriptionOrder];
        }

        // 根據回饋代碼更新訂單狀態
        if ($this->ecPayService->isPaymentSuccessful($rtnCode)) { // 交易成功
            $subscriptionOrder->update([
                'status' => 'paid',
                'payment_method' => $this->getPaymentMethodName($paymentType),
                'paid_at' => now(),
                'ecpay_trade_no' => $callbackData['trade_no'],
                'ecpay_payment_date' => $this->ecPayService->parsePaymentDate($paymentDate),
            ]);

            // 更新用戶訂閱狀態
            $user = $subscriptionOrder->user;
            if ($user) {
                $user->update([
                    'subscription_status' => 'active',
                    'subscription_ends_at' => now()->addMonths(1), // 假設月付訂閱
                ]);
            }

            Log::info('Subscription order payment successful', [
                'subscription_order_id' => $subscriptionOrder->id,
                'order_number' => $subscriptionOrder->order_number,
                'amount' => $tradeAmt,
                'user_id' => $user?->id
            ]);

            return ['success' => true, 'order' => $subscriptionOrder];

        } else { // 交易失敗
            $subscriptionOrder->update([
                'status' => 'failed',
                'payment_method' => $this->getPaymentMethodName($paymentType),
                'ecpay_trade_no' => $callbackData['trade_no'],
                'ecpay_payment_date' => $this->ecPayService->parsePaymentDate($paymentDate),
                'failure_reason' => $rtnMsg,
            ]);

            $this->ecPayService->logPaymentCallback('warning', 'Subscription order payment failed', [
                'subscription_order_id' => $subscriptionOrder->id,
                'order_number' => $subscriptionOrder->order_number,
                'rtn_code' => $rtnCode,
                'rtn_msg' => $rtnMsg
            ]);

            return ['success' => false, 'message' => $this->ecPayService->getPaymentStatusDescription($rtnCode, $rtnMsg)];
        }
    }

    /**
     * 取得付款方式名稱
     */
    private function getPaymentMethodName(string $paymentType): string
    {
        $paymentTypes = [
            'Credit' => '信用卡',
            'Credit_3D' => '信用卡(3D驗證)',
            'Credit_3D_Redirect' => '信用卡(3D驗證跳轉)',
            'WebATM' => '網路ATM',
            'ATM' => 'ATM',
            'CVS' => '超商代碼',
            'BARCODE' => '超商條碼',
            'AndroidPay' => 'Android Pay',
            'GooglePay' => 'Google Pay',
            'SamsungPay' => 'Samsung Pay',
            'LINEPay' => 'LINE Pay',
            'TopUpUsed' => '錢包餘額',
        ];

        return $paymentTypes[$paymentType] ?? $paymentType;
    }

    /**
     * 付款成功回應
     */
    private function paymentSuccess($order, array $callbackData)
    {
        // 設定 session 傳遞訂單資料到結果頁面
        session(['payment_result' => [
            'success' => true,
            'order' => $order,
            'message' => '付款成功完成'
        ]);

        // 返回重定向頁面
        return view('ecpay.payment-redirect');
    }

    /**
     * 付款失敗回應
     */
    private function paymentError(string $message, ?string $orderNumber)
    {
        // 設定 session 傳遞錯誤資料到結果頁面
        session(['payment_result' => [
            'success' => false,
            'order_number' => $orderNumber,
            'message' => $message
        ]]);

        // 返回重定向頁面
        return view('ecpay.payment-redirect');
    }

    /**
     * 顯示付款結果頁面 (Web端)
     */
    public function showPaymentResult(Request $request)
    {
        // 從 session 取得付款結果 (支援兩種 session key)
        $paymentResult = session('payment_result') ?: session('ecpay_payment_result');

        if (!$paymentResult) {
            // 如果沒有 session 資料，檢查 URL 參數 (支援 GET 和 POST)
            $orderNumber = $request->input('order_number');
            $success = $request->input('success', false);
            $message = $request->input('message', '');

            if (!$orderNumber) {
                return view('ecpay.payment-result', [
                    'success' => false,
                    'message' => '找不到付款結果資訊',
                    'orderNumber' => null,
                ]);
            }

            // 取得訂單資訊
            $order = Order::where('order_number', $orderNumber)->first();
            if (!$order) {
                $order = SubscriptionOrder::where('order_number', $orderNumber)->first();
            }

            return view('ecpay.payment-result', [
                'success' => $success,
                'message' => $message,
                'orderNumber' => $orderNumber,
                'order' => $order,
            ]);
        }

        // 從 session 取得資料
        $success = $paymentResult['success'];
        $message = $paymentResult['message'];
        $order = $paymentResult['order'] ?? null;
        $orderNumber = $paymentResult['order_number'] ?? ($order ? $order->order_number : null);

        // 清除 session 中的付款結果
        session()->forget('payment_result');
        session()->forget('ecpay_payment_result');

        return view('ecpay.payment-result', [
            'success' => $success,
            'message' => $message,
            'orderNumber' => $orderNumber,
            'order' => $order,
        ]);
    }
}