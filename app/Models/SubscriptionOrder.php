<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class SubscriptionOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'months',
        'unit_price',
        'total_amount',
        'status',
        'ecpay_trade_no',
        'payment_type',
        'payment_date',
        'expire_date',
        'paid_at',
        'subscription_start_date',
        'subscription_end_date',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:0',
        'total_amount' => 'decimal:0',
        'expire_date' => 'datetime',
        'paid_at' => 'datetime',
        'subscription_start_date' => 'datetime',
        'subscription_end_date' => 'datetime',
        'payment_date' => 'datetime',
    ];

    /**
     * 關聯用戶
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 關聯付款日誌
     */
    public function paymentLogs()
    {
        return $this->hasMany(SubscriptionPaymentLog::class, 'order_id');
    }

    /**
     * 生成訂單編號
     */
    public static function generateOrderNumber(): string
    {
        do {
            $date = now()->format('Ymd');
            $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $orderNumber = 'SUB' . $date . $random;
        } while (self::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * 檢查訂單是否已過期
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' ||
               ($this->status === 'pending' && $this->expire_date->isPast());
    }

    /**
     * 檢查訂單是否可以重新繳費
     */
    public function canRepay(): bool
    {
        return $this->isExpired() && $this->status !== 'cancelled';
    }

    /**
     * 檢查訂單是否已付款
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * 檢查訂單是否為待繳費狀態
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * 取得狀態標籤
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => '待繳費',
            'paid' => '已付款',
            'expired' => '已過期',
            'cancelled' => '已取消',
            default => '未知',
        };
    }

    /**
     * 取得狀態顏色 (Bootstrap)
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'paid' => 'success',
            'expired' => 'danger',
            'cancelled' => 'secondary',
            default => 'light',
        };
    }

    /**
     * 取得付款方式標籤
     */
    public function getPaymentTypeLabel(): string
    {
        if (!$this->payment_type) {
            return '未設定';
        }

        $labels = [
            'Credit_CreditCard' => '信用卡',
            'Credit_UnionPay' => '銀聯卡',
            'Credit_AmericanExpress' => '美國運通卡',
            'CVS' => '超商代碼',
            'BARCODE' => '超商條碼',
            'ATM' => 'ATM轉帳',
            'WebATM' => '網路ATM',
        ];

        return $labels[$this->payment_type] ?? $this->payment_type;
    }

    /**
     * 標記訂單為已付款
     */
    public function markAsPaid(array $paymentData = []): bool
    {
        $this->status = 'paid';
        $this->paid_at = now();
        $this->payment_date = now();

        if (isset($paymentData['TradeNo'])) {
            $this->ecpay_trade_no = $paymentData['TradeNo'];
        }

        if (isset($paymentData['PaymentType'])) {
            $this->payment_type = $paymentData['PaymentType'];
        }

        return $this->save();
    }

    /**
     * 標記訂單為過期
     */
    public function markAsExpired(): bool
    {
        if ($this->status === 'pending') {
            $this->status = 'expired';
            return $this->save();
        }
        return false;
    }

    /**
     * 取得付款期限剩餘時間
     */
    public function getExpireTimeRemaining(): string
    {
        if ($this->status !== 'pending') {
            return '-';
        }

        $remaining = $this->expire_date->diffForHumans(now(), true);

        if ($this->expire_date->isPast()) {
            return '已過期';
        }

        return $remaining;
    }

    /**
     * 建立訂單紀錄
     */
    public static function createOrder(User $user, int $months, ?string $notes = null): self
    {
        $unitPrice = (int) config('ecpay.monthly_price', 50);
        $totalAmount = $months * $unitPrice;
        $expireDate = now()->addHours((int) config('ecpay.order_expire_hours', 72));

        return self::create([
            'order_number' => self::generateOrderNumber(),
            'user_id' => $user->id,
            'months' => $months,
            'unit_price' => $unitPrice,
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'expire_date' => $expireDate,
            'notes' => $notes,
        ]);
    }
}