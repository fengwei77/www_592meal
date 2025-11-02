<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderCancellationLog extends Model
{
    protected $fillable = [
        'line_user_id',
        'order_id',
        'cancelled_at',
        'ip_address',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
    ];

    /**
     * 關聯到訂單
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * 獲取指定時間範圍內的取消次數
     */
    public static function getCancellationCount(string $lineUserId, int $minutes): int
    {
        return static::where('line_user_id', $lineUserId)
            ->where('cancelled_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * 獲取今日的取消次數
     */
    public static function getTodayCancellationCount(string $lineUserId): int
    {
        return static::where('line_user_id', $lineUserId)
            ->whereDate('cancelled_at', today())
            ->count();
    }

    /**
     * 記錄取消動作
     */
    public static function logCancellation(string $lineUserId, int $orderId, ?string $ipAddress = null): self
    {
        return static::create([
            'line_user_id' => $lineUserId,
            'order_id' => $orderId,
            'cancelled_at' => now(),
            'ip_address' => $ipAddress,
        ]);
    }
}
