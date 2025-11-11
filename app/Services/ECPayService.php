<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ECPayService
{
    /**
     * 產生綠界金流的 CheckMacValue
     */
    public function generateCheckMacValue(array $parameters, string $hashKey, string $hashIV): string
    {
        // 移除 CheckMacValue 參數
        unset($parameters['CheckMacValue']);

        // 按參數名稱 ASCII 排序
        ksort($parameters);

        // 產生查詢字串
        $queryString = http_build_query($parameters);

        // URL encode
        $queryString = urlencode($queryString);

        // 替換特殊字符
        $queryString = str_replace('%2D', '-', $queryString);
        $queryString = str_replace('%5F', '_', $queryString);
        $queryString = str_replace('%2E', '.', $queryString);
        $queryString = str_replace('%21', '!', $queryString);
        $queryString = str_replace('%2A', '*', $queryString);
        $queryString = str_replace('%28', '(', $queryString);
        $queryString = str_replace('%29', ')', $queryString);

        // 組合 Hash 資料
        $toHash = "HashKey={$hashKey}&{$queryString}&HashIV={$hashIV}";

        // 進行 URL encode
        $toHash = urlencode($toHash);

        // 替換特殊字符
        $toHash = str_replace('%2D', '-', $toHash);
        $toHash = str_replace('%5F', '_', $toHash);
        $toHash = str_replace('%2E', '.', $toHash);
        $toHash = str_replace('%21', '!', $toHash);
        $toHash = str_replace('%2A', '*', $toHash);
        $toHash = str_replace('%28', '(', $toHash);
        $toHash = str_replace('%29', ')', $toHash);

        // 產生 SHA256 雜湊
        return strtoupper(hash('sha256', $toHash));
    }

    /**
     * 驗證綠界金流的 CheckMacValue
     */
    public function verifyCheckMacValue(array $parameters, string $hashKey, string $hashIV): bool
    {
        if (!isset($parameters['CheckMacValue'])) {
            return false;
        }

        $receivedMacValue = $parameters['CheckMacValue'];
        $calculatedMacValue = $this->generateCheckMacValue($parameters, $hashKey, $hashIV);

        return hash_equals($calculatedMacValue, $receivedMacValue);
    }

    /**
     * 解析綠界回饋的付款日期
     */
    public function parsePaymentDate(string $paymentDate): string
    {
        // 綠界回饋的格式通常是: yyyy/MM/dd HH:mm:ss
        // 轉換為資料庫格式: Y-m-d H:i:s
        try {
            $dateTime = \DateTime::createFromFormat('Y/m/d H:i:s', $paymentDate);
            return $dateTime ? $dateTime->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            Log::error('Failed to parse payment date', ['paymentDate' => $paymentDate]);
            return now()->format('Y-m-d H:i:s');
        }
    }

    /**
     * 判斷交易是否成功
     */
    public function isPaymentSuccessful(string $rtnCode): bool
    {
        // 綠界金流成功代碼為 1
        return $rtnCode === '1';
    }

    /**
     * 取得交易狀態說明
     */
    public function getPaymentStatusDescription(string $rtnCode, string $rtnMsg = ''): string
    {
        $statusMap = [
            '1' => '交易成功',
            '2' => '交易失敗',
            '3' => '交易取消',
            '4' => '交易逾期',
            '5' => '帳號錯誤',
            '6' => '金額不符',
            '7' => '系統錯誤',
            '8' => '訂單編號重複',
            '9' => '訂單編號不存在',
            '10' => '訂單已付款',
            '11' => '訂單已逾期',
            '12' => '訂單已取消',
            '13' => '訂單已退款',
            '14' => '訂單已部分退款',
            '15' => '訂單已全部退款',
            '800' => '交易處理中',
            '801' => '交易通知中',
            '900' => '訂單建立失敗',
            '1000' => '輸入資料錯誤',
            '1001' => '輸入資料格式錯誤',
            '1002' => '輸入資料長度錯誤',
            '1003' => '輸入資料為空值',
        ];

        return $statusMap[$rtnCode] ?? $rtnMsg ?: "未知狀態代碼: {$rtnCode}";
    }

    /**
     * 取得綠界金流設定
     */
    public function getECPayConfig(): array
    {
        return [
            'merchant_id' => config('services.ecpay.merchant_id'),
            'hash_key' => config('services.ecpay.hash_key'),
            'hash_iv' => config('services.ecpay.hash_iv'),
            'test_mode' => config('services.ecpay.test_mode', true),
        ];
    }

    /**
     * 格式化金流商回饋資料
     */
    public function formatCallbackData(array $callbackData): array
    {
        return [
            'merchant_id' => $callbackData['MerchantID'] ?? null,
            'merchant_trade_no' => $callbackData['MerchantTradeNo'] ?? null,
            'store_id' => $callbackData['StoreID'] ?? null,
            'trade_no' => $callbackData['TradeNo'] ?? null,
            'trade_amount' => $callbackData['TradeAmt'] ?? 0,
            'payment_date' => $callbackData['PaymentDate'] ?? null,
            'payment_type' => $callbackData['PaymentType'] ?? null,
            'payment_type_charge_fee' => $callbackData['PaymentTypeChargeFee'] ?? 0,
            'trade_date' => $callbackData['TradeDate'] ?? null,
            'return_code' => $callbackData['RtnCode'] ?? null,
            'return_message' => $callbackData['RtnMsg'] ?? null,
            'simulate_paid' => $callbackData['SimulatePaid'] ?? null,
            'check_mac_value' => $callbackData['CheckMacValue'] ?? null,
            'payment_no' => $callbackData['PaymentNo'] ?? null,
            'payment_no_expire_date' => $callbackData['PaymentNoExpireDate'] ?? null,
            'bank_code' => $callbackData['BankCode'] ?? null,
            'virtual_account' => $callbackData['vAccount'] ?? null,
            'code_no' => $callbackData['CodeNo'] ?? null,
            'barcode1' => $callbackData['Barcode1'] ?? null,
            'barcode2' => $callbackData['Barcode2'] ?? null,
            'barcode3' => $callbackData['Barcode3'] ?? null,
            'expire_date' => $callbackData['ExpireDate'] ?? null,
            'comment1' => $callbackData['Comment1'] ?? null,
            'comment2' => $callbackData['Comment2'] ?? null,
            'collected_amount' => $callbackData['CollectedAmount'] ?? null,
            'clearance_mark' => $callbackData['ClearanceMark'] ?? null,
            'recognize_account_no' => $callbackData['RecognizeAccountNo'] ?? null,
            'auth_code' => $callbackData['AuthCode'] ?? null,
            'card_6no' => $callbackData['Card6No'] ?? null,
            'card4no' => $callbackData['Card4No'] ?? null,
            'exec_trade_time' => $callbackData['ExecTradeTime'] ?? null,
            'fund_code' => $callbackData['FundCode'] ?? null,
            'card_type' => $callbackData['CardType'] ?? null,
            'process_date' => $callbackData['ProcessDate'] ?? null,
            'amt_settlement' => $callbackData['AmtSettlement'] ?? null,
            'settle_date' => $callbackData['SettleDate'] ?? null,
            'eci' => $callbackData['ECI'] ?? null,
            'pay_token_use' => $callbackData['PayTokenUse'] ?? null,
            'custom_field1' => $callbackData['CustomField1'] ?? null,
            'custom_field2' => $callbackData['CustomField2'] ?? null,
            'custom_field3' => $callbackData['CustomField3'] ?? null,
            'custom_field4' => $callbackData['CustomField4'] ?? null,
            'invoice_no' => $callbackData['InvoiceNo'] ?? null,
            'issue_time' => $callbackData['IssueTime'] ?? null,
            'delay_day' => $callbackData['DelayDay'] ?? null,
            'delay_time' => $callbackData['DelayTime'] ?? null,
        ];
    }

    /**
     * 記錄金流交易日誌
     */
    public function logPaymentCallback(string $level, string $message, array $data = []): void
    {
        Log::channel('ecpay')->{$level}($message, $data);
    }

    /**
     * 產生訂單編號
     */
    public function generateOrderNumber(string $prefix = ''): string
    {
        return $prefix . date('YmdHis') . Str::random(6);
    }

    /**
     * 驗證回饋資料完整性
     */
    public function validateCallbackData(array $data): array
    {
        $errors = [];

        // 檢查必要欄位
        $requiredFields = ['MerchantID', 'MerchantTradeNo', 'RtnCode', 'RtnMsg', 'TradeAmt', 'CheckMacValue'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = "缺少必要欄位: {$field}";
            }
        }

        // 檢查金額格式
        if (isset($data['TradeAmt']) && !is_numeric($data['TradeAmt'])) {
            $errors[] = "交易金額格式錯誤";
        }

        // 檢查回饋代碼
        if (isset($data['RtnCode']) && !$this->isValidReturnCode($data['RtnCode'])) {
            $errors[] = "無效的回饋代碼: {$data['RtnCode']}";
        }

        return $errors;
    }

    /**
     * 檢查是否為有效的回饋代碼
     */
    private function isValidReturnCode(string $code): bool
    {
        $validCodes = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '800', '801', '900', '1000', '1001', '1002', '1003'];
        return in_array($code, $validCodes);
    }
}