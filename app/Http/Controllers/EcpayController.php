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

                // 即使驗證失敗也顯示處理中頁面，避免重複發送
                $html = '<!DOCTYPE html>';
                $html .= '<html lang="zh-TW">';
                $html .= '<head>';
                $html .= '    <meta charset="UTF-8">';
                $html .= '    <meta name="viewport" content="width=device-width, initial-scale=1.0">';
                $html .= '    <title>付款處理中 - 592Meal</title>';
            $html .= '    <meta http-equiv="refresh" content="3; url=/ecpay/payment-result">';
                $html .= '    <script src="https://cdn.tailwindcss.com"></script>';
                $html .= '    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;700&display=swap" rel="stylesheet">';
                $html .= '    <style>';
                $html .= '        body { font-family: "Noto Sans TC", sans-serif; }';
                $html .= '        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(-360deg); } }';
                $html .= '        .animate-spin-slow { animation: spin 2s linear infinite; }';
                $html .= '    </style>';
                $html .= '</head>';
                $html .= '<body class="bg-gradient-to-br from-gray-100 via-gray-200 to-gray-300 min-h-screen flex items-center justify-center">';
                $html .= '    <div class="text-center px-4">';
                $html .= '        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-2xl p-8 max-w-md w-full border border-white/30">';
                $html .= '            <div class="flex justify-center mb-6">';
                $html .= '                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-lg">';
                $html .= '                    <svg class="w-10 h-10 text-orange-500 animate-spin-slow" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                $html .= '                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>';
                $html .= '                    </svg>';
                $html .= '                </div>';
                $html .= '            </div>';
                $html .= '            <h2 class="text-3xl font-bold text-gray-800 mb-4">處理中...</h2>';
                $html .= '            <p class="text-gray-600 text-lg">正在確認您的付款狀態，請稍候</p>';
                $html .= '        </div>';
                $html .= '    </div>';
                $html .= '    <script>';
                $html .= '        window.addEventListener(\'load\', function() {';
                $html .= '            setTimeout(function() {';
                $html .= '                window.location.href = "/ecpay/payment-result";';
                $html .= '            }, 2000);';
                $html .= '        });';
                $html .= '    </script>';
                $html .= '</body>';
                $html .= '</html>';

                return Response::make($html . '<!-- 1|OK -->')
                    ->header('Content-Type', 'text/html; charset=utf-8');
            }

            // 處理取號結果
            $result = $this->subscriptionService->handlePaymentInfo($request->all());

            // 記錄處理結果
            Log::info('ECPay PaymentInfo URL processed', [
                'success' => $result['success'],
                'message' => $result['message'] ?? '',
            ]);

            // 設定 session 傳遞結果到結果頁面
            $paymentResult = [
                'success' => $result['success'],
                'order_number' => $result['order_number'] ?? null,
                'message' => $result['message'] ?? ($result['success'] ? '取號成功完成' : '取號處理失敗'),
            ];

            session(['ecpay_payment_result' => $paymentResult]);

            // 建立處理中頁面
            $html = '<!DOCTYPE html>';
            $html .= '<html lang="zh-TW">';
            $html .= '<head>';
            $html .= '    <meta charset="UTF-8">';
            $html .= '    <meta name="viewport" content="width=device-width, initial-scale=1.0">';
            $html .= '    <title>付款處理中 - 592Meal</title>';
            $html .= '    <meta http-equiv="refresh" content="3; url=/ecpay/payment-result">';
            $html .= '    <script src="https://cdn.tailwindcss.com"></script>';
            $html .= '    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;700&display=swap" rel="stylesheet">';
            $html .= '    <style>';
            $html .= '        body { font-family: "Noto Sans TC", sans-serif; }';
            $html .= '        .brand-orange { color: #FB923C; }';
            $html .= '        .brand-orange-bg { background-color: #FB923C; }';
            $html .= '        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(-360deg); } }';
            $html .= '        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }';
            $html .= '        .animate-spin-slow { animation: spin 2s linear infinite; }';
            $html .= '        .animate-pulse-slow { animation: pulse 2s ease-in-out infinite; }';
            $html .= '    </style>';
            $html .= '</head>';
            $html .= '<body class="bg-gradient-to-br from-gray-100 via-gray-200 to-gray-300 min-h-screen flex items-center justify-center">';
            $html .= '    <div class="text-center px-4">';
            $html .= '        <!-- 592Meal 品牌 -->';
            $html .= '        <div class="mb-8 animate-pulse-slow">';
            $html .= '            <h1 class="text-5xl font-bold text-gray-800 mb-2">592Meal</h1>';
            $html .= '            <p class="text-gray-600">訂餐服務系統</p>';
            $html .= '        </div>';
            $html .= '';
            $html .= '        <!-- 處理中訊息 -->';
            $html .= '        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-2xl p-8 max-w-md w-full border border-white/30">';
            $html .= '            <div class="flex justify-center mb-6">';
            $html .= '                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-lg">';
            $html .= '                    <svg class="w-10 h-10 text-orange-500 animate-spin-slow" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $html .= '                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>';
            $html .= '                    </svg>';
            $html .= '                </div>';
            $html .= '            </div>';
            $html .= '';
            $html .= '            <h2 class="text-3xl font-bold text-gray-800 mb-4">處理中...</h2>';
            $html .= '            <p class="text-gray-600 text-lg mb-6">正在確認您的付款狀態，請稍候</p>';
            $html .= '';
            $html .= '            <div class="space-y-3">';
            $html .= '                <div class="bg-white/60 rounded-lg p-3">';
            $html .= '                    <p class="text-gray-800 text-sm flex items-center justify-center">';
            $html .= '                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">';
            $html .= '                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>';
            $html .= '                        </svg>';
            $html .= '                        正在驗證付款資訊';
            $html .= '                    </p>';
            $html .= '                </div>';
            $html .= '                <div class="bg-white/60 rounded-lg p-3">';
            $html .= '                    <p class="text-gray-800 text-sm flex items-center justify-center">';
            $html .= '                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">';
            $html .= '                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>';
            $html .= '                        </svg>';
            $html .= '                        更新訂單狀態';
            $html .= '                    </p>';
            $html .= '                </div>';
            $html .= '                <div class="bg-white/60 rounded-lg p-3">';
            $html .= '                    <p class="text-gray-800 text-sm flex items-center justify-center">';
            $html .= '                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">';
            $html .= '                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>';
            $html .= '                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H6a2 2 0 100 4h2a2 2 0 100-4h-.5a1 1 0 000-2H8a2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"></path>';
            $html .= '                        </svg>';
            $html .= '                        準備結果頁面';
            $html .= '                    </p>';
            $html .= '                </div>';
            $html .= '            </div>';
            $html .= '';
            $html .= '            <div class="mt-6">';
            $html .= '                <div class="bg-white/60 rounded-full px-4 py-2 inline-block">';
            $html .= '                    <p class="text-gray-800 text-xs">';
            $html .= '                        即將自動跳轉到付款結果頁面...';
            $html .= '                    </p>';
            $html .= '                </div>';
            $html .= '            </div>';
            $html .= '            <div class="mt-4">';
            $html .= '                <button onclick="window.location.href=\'/ecpay/payment-result\'" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg transition-colors">';
            $html .= '                    立即查看付款結果';
            $html .= '                </button>';
            $html .= '            </div>';
            $html .= '        </div>';
            $html .= '';
            $html .= '        <!-- 版權資訊 -->';
            $html .= '        <div class="mt-8 text-center">';
            $html .= '            <p class="text-gray-500 text-sm">© 2024 592Meal. 版權所有。</p>';
            $html .= '        </div>';
            $html .= '    </div>';
            $html .= '';
            $html .= '    <script>';
            $html .= '        // 強制跳轉函數';
            $html .= '        function forceRedirect() {';
            $html .= '            console.log("執行強制跳轉");';
            $html .= '            // 多種跳轉方式';
            $html .= '            try {';
            $html .= '                window.location.href = "/ecpay/payment-result";';
            $html .= '            } catch(e) {';
            $html .= '                console.error("跳轉方式1失敗:", e);';
            $html .= '            }';
            $html .= '            ';
            $html .= '            try {';
            $html .= '                window.location.replace("/ecpay/payment-result");';
            $html .= '            } catch(e) {';
            $html .= '                console.error("跳轉方式2失敗:", e);';
            $html .= '            }';
            $html .= '            ';
            $html .= '            try {';
            $html .= '                window.open("/ecpay/payment-result", "_self");';
            $html .= '            } catch(e) {';
            $html .= '                console.error("跳轉方式3失敗:", e);';
            $html .= '            }';
            $html .= '        }';
            $html .= '        ';
            $html .= '        // 確保頁面完全載入後開始計時';
            $html .= '        window.addEventListener(\'load\', function() {';
            $html .= '            console.log("頁面載入完成，2秒後跳轉到結果頁面");';
            $html .= '            setTimeout(forceRedirect, 2000);';
            $html .= '        });';
            $html .= '        ';
            $html .= '        // 備用跳轉機制';
            $html .= '        var fallbackTimer = setTimeout(function() {';
            $html .= '            console.log("執行備用跳轉機制");';
            $html .= '            forceRedirect();';
            $html .= '        }, 3000);';
            $html .= '        ';
            $html .= '        // 最終備用機制';
            $html .= '        var ultimateTimer = setTimeout(function() {';
            $html .= '            console.log("執行最終跳轉機制");';
            $html .= '            document.body.innerHTML += "<div style=\'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:9999;display:flex;align-items:center;justify-content:center;\'><a href=\'/ecpay/payment-result\' style=\'background:#007bff;color:white;padding:20px;border-radius:5px;text-decoration:none;font-size:18px;\'>點擊查看付款結果</a></div>";';
            $html .= '        }, 5000);';
            $html .= '        ';
            $html .= '        // 如果正常跳轉發生，取消計時器';
            $html .= '        window.addEventListener(\'beforeunload\', function() {';
            $html .= '            clearTimeout(fallbackTimer);';
            $html .= '            clearTimeout(ultimateTimer);';
            $html .= '        });';
            $html .= '    </script>';
            $html .= '</body>';
            $html .= '</html>';

            // 必須回傳 "1|OK" 給綠界，但我們可以藏起來
            return Response::make($html . '<!-- 1|OK -->')
                ->header('Content-Type', 'text/html; charset=utf-8');
        } catch (\Exception $e) {
            Log::error('ECPay PaymentInfo URL error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            // 即使發生錯誤也顯示處理中頁面
            $html = '<!DOCTYPE html>';
            $html .= '<html lang="zh-TW">';
            $html .= '<head>';
            $html .= '    <meta charset="UTF-8">';
            $html .= '    <meta name="viewport" content="width=device-width, initial-scale=1.0">';
            $html .= '    <title>付款處理中 - 592Meal</title>';
            $html .= '    <meta http-equiv="refresh" content="3; url=/ecpay/payment-result">';
            $html .= '    <script src="https://cdn.tailwindcss.com"></script>';
            $html .= '    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;700&display=swap" rel="stylesheet">';
            $html .= '    <style>';
            $html .= '        body { font-family: "Noto Sans TC", sans-serif; }';
            $html .= '        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(-360deg); } }';
            $html .= '        .animate-spin-slow { animation: spin 2s linear infinite; }';
            $html .= '    </style>';
            $html .= '</head>';
            $html .= '<body class="bg-gradient-to-br from-gray-100 via-gray-200 to-gray-300 min-h-screen flex items-center justify-center">';
            $html .= '    <div class="text-center px-4">';
            $html .= '        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-2xl p-8 max-w-md w-full border border-white/30">';
            $html .= '            <div class="flex justify-center mb-6">';
            $html .= '                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-lg">';
            $html .= '                    <svg class="w-10 h-10 text-orange-500 animate-spin-slow" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $html .= '                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>';
            $html .= '                    </svg>';
            $html .= '                </div>';
            $html .= '            </div>';
            $html .= '            <h2 class="text-3xl font-bold text-gray-800 mb-4">處理中...</h2>';
            $html .= '            <p class="text-gray-600 text-lg">正在確認您的付款狀態，請稍候</p>';
            $html .= '        </div>';
            $html .= '    </div>';
            $html .= '    <script>';
            $html .= '        window.addEventListener(\'load\', function() {';
            $html .= '            setTimeout(function() {';
            $html .= '                window.location.href = "/ecpay/payment-result";';
            $html .= '            }, 2000);';
            $html .= '        });';
            $html .= '    </script>';
            $html .= '</body>';
            $html .= '</html>';

            return Response::make($html . '<!-- 1|OK -->')
                ->header('Content-Type', 'text/html; charset=utf-8');
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