<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class EcpayTestController extends Controller
{
    /**
     * 設定付款測試 session
     */
    public function setPaymentSession(Request $request)
    {
        $data = $request->all();

        // 設定測試用的付款結果 session
        $paymentResult = [
            'success' => $data['success'] ?? false,
            'order_number' => $data['order_number'] ?? null,
            'message' => $data['message'] ?? '',
            'order' => null
        ];

        // 如果有訂單編號，嘗試找到訂單
        if (!empty($data['order_number'])) {
            $order = \App\Models\Order::where('order_number', $data['order_number'])->first();
            if (!$order) {
                $order = \App\Models\SubscriptionOrder::where('order_number', $data['order_number'])->first();
            }
            $paymentResult['order'] = $order;
        }

        session(['ecpay_payment_result' => $paymentResult]);

        return response()->json([
            'success' => true,
            'message' => '測試 session 已設定',
            'data' => $paymentResult
        ]);
    }

    /**
     * 清除付款測試 session
     */
    public function clearPaymentSession()
    {
        session()->forget('ecpay_payment_result');
        session()->forget('payment_result');

        return response()->json([
            'success' => true,
            'message' => '測試 session 已清除'
        ]);
    }

    /**
     * 顯示測試頁面
     */
    public function showTestPage()
    {
        return view('test.ecpay-payment-test');
    }
}