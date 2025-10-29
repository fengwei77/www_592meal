<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * MenuItem Model (餐點項目)
 *
 * 店家菜單中的餐點項目
 */
class MenuItem extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'description',
        'price',
        'is_active',
        'is_featured',
        'is_sold_out',
        'display_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_sold_out' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * 關聯：所屬店家
     *
     * @return BelongsTo<Store, MenuItem>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * 關聯：所屬分類
     *
     * @return BelongsTo<MenuCategory, MenuItem>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class);
    }

    /**
     * 關聯：訂單項目 (多對多)
     *
     * @return BelongsToMany<Order>
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_items')
                    ->withPivot(['quantity', 'item_price', 'subtotal', 'special_instructions'])
                    ->withTimestamps();
    }

    /**
     * Scope: 只取得啟用中的餐點
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: 只取得推薦餐點
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: 只取得可訂購的餐點 (啟用且未售完)
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
                     ->where('is_sold_out', false);
    }

    /**
     * Scope: 依照排序順序排列
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    /**
     * 註冊 Spatie Media Library Collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('menu-item-photos')
             ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
             ->useDisk('public');
    }

    /**
     * 註冊 Media Conversions
     */
    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('thumb')
             ->width(200)
             ->height(200)
             ->sharpen(10)
             ->performOnCollections('menu-item-photos');

        $this->addMediaConversion('medium')
             ->width(800)
             ->height(600)
             ->sharpen(10)
             ->performOnCollections('menu-item-photos');

        $this->addMediaConversion('large')
             ->width(1200)
             ->height(900)
             ->sharpen(10)
             ->performOnCollections('menu-item-photos');
    }

    /**
     * 取得主要圖片 URL
     *
     * @return string
     */
    public function getPrimaryImageUrlAttribute(): string
    {
        $media = $this->getFirstMedia('menu-item-photos');
        return $media ? $media->getUrl() : asset('images/default-menu-item.png');
    }

    /**
     * 檢查餐點是否可訂購
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->is_active && !$this->is_sold_out;
    }

    /**
     * 取得格式化的價格
     *
     * @return string
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 0);
    }
}
