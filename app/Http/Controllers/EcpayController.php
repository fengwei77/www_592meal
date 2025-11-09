<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionService;
use App\Services\EcpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class EcpayController extends Controller
{
    private SubscriptionService $subscriptionService;
    private EcpayService $ecpayService;

    public function __construct(SubscriptionService $subscriptionService, EcpayService $ecpayService)
    {
        $this->subscriptionService = $subscriptionService;
        $this->ecpayService = $ecpayService;
    }

    /**
     * 接收綠界付款完成通知 (Server端)
     */
    public function returnUrl(Request $request)
    {
        try {
            Log::info('ECPay Return URL received', $request->all());

            // 驗證 CheckMacValue
            if (!$this->ecpayService->verifyCheckMacValue($request->all())) {
                Log::error('ECPay Return URL CheckMacValue verification failed', $request->all());
                // 即使驗證失敗也要回傳 "1|OK" 給綠界，避免重複發送
                return Response::make('1|OK')
                    ->header('Content-Type', 'text/plain');
            }

            // 處理付款結果
            $result = $this->subscriptionService->handlePaymentReturn($request->all());

            // 記錄處理結果
            Log::info('ECPay Return URL processed', [
                'success' => $result['success'],
                'message' => $result['message'] ?? '',
                'simulate' => $result['simulate'] ?? false,
            ]);

            // 檢查是否為模擬付款
            if (($request->input('SimulatePaid') ?? 0) == 1) {
                Log::info('ECPay Simulate payment received');
                return '1|Simulate payment received';
            }

            // 必須回傳 "1|OK" 給綠界
            return Response::make('1|OK')
                ->header('Content-Type', 'text/plain');
        } catch (\Exception $e) {
            Log::error('ECPay Return URL error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            // 即使發生錯誤也要回傳 "1|OK"，避免綠界重複發送
            return Response::make('1|OK')
                ->header('Content-Type', 'text/plain');
        }
    }

    /**
     * 接收綠界取號結果通知 (ATM/CVS/BARCODE)
     */
    public function paymentInfo(Request $request)
    {
        try {
            Log::info('ECPay PaymentInfo URL received', $request->all());

            // 驗證 CheckMacValue
            if (!$this->ecpayService->verifyCheckMacValue($request->all())) {
                Log::error('ECPay PaymentInfo CheckMacValue verification failed', $request->all());
                // 即使驗證失敗也要回傳 "1|OK" 給綠界，避免重複發送
                return Response::make('1|OK')
                    ->header('Content-Type', 'text/plain');
            }

            // 處理取號結果
            $result = $this->subscriptionService->handlePaymentInfo($request->all());

            // 記錄處理結果
            Log::info('ECPay PaymentInfo URL processed', [
                'success' => $result['success'],
                'message' => $result['message'] ?? '',
            ]);

            // 必須回傳 "1|OK" 給綠界
            return Response::make('1|OK')
                ->header('Content-Type', 'text/plain');
        } catch (\Exception $e) {
            Log::error('ECPay PaymentInfo URL error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            // 即使發生錯誤也要回傳 "1|OK"
            return Response::make('1|OK')
                ->header('Content-Type', 'text/plain');
        }
    }

    /**
     * 用戶端返回頁面 (Client端)
     */
    public function clientReturn(Request $request)
    {
        try {
            Log::info('ECPay Client Return received', $request->all());

            $merchantTradeNo = $request->input('MerchantTradeNo');
            $rtnCode = (int)$request->input('RtnCode', 0);
            $rtnMsg = $request->input('RtnMsg', '');

            // 查找訂單
            $order = \App\Models\SubscriptionOrder::where('order_number', $merchantTradeNo)->first();

            if (!$order) {
                return redirect()
                    ->route('filament.admin.resources.subscription-orders.index')
                    ->with('error', '找不到相關訂單資訊');
            }

            // 檢查是否為當前用戶的訂單
            if (auth()->check() && $order->user_id !== auth()->id()) {
                return redirect()
                    ->route('filament.admin.resources.subscription-orders.index')
                    ->with('error', '無權存取此訂單');
            }

            // 根據付款結果顯示不同頁面
            if ($rtnCode === 1 && ($request->input('SimulatePaid', 0) == 0)) {
                // 付款成功
                return redirect()
                    ->route('subscription.history')
                    ->with('success', '付款成功！訂閱已開通');
            } elseif ($request->input('SimulatePaid', 0) == 1) {
                // 模擬付款
                return redirect()
                    ->route('subscription.history')
                    ->with('info', '測試付款通知已接收');
            } else {
                // 付款失敗或處理中
                $errorMessage = $rtnMsg ?: '付款處理失敗';

                return redirect()
                    ->route('filament.admin.resources.subscription-orders.view', $order)
                    ->with('error', '付款失敗：' . $errorMessage);
            }
        } catch (\Exception $e) {
            Log::error('ECPay Client Return error', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return redirect()
                ->route('filament.admin.resources.subscription-orders.index')
                ->with('error', '處理付款結果時發生錯誤，請稍後再試');
        }
    }

    /**
     * 測試綠界回傳 (開發用)
     */
    public function testReturn(Request $request)
    {
        if (app()->environment('production')) {
            abort(404);
        }

        $testData = [
            'MerchantID' => config('ecpay.merchant_id'),
            'MerchantTradeNo' => 'TEST' . time(),
            'RtnCode' => 1,
            'RtnMsg' => '測試付款成功',
            'TradeNo' => 'TEST' . time(),
            'TradeAmt' => 100,
            'PaymentDate' => now()->format('Y/m/d H:i:s'),
            'PaymentType' => 'Credit_CreditCard',
            'PaymentTypeChargeFee' => 0,
            'TradeDate' => now()->format('Y/m/d H:i:s'),
            'SimulatePaid' => 1,
            'CheckMacValue' => 'test',
        ];

        Log::info('ECPay Test Return', $testData);

        return response()->json([
            'success' => true,
            'message' => '測試回傳已記錄',
            'data' => $testData,
        ]);
    }

    /**
     * 測試取號通知 (開發用)
     */
    public function testPaymentInfo(Request $request)
    {
        if (app()->environment('production')) {
            abort(404);
        }

        $testData = [
            'MerchantID' => config('ecpay.merchant_id'),
            'MerchantTradeNo' => 'TEST' . time(),
            'RtnCode' => 2,
            'RtnMsg' => '取號成功',
            'TradeNo' => 'TEST' . time(),
            'TradeAmt' => 100,
            'PaymentType' => 'ATM',
            'TradeDate' => now()->format('Y/m/d H:i:s'),
            'BankCode' => '007',
            'vAccount' => '9123456789012345',
            'ExpireDate' => now()->addDays(1)->format('Y/m/d'),
            'CheckMacValue' => 'test',
        ];

        Log::info('ECPay Test PaymentInfo', $testData);

        return response()->json([
            'success' => true,
            'message' => '測試取號通知已記錄',
            'data' => $testData,
        ]);
    }
}