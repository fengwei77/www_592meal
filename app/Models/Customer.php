<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
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
