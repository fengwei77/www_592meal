<?php

namespace App\Http\Controllers;

use App\Services\EcpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EcpayTestController extends Controller
{
    private EcpayService $ecpayService;

    public function __construct(EcpayService $ecpayService)
    {
        $this->ecpayService = $ecpayService;
    }

    /**
     * 顯示測試頁面
     */
    public function index()
    {
        return view('ecpay.test-index');
    }

    /**
     * 產生測試付款表單
     */
    public function generatePayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:1|max:20000',
            'item_name' => 'required|string|max:200',
            'description' => 'nullable|string|max:200',
        ], [
            'amount.required' => '請輸入金額',
            'amount.integer' => '金額必須是整數',
            'amount.min' => '金額至少為 1 元',
            'amount.max' => '金額最多為 20,000 元',
            'item_name.required' => '請輸入商品名稱',
            'item_name.max' => '商品名稱最多 200 個字元',
            'description.max' => '描述最多 200 個字元',
        ]);

        try {
            // 產生唯一的交易編號 (最多20字元)
            // 格式: TEST + 年月日時分秒(12) + 3位隨機數 = 4 + 12 + 3 = 19字元
            $merchantTradeNo = 'TEST' . date('ymdHis') . rand(100, 999);

            // 綠界參數設定
            $params = [
                'MerchantID'        => config('ecpay.merchant_id'),
                'MerchantTradeNo'   => $merchantTradeNo,
                'MerchantTradeDate' => date('Y/m/d H:i:s'),
                'PaymentType'       => 'aio',
                'TotalAmount'       => (int) $request->amount,
                'TradeDesc'         => $request->description ?: '測試交易',
                'ItemName'          => $request->item_name,
                'ChoosePayment'     => 'ALL',
                'EncryptType'       => 1,
                'ReturnURL'         => route('ecpay.test.return'),
                'ClientBackURL'     => route('ecpay.test.client-return'),
                'OrderResultURL'    => route('ecpay.test.payment-info'),
                'CustomField1'      => 'test_payment',
                'CustomField2'      => (string) (auth()->check() ? auth()->id() : 'guest'),
                'CustomField3'      => 'ECPay測試',
            ];

            // 記錄測試交易日誌
            Log::info('ECPay Test Payment Created', [
                'merchant_trade_no' => $merchantTradeNo,
                'amount' => $request->amount,
                'item_name' => $request->item_name,
                'user_id' => auth()->check() ? auth()->id() : null,
                'params' => $params,
            ]);

            // 記錄 CheckMacValue 計算過程
            Log::info('ECPay CheckMacValue Debug', [
                'original_params' => $params,
                'checkmacvalue' => $this->ecpayService->generateCheckMacValue($params),
            ]);

            // 產生付款表單HTML
            $paymentForm = $this->ecpayService->generateSubmitForm($params);

            return view('ecpay.confirm', compact('paymentForm', 'merchantTradeNo', 'params'));

        } catch (\Exception $e) {
            Log::error('ECPay Test Payment Error', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return back()
                ->with('error', '產生付款表單時發生錯誤：' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * 綠界回傳處理 (ReturnURL)
     */
    public function returnUrl(Request $request)
    {
        try {
            // 記錄原始資料
            Log::info('ECPay Test Return URL Received', $request->all());

            // 驗證檢查碼
            if (!$this->ecpayService->verifyCheckMacValue($request->all())) {
                Log::error('ECPay Test CheckMacValue verification failed', $request->all());
                return '0|CheckMacValue verification failed';
            }

            // 處理特殊字元
            $this->ecpayService->handleSpecialChars($request->all());

            $merchantTradeNo = $request->input('MerchantTradeNo');
            $tradeAmt = $request->input('TradeAmt');
            $rtnCode = $request->input('RtnCode');
            $rtnMsg = $request->input('RtnMsg');

            Log::info('ECPay Test Payment Result', [
                'merchant_trade_no' => $merchantTradeNo,
                'trade_amt' => $tradeAmt,
                'rtn_code' => $rtnCode,
                'rtn_msg' => $rtnMsg,
            ]);

            // 檢查是否為模擬付款
            if (($request->input('SimulatePaid') ?? 0) == 1) {
                Log::info('ECPay Test Simulate payment received');
                return '1|Simulate payment received';
            }

            // 回傳成功訊息
            if ($rtnCode == 1) {
                return '1|OK';
            } else {
                return "0|{$rtnMsg}";
            }

        } catch (\Exception $e) {
            Log::error('ECPay Test Return URL Error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return '0|System error';
        }
    }

    /**
     * 取號結果回傳 (OrderResultURL)
     */
    public function paymentInfo(Request $request)
    {
        try {
            Log::info('ECPay Test PaymentInfo Received', $request->all());

            // 驗證檢查碼
            if (!$this->ecpayService->verifyCheckMacValue($request->all())) {
                Log::error('ECPay Test PaymentInfo CheckMacValue verification failed', $request->all());
                return response()->json(['success' => false, 'message' => 'CheckMacValue verification failed']);
            }

            $merchantTradeNo = $request->input('MerchantTradeNo');
            $paymentType = $request->input('PaymentType');
            $rtnCode = $request->input('RtnCode');
            $rtnMsg = $request->input('RtnMsg');

            Log::info('ECPay Test PaymentInfo Result', [
                'merchant_trade_no' => $merchantTradeNo,
                'payment_type' => $paymentType,
                'rtn_code' => $rtnCode,
                'rtn_msg' => $rtnMsg,
            ]);

            return response()->json([
                'success' => $rtnCode == 1 || in_array($rtnCode, ['10100073', '2']),
                'message' => $rtnMsg,
                'data' => $request->all(),
            ]);

        } catch (\Exception $e) {
            Log::error('ECPay Test PaymentInfo Error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 取號結果回傳測試 (GET 版本，用於直接測試)
     */
    public function paymentInfoTest(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Payment-info GET 測試頁面正常運作',
            'note' => '這是 GET 版本的測試頁面，綠界實際回傳使用 POST 版本',
            'request_method' => $request->method(),
            'path' => $request->path(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * 用戶返回頁面
     */
    public function clientReturn()
    {
        return view('ecpay.client-return');
    }
}