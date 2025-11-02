<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreCustomerBlock extends Model
{
    protected $fillable = [
        'store_id',
        'line_user_id',
        'customer_id',
        'reason',
        'cancellation_count',
        'blocked_at',
        'blocked_by',
        'notes',
    ];

    protected $casts = [
        'blocked_at' => 'datetime',
    ];

    /**
     * 關聯到店家
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * 關聯到客戶
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 檢查客戶是否被特定店家鎖定
     */
    public static function isBlockedByStore(string $lineUserId, int $storeId): bool
    {
        return static::where('line_user_id', $lineUserId)
            ->where('store_id', $storeId)
            ->exists();
    }

    /**
     * 獲取客戶被鎖定的店家數量
     */
    public static function getBlockedStoreCount(string $lineUserId): int
    {
        return static::where('line_user_id', $lineUserId)->count();
    }

    /**
     * 檢查客戶是否被平台鎖定（被3個或以上店家鎖定）
     */
    public static function isPlatformBlocked(string $lineUserId): bool
    {
        return static::getBlockedStoreCount($lineUserId) >= 3;
    }

    /**
     * 獲取鎖定資訊
     */
    public static function getBlock(string $lineUserId, int $storeId): ?self
    {
        return static::where('line_user_id', $lineUserId)
            ->where('store_id', $storeId)
            ->first();
    }

    /**
     * 鎖定客戶
     */
    public static function blockCustomer(
        int $storeId,
        string $lineUserId,
        ?int $customerId = null,
        int $cancellationCount = 0,
        string $reason = 'exceed_cancellation_limit',
        string $blockedBy = 'system',
        ?string $notes = null
    ): self {
        return static::updateOrCreate(
            [
                'store_id' => $storeId,
                'line_user_id' => $lineUserId,
            ],
            [
                'customer_id' => $customerId,
                'reason' => $reason,
                'cancellation_count' => $cancellationCount,
                'blocked_at' => now(),
                'blocked_by' => $blockedBy,
                'notes' => $notes,
            ]
        );
    }

    /**
     * 解鎖客戶
     */
    public static function unblockCustomer(string $lineUserId, int $storeId): bool
    {
        return static::where('line_user_id', $lineUserId)
            ->where('store_id', $storeId)
            ->delete();
    }

    /**
     * 獲取客戶被鎖定的所有店家
     */
    public static function getBlockedStores(string $lineUserId)
    {
        return static::where('line_user_id', $lineUserId)
            ->with('store')
            ->get();
    }
}
