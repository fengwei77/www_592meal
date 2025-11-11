<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Website Login Log Model
 *
 * 記錄網站前台登入日誌
 */
class WebsiteLoginLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'ip_address',
        'user_agent',
        'success',
        'failure_reason',
    ];

    protected $casts = [
        'success' => 'boolean',
    ];
}