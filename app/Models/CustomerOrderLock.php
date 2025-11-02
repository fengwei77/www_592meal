<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerOrderLock extends Model
{
    protected $fillable = [
        'line_user_id',
        'locked_until',
        'reason',
        'cancellation_count',
    ];

    protected $casts = [
        'locked_until' => 'datetime',
    ];

    /**
     * 檢查用戶是否被鎖定
     */
    public static function isLocked(string $lineUserId): bool
    {
        $lock = static::where('line_user_id', $lineUserId)
            ->where('locked_until', '>', now())
            ->first();

        return $lock !== null;
    }

    /**
     * 獲取鎖定資訊
     */
    public static function getLock(string $lineUserId): ?self
    {
        return static::where('line_user_id', $lineUserId)
            ->where('locked_until', '>', now())
            ->first();
    }

    /**
     * 鎖定用戶
     */
    public static function lockUser(string $lineUserId, int $hours = 24, int $cancellationCount = 0): self
    {
        return static::updateOrCreate(
            ['line_user_id' => $lineUserId],
            [
                'locked_until' => now()->addHours($hours),
                'reason' => 'exceed_cancellation_limit',
                'cancellation_count' => $cancellationCount,
            ]
        );
    }

    /**
     * 解鎖用戶
     */
    public static function unlockUser(string $lineUserId): bool
    {
        return static::where('line_user_id', $lineUserId)->delete();
    }

    /**
     * 清理過期的鎖定
     */
    public static function cleanExpiredLocks(): int
    {
        return static::where('locked_until', '<=', now())->delete();
    }
}
