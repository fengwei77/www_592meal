<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreQuotaLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'store_quota_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'old_max_stores',
        'new_max_stores',
        'adjustment',
        'reason',
        'action_type',
        'performed_by',
        'performed_by_name',
        'ip_address',
        'user_agent',
    ];

    /**
     * We don't need to update this model directly.
     */
    public const UPDATED_AT = null;
}
