<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PushSubscription Model (推播訂閱)
 *
 * 儲存用戶的推播訂閱資訊
 */
class PushSubscription extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'endpoint',
        'p256dh_key',
        'auth_key',
        'user_agent',
        'is_active',
        'last_used_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 關聯：顧客
     *
     * @return BelongsTo<Customer, PushSubscription>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 查詢作用域：啟用的訂閱
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 查詢作用域：依客戶查詢
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $customerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * 標記為失效
     *
     * @return bool
     */
    public function markAsInactive(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * 更新最後使用時間
     *
     * @return bool
     */
    public function touchLastUsed(): bool
    {
        return $this->update(['last_used_at' => now()]);
    }

    /**
     * 檢查訂閱是否過期 (超過 30 天未使用)
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if (!$this->last_used_at) {
            return false;
        }

        return $this->last_used_at->lt(now()->subDays(30));
    }
}
