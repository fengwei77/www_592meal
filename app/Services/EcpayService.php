<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EcpayService
{
    private string $merchantId;
    private string $hashKey;
    private string $hashIv;
    private bool $testMode;
    private string $returnUrl;
    private string $paymentInfoUrl;
    private string $clientReturnUrl;

    public function __construct()
    {
        $this->merchantId = config('ecpay.merchant_id');
        $this->hashKey = config('ecpay.hash_key');
        $this->hashIv = config('ecpay.hash_iv');
        $this->testMode = config('ecpay.test_mode', true);
        $this->returnUrl = config('ecpay.return_url');
        $this->paymentInfoUrl = config('ecpay.payment_info_url');
        $this->clientReturnUrl = config('ecpay.client_return_url');
    }

    /**
     * 生成綠界付款參數
     */
    public function generateOrderParams(array $orderData): array
    {
        $params = [
            'MerchantID' => $this->merchantId,
            'MerchantTradeNo' => $orderData['order_number'],
            'MerchantTradeDate' => now()->format('Y/m/d H:i:s'),
            'PaymentType' => 'aio',
            'TotalAmount' => (int)$orderData['total_amount'],
            'TradeDesc' => $this->sanitizeTradeDesc($orderData['trade_desc'] ?? '592Meal訂閱服務'),
            'ItemName' => $orderData['item_name'],
            'ReturnURL' => $this->returnUrl,
            'ChoosePayment' => $orderData['choose_payment'] ?? 'ALL',
            'EncryptType' => 1,
            'ClientBackURL' => $this->clientReturnUrl,
        ];

        // 加入選用參數
        if (isset($orderData['store_id'])) {
            $params['StoreID'] = $orderData['store_id'];
        }

        if (isset($orderData['payment_info_url'])) {
            $params['PaymentInfoURL'] = $orderData['payment_info_url'];
        }

        if (isset($orderData['client_return_url'])) {
            $params['OrderResultURL'] = $orderData['client_return_url'];
        }

        // 加入自訂欄位
        for ($i = 1; $i <= 4; $i++) {
            $fieldKey = "custom_field_{$i}";
            if (isset($orderData[$fieldKey])) {
                $params["CustomField{$i}"] = $orderData[$fieldKey];
            }
        }

        // 生成檢查碼
        $params['CheckMacValue'] = $this->generateCheckMacValue($params);

        return $params;
    }

    /**
     * 替換特殊字符 (官方 SDK 方法)
     */
    private static function replaceSymbol(string $input): string
    {
        return str_replace(
            ['%2d', '%5f', '%2e', '%21', '%2a', '%28', '%29'],
            ['-', '_', '.', '!', '*', '(', ')'],
            strtolower($input)
        );
    }

    /**
     * 生成 SHA256 檢查碼 (官方 SDK 版本)
     */
    public function generateCheckMacValue(array $data): string
    {
        // 移除 CheckMacValue 參數
        unset($data['CheckMacValue']);

        // 按照字母順序排序
        uksort($data, 'strcmp');

        // 組合加密字串 (按照官方 SDK 方式)
        $checkCodeStr = "HashKey={$this->hashKey}";

        foreach ($data as $key => $val) {
            $checkCodeStr .= '&' . $key . '=' . $val;
        }

        $checkCodeStr .= '&HashIV=' . $this->hashIv;

        // 處理特殊字符並使用 SHA256 加密
        $checkCodeStr = self::replaceSymbol(urlencode($checkCodeStr));

        return strtoupper(hash('sha256', strtolower($checkCodeStr)));
    }

    /**
     * 驗證綠界回傳的檢查碼
     */
    public function verifyCheckMacValue(array $data): bool
    {
        if (!isset($data['CheckMacValue'])) {
            return false;
        }

        $receivedCheckMacValue = $data['CheckMacValue'];
        $calculatedCheckMacValue = $this->generateCheckMacValue($data);

        return hash_equals($receivedCheckMacValue, $calculatedCheckMacValue);
    }

    /**
     * 取得綠界付款網址
     */
    public function getPaymentUrl(): string
    {
        return $this->testMode
            ? 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5'
            : 'https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5';
    }

    /**
     * 產生 HTML 表單
     */
    public function generateSubmitForm(array $params): string
    {
        // 生成 CheckMacValue
        $params['CheckMacValue'] = $this->generateCheckMacValue($params);

        $url = $this->getPaymentUrl();

        $formFields = '';
        foreach ($params as $key => $value) {
            $formFields .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value, ENT_QUOTES) . '">' . "\n";
        }

        $form = '<form method="POST" action="' . $url . '" id="ecpayPaymentForm">' . "\n";
        $form .= $formFields;
        $form .= '</form>';

        return $form;
    }

    
    /**
     * 處理交易描述，移除不允許的字元
     */
    private function sanitizeTradeDesc(string $desc): string
    {
        // 移除特殊字元
        $desc = preg_replace('/[<>"\']/', '', $desc);
        $desc = preg_replace('/[&]/', ' and ', $desc);

        // 限制長度
        return Str::limit($desc, 200, '');
    }

    /**
     * 取得付款方式對應表
     */
    public function getPaymentTypeMapping(): array
    {
        return [
            'Credit_CreditCard' => '信用卡',
            'Credit_UnionPay' => '銀聯卡',
            'Credit_AmericanExpress' => '美國運通卡',
            'CVS' => '超商代碼',
            'BARCODE' => '超商條碼',
            'ATM' => 'ATM轉帳',
            'WebATM' => '網路ATM',
            'ApplePay' => 'Apple Pay',
            'TWQR' => '歐付寶TWQR',
        ];
    }

    /**
     * 取得交易狀態對應表
     */
    public function getRtnCodeMapping(): array
    {
        return [
            1 => '交易成功',
            2 => '取號成功 (ATM)',
            10100073 => '取號成功 (CVS/BARCODE)',
            10300066 => '交易付款結果待確認中',
            10100248 => '拒絕交易',
            10100252 => '額度不足',
            10100254 => '交易失敗',
            10100251 => '卡片過期',
            10100255 => '報失卡',
            10100256 => '被盜用卡',
        ];
    }

    /**
     * 判斷是否為付款成功
     */
    public function isPaymentSuccess(array $data): bool
    {
        return (int)($data['RtnCode'] ?? 0) === 1 && (int)($data['SimulatePaid'] ?? 0) === 0;
    }

    /**
     * 判斷是否為取號成功
     */
    public function isNumberGeneratedSuccess(array $data): bool
    {
        $rtnCode = (int)($data['RtnCode'] ?? 0);
        $rtnMsg = strtolower($data['RtnMsg'] ?? '');

        return match ($rtnCode) {
            2 => true, // ATM 取號成功
            10100073 => true, // CVS/BARCODE 取號成功
            1 => true, // 付款成功 (處理一般付款)
            default => false,
        } || in_array($rtnMsg, ['succeeded', 'success', '成功']);
    }

    /**
     * 記錄綠界回傳資料
     */
    public function logEcpayResponse(array $data, string $type = 'return_url'): void
    {
        Log::channel('ecpay')->info("ECPay {$type} Response", [
            'MerchantTradeNo' => $data['MerchantTradeNo'] ?? null,
            'RtnCode' => $data['RtnCode'] ?? null,
            'RtnMsg' => $data['RtnMsg'] ?? null,
            'TradeAmt' => $data['TradeAmt'] ?? null,
            'PaymentType' => $data['PaymentType'] ?? null,
            'TradeNo' => $data['TradeNo'] ?? null,
            'SimulatePaid' => $data['SimulatePaid'] ?? null,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * 檢查訂單金額是否正確
     */
    public function verifyOrderAmount(int $expectedAmount, int $receivedAmount): bool
    {
        return $expectedAmount === $receivedAmount;
    }

    /**
     * 格式化商品名稱
     */
    public function formatItemName(array $items): string
    {
        $itemNames = [];

        foreach ($items as $item) {
            $name = $item['name'] ?? '';
            $quantity = $item['quantity'] ?? 1;

            if ($quantity > 1) {
                $name .= " x{$quantity}";
            }

            $itemNames[] = $name;
        }

        return implode('#', $itemNames);
    }

    /**
     * 處理特殊字元 URL 解碼
     */
    public function handleSpecialChars(array &$data): void
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // 處理 %26(&) 和 %3C(<)
                $data[$key] = urldecode($value);
            }
        }
    }

    /**
     * 取得測試資訊
     */
    public function getTestInfo(): array
    {
        return [
            'merchant_id' => $this->testMode ? '3002607' : $this->merchantId,
            'backend_url' => $this->testMode ? 'https://vendor-stage.ecpay.com.tw' : 'https://vendor.ecpay.com.tw',
            'test_cards' => [
                'visa' => '4311-9511-1111-1111',
                'mastercard' => '4311-9522-2222-2222',
                'overseas' => '4000-2011-1111-1111',
                'amex_domestic' => '3403-532780-80900',
                'amex_overseas' => '3712-222222-22222',
            ],
            '3d_otp' => '1234',
        ];
    }
}