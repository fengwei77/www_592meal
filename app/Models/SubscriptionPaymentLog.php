<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPaymentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_number',
        'merchant_id',
        'ecpay_trade_no',
        'rtn_code',
        'rtn_msg',
        'trade_amt',
        'payment_date',
        'payment_type',
        'payment_type_charge_fee',
        'bank_code',
        'virtual_account',
        'payment_no',
        'barcode1',
        'barcode2',
        'barcode3',
        'expire_date',
        'processed',
        'processed_at',
        'check_mac_value',
        'raw_data',
        'simulate_paid',
    ];

    protected $casts = [
        'trade_amt' => 'decimal:0',
        'payment_type_charge_fee' => 'decimal:2',
        'payment_date' => 'datetime',
        'expire_date' => 'datetime',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
        'raw_data' => 'array',
        'simulate_paid' => 'boolean',
    ];

    /**
     * 關聯訂單
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(SubscriptionOrder::class, 'order_id');
    }

    /**
     * 檢查是否為付款成功
     */
    public function isPaymentSuccess(): bool
    {
        return $this->rtn_code === 1 && !$this->simulate_paid;
    }

    /**
     * 檢查是否為取號成功
     */
    public function isNumberGeneratedSuccess(): bool
    {
        return match ($this->rtn_code) {
            2 => true, // ATM
            10100073 => true, // CVS/BARCODE
            default => false,
        };
    }

    /**
     * 取得交易狀態標籤
     */
    public function getRtnCodeLabel(): string
    {
        $labels = [
            1 => '付款成功',
            2 => '取號成功 (ATM)',
            10100073 => '取號成功 (CVS/BARCODE)',
            10300066 => '付款結果待確認',
            10100248 => '拒絕交易',
            10100252 => '額度不足',
            10100254 => '交易失敗',
            10100251 => '卡片過期',
            10100255 => '報失卡',
            10100256 => '被盜用卡',
        ];

        return $labels[$this->rtn_code] ?? "狀態碼: {$this->rtn_code}";
    }

    /**
     * 取得交易狀態顏色
     */
    public function getRtnCodeColor(): string
    {
        if ($this->simulate_paid) {
            return 'info'; // 模擬付款
        }

        return match ($this->rtn_code) {
            1, 2, 10100073 => 'success', // 成功
            10300066 => 'warning', // 待確認
            default => 'danger', // 失敗
        };
    }

    /**
     * 標記為已處理
     */
    public function markAsProcessed(): bool
    {
        $this->processed = true;
        $this->processed_at = now();
        return $this->save();
    }

    /**
     * 建立付款日誌
     */
    public static function createPaymentLog(SubscriptionOrder $order, array $data): self
    {
        return self::create([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'merchant_id' => $data['MerchantID'] ?? null,
            'ecpay_trade_no' => $data['TradeNo'] ?? null,
            'rtn_code' => (int)($data['RtnCode'] ?? 0),
            'rtn_msg' => $data['RtnMsg'] ?? null,
            'trade_amt' => (int)($data['TradeAmt'] ?? 0),
            'payment_date' => !empty($data['PaymentDate']) ? Carbon::createFromFormat('Y/m/d H:i:s', $data['PaymentDate']) : null,
            'payment_type' => $data['PaymentType'] ?? null,
            'payment_type_charge_fee' => !empty($data['PaymentTypeChargeFee']) ? (float)$data['PaymentTypeChargeFee'] : null,
            'bank_code' => $data['BankCode'] ?? null,
            'virtual_account' => $data['vAccount'] ?? null,
            'payment_no' => $data['PaymentNo'] ?? null,
            'barcode1' => $data['Barcode1'] ?? null,
            'barcode2' => $data['Barcode2'] ?? null,
            'barcode3' => $data['Barcode3'] ?? null,
            'expire_date' => !empty($data['ExpireDate']) ? static::parseExpireDate($data['ExpireDate']) : null,
            'check_mac_value' => $data['CheckMacValue'] ?? null,
            'raw_data' => $data,
            'simulate_paid' => ($data['SimulatePaid'] ?? 0) == 1,
        ]);
    }

    /**
     * 解析繳費期限
     */
    private static function parseExpireDate(string $expireDate): Carbon
    {
        // 嘗試不同的日期格式
        $formats = [
            'Y/m/d H:i:s', // 完整格式
            'Y/m/d',       // 只有日期
            'Y-m-d H:i:s', // 標準格式
            'Y-m-d',       // 標準日期格式
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $expireDate);
            } catch (\Exception $e) {
                // 繼續嘗試下一個格式
                continue;
            }
        }

        // 如果都失敗，使用當前時間
        return now();
    }

    /**
     * 取得繳費資訊 (適用於ATM/CVS/BARCODE)
     */
    public function getPaymentInfo(): array
    {
        $info = [];

        if ($this->payment_type === 'ATM') {
            $info = [
                'type' => 'ATM',
                'bank_code' => $this->bank_code,
                'virtual_account' => $this->virtual_account,
                'expire_date' => $this->expire_date?->format('Y/m/d'),
            ];
        } elseif ($this->payment_type === 'CVS') {
            $info = [
                'type' => 'CVS',
                'payment_no' => $this->payment_no,
                'expire_date' => $this->expire_date?->format('Y/m/d H:i'),
            ];
        } elseif ($this->payment_type === 'BARCODE') {
            $info = [
                'type' => 'BARCODE',
                'barcode1' => $this->barcode1,
                'barcode2' => $this->barcode2,
                'barcode3' => $this->barcode3,
                'expire_date' => $this->expire_date?->format('Y/m/d H:i'),
            ];
        }

        return $info;
    }

    /**
     * 取得原始回傳資料的文字格式
     */
    public function getRawDataString(): string
    {
        if (!$this->raw_data) {
            return '';
        }

        $pairs = [];
        foreach ($this->raw_data as $key => $value) {
            if ($value !== null && $value !== '') {
                $pairs[] = $key . '=' . $value;
            }
        }

        return implode('&', $pairs);
    }
}