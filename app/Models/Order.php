<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Order Model (訂單資訊)
 *
 * 儲存 Public Schema，包含訂單基本資訊與狀態
 */
class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'customer_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_email',
        'line_user_id',
        'line_display_name',
        'line_picture_url',
        'total_amount',
        'status',
        'cancellation_type',
        'cancellation_reason',
        'cancelled_at',
        'notes',
        'scheduled_for',
        'is_scheduled_order',
        'payment_method',
        'payment_status',
        'pickup_time',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
        'scheduled_for' => 'datetime',
        'is_scheduled_order' => 'boolean',
        'pickup_time' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'pickup_time',
        'completed_at',
        'cancelled_at',
        'created_at',
        'updated_at',
    ];

    /**
     * 關聯：店家
     *
     * @return BelongsTo<Store, Order>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * 關聯：顧客
     *
     * @return BelongsTo<Customer, Order>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 關聯：訂單項目
     *
     * @return HasMany<OrderItem>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * 關聯：菜單項目 (通過 orderItems)
     *
     * @return HasMany<MenuItem>
     */
    public function menuItems(): HasMany
    {
        return $this->hasManyThrough(
            MenuItem::class,
            OrderItem::class,
            'order_id',
            'menu_item_id',
            'id',
            'id'
        );
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
            'completed' => '已完成',
            'cancelled' => '已取消',
            default => '未知狀態',
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
            'completed' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    /**
     * 取得付款狀態顯示名稱
     *
     * @return string
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return match($this->payment_status) {
            'pending' => '待付款',
            'paid' => '已付款',
            'refunded' => '已退款',
            'failed' => '付款失敗',
            default => '未設定',
        };
    }

    /**
     * 檢查訂單是否可以取消
     *
     * @return bool
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    /**
     * 檢查訂單是否已完成
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * 檢查訂單是否已取消
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * 取得訂單商品總數量
     *
     * @return int
     */
    public function getTotalQuantityAttribute(): int
    {
        return $this->orderItems()->sum('quantity');
    }

    /**
     * 取得格式化的總金額
     *
     * @return string
     */
    public function getFormattedTotalAmountAttribute(): string
    {
        return '$' . number_format($this->total_amount, 0);
    }

    /**
     * 產生唯一訂單編號
     *
     * @return string
     */
    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD' . date('Ymd') . strtoupper(str_random(6));
        } while (static::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * 建立訂單並關聯商品
     *
     * @param array $orderData
     * @param array $cartItems
     * @return Order
     */
    public static function createWithItems(array $orderData, array $cartItems): self
    {
        $order = static::create($orderData);

        foreach ($cartItems as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'menu_item_id' => $item['menu_item_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['total_price'],
            ]);
        }

        return $order;
    }

    /**
     * 取得今日訂單數量
     *
     * @param int $storeId
     * @return int
     */
    public static function getTodayOrderCount(int $storeId): int
    {
        return static::where('store_id', $storeId)
                   ->whereDate('created_at', today())
                   ->count();
    }

    /**
     * 取得本月訂單數量
     *
     * @param int $storeId
     * @return int
     */
    public static function getThisMonthOrderCount(int $storeId): int
    {
        return static::where('store_id', $storeId)
                   ->whereMonth('created_at', now()->month)
                   ->whereYear('created_at', now()->year)
                   ->count();
    }

    /**
     * 取得今日總營業額
     *
     * @param int $storeId
     * @return float
     */
    public static function getTodayRevenue(int $storeId): float
    {
        return static::where('store_id', $storeId)
                   ->whereDate('created_at', today())
                   ->where('status', '!=', 'cancelled')
                   ->sum('total_amount');
    }

    /**
     * 取得本月總營業額
     *
     * @param int $storeId
     * @return float
     */
    public static function getThisMonthRevenue(int $storeId): float
    {
        return static::where('store_id', $storeId)
                   ->whereMonth('created_at', now()->month)
                   ->whereYear('created_at', now()->year)
                   ->where('status', '!=', 'cancelled')
                   ->sum('total_amount');
    }

    /**
     * 取得熱銷商品統計
     *
     * @param int $storeId
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public static function getTopSellingItems(int $storeId, int $limit = 10): \Illuminate\Support\Collection
    {
        return static::join('order_items', 'orders.id', '=', 'order_items.order_id')
                   ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
                   ->where('orders.store_id', $storeId)
                   ->where('orders.status', '!=', 'cancelled')
                   ->whereDate('orders.created_at', '>=', now()->subDays(30))
                   ->selectRaw('menu_items.name, SUM(order_items.quantity) as total_quantity, SUM(order_items.total_price) as total_revenue')
                   ->groupBy('menu_items.id', 'menu_items.name')
                   ->orderByDesc('total_quantity')
                   ->limit($limit)
                   ->get();
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
     * 查詢作用域：按店家篩選
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $storeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * 查詢作用域：日期範圍
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * 查詢作用域：今日訂單
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * 查詢作用域：本周訂單
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    /**
     * 查詢作用域：本月訂單
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                   ->whereYear('created_at', now()->year);
    }

    /**
     * 判斷是否為退單
     */
    public function isRejected(): bool
    {
        return $this->status === 'cancelled' && $this->cancellation_type === 'rejected';
    }

    /**
     * 判斷是否為棄單
     */
    public function isAbandoned(): bool
    {
        return $this->status === 'cancelled' && $this->cancellation_type === 'abandoned';
    }

    /**
     * 取得取消類型標籤
     */
    public function getCancellationTypeLabel(): string
    {
        return match($this->cancellation_type) {
            'rejected' => '店家退單',
            'abandoned' => '客人棄單',
            'customer_cancelled' => '客人取消',
            default => '已取消',
        };
    }
}