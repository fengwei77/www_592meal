<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable;
    /**
     * 認證 Guard
     */
    protected $guard = 'customer';

    /**
     * 可填充屬性
     */
    protected $fillable = [
        'name',
        'line_id',
        'avatar_url',
        'phone',
        'email',
        'notification_confirmed',
        'notification_preparing',
        'notification_ready',
    ];

    /**
     * 隱藏屬性
     */
    protected $hidden = [];

    /**
     * 屬性轉換
     */
    protected function casts(): array
    {
        return [
            'notification_confirmed' => 'boolean',
            'notification_preparing' => 'boolean',
            'notification_ready' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * 關聯：訂單
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * 關聯：推播訂閱
     */
    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * 關聯：店家封鎖記錄
     */
    public function storeCustomerBlocks()
    {
        return $this->hasMany(StoreCustomerBlock::class, 'line_user_id', 'line_id');
    }
}
