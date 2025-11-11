<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin Login Log Model
 *
 * 記錄後台管理面板登入日誌
 */
class AdminLoginLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'ip_address',
        'user_agent',
        'success',
        'failure_reason',
    ];

    protected $casts = [
        'success' => 'boolean',
    ];

    /**
     * 關聯用戶
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}