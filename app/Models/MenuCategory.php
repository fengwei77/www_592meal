<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MenuCategory Model (菜單分類)
 *
 * 店家菜單分類，用於組織餐點項目
 */
class MenuCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'name',
        'description',
        'icon',
        'display_order',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * 關聯：所屬店家
     *
     * @return BelongsTo<Store, MenuCategory>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * 關聯：分類下的餐點項目
     *
     * @return HasMany<MenuItem>
     */
    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'category_id');
    }

    /**
     * 關聯：啟用中的餐點項目
     *
     * @return HasMany<MenuItem>
     */
    public function activeMenuItems(): HasMany
    {
        return $this->menuItems()->where('is_active', true);
    }

    /**
     * Scope: 只取得啟用中的分類
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: 依照排序順序排列
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    /**
     * 取得分類下的餐點數量
     *
     * @return int
     */
    public function getMenuItemsCountAttribute(): int
    {
        return $this->menuItems()->count();
    }
}
