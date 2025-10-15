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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
