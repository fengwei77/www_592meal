<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OrderItem Model (訂單項目資訊)
 *
 * 儲存 Public Schema，包含訂單中每個商品的詳細資訊
 */
class OrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'menu_item_id',
        'quantity',
        'unit_price',
        'total_price',
        'special_instructions',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 關聯：訂單
     *
     * @return BelongsTo<Order, OrderItem>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * 關聯：菜單項目
     *
     * @return BelongsTo<MenuItem, OrderItem>
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /**
     * 關聯：店家（通過訂單）
     *
     * @return BelongsTo<Store, OrderItem>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'store_id');
    }

    /**
     * 取得狀態顯示名稱
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => '待確認',
            'confirmed' => '已確認',
            'preparing' => '準備中',
            'ready' => '準備完成',
            'served' => '已提供',
            'cancelled' => '已取消',
            default => '正常',
        };
    }

    /**
     * 取得狀態顏色
     *
     * @return string
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'confirmed' => 'blue',
            'preparing' => 'indigo',
            'ready' => 'green',
            'served' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    /**
     * 取得格式化的單價
     *
     * @return string
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return '$' . number_format($this->unit_price, 0);
    }

    /**
     * 取得格式化的總價
     *
     * @return string
     */
    public function getFormattedTotalPriceAttribute(): string
    {
        return '$' . number_format($this->total_price, 0);
    }

    /**
     * 取得商品名稱（優先使用菜單項目名稱）
     *
     * @return string
     */
    public function getItemNameAttribute(): string
    {
        return $this->menuItem->name ?? '商品已下架';
    }

    /**
     * 取得商品圖片 URL
     *
     * @return string
     */
    public function getItemImageUrlAttribute(): string
    {
        return $this->menuItem ? $this->menuItem->getImageUrl() : asset('images/default-food.jpg');
    }

    /**
     * 檢查商品是否已準備完成
     *
     * @return bool
     */
    public function isReady(): bool
    {
        return in_array($this->status, ['ready', 'served']);
    }

    /**
     * 檢查商品是否已取消
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * 標記為準備中
     *
     * @return bool
     */
    public function markAsPreparing(): bool
    {
        if (in_array($this->status, ['pending', 'confirmed'])) {
            $this->status = 'preparing';
            return $this->save();
        }
        return false;
    }

    /**
     * 標記為準備完成
     *
     * @return bool
     */
    public function markAsReady(): bool
    {
        if ($this->status === 'preparing') {
            $this->status = 'ready';
            return $this->save();
        }
        return false;
    }

    /**
     * 標記為已提供
     *
     * @return bool
     */
    public function markAsServed(): bool
    {
        if ($this->status === 'ready') {
            $this->status = 'served';
            return $this->save();
        }
        return false;
    }

    /**
     * 取消商品
     *
     * @return bool
     */
    public function cancel(): bool
    {
        if (in_array($this->status, ['pending', 'confirmed', 'preparing'])) {
            $this->status = 'cancelled';
            return $this->save();
        }
        return false;
    }

    /**
     * 查詢作用域：按狀態篩選
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 查詢作用域：準備中的商品
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePreparing($query)
    {
        return $query->where('status', 'preparing');
    }

    /**
     * 查詢作用域：準備完成的商品
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    /**
     * 查詢作用域：已提供的商品
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeServed($query)
    {
        return $query->where('status', 'served');
    }

    /**
     * 查詢作用域：已取消的商品
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($orderItem) {
            // 如果沒有指定狀態，預設為正常
            if (!$orderItem->status) {
                $orderItem->status = 'pending';
            }
        });

        static::updating(function ($orderItem) {
            // 確保狀態變更的合理性
            $validTransitions = [
                'pending' => ['confirmed', 'preparing', 'cancelled'],
                'confirmed' => ['preparing', 'cancelled'],
                'preparing' => ['ready', 'cancelled'],
                'ready' => ['served'],
                'served' => [],
                'cancelled' => [],
            ];

            $newStatus = $orderItem->status;
            $oldStatus = $orderItem->getOriginal('status');

            if (!in_array($newStatus, $validTransitions[$oldStatus] ?? [])) {
                throw new \InvalidArgumentException("Invalid status transition from {$oldStatus} to {$newStatus}");
            }
        });
    }
}