<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Store Model (店家資訊)
 *
 * 儲存在 Public Schema，包含店家基本資訊與設定
 * 每個 Store 對應一個 Tenant (一對一關係)
 */
class Store extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'subdomain',
        'phone',
        'address',
        'settings',
        'line_pay_settings',
        'is_active',
        // Phase 1 新增欄位
        'description',
        'store_type',
        'latitude',
        'longitude',
        'business_hours',
        'logo_url',
        'cover_image_url',
        'social_links',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
        'line_pay_settings' => 'array',
        'is_active' => 'boolean',
        // Phase 1 新增類型轉換
        'business_hours' => 'array',
        'social_links' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * 關聯：店家老闆 (User)
     *
     * @return BelongsTo<User, Store>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 關聯：租戶 Schema (一對一)
     *
     * @return HasOne<Tenant>
     */
    public function tenant(): HasOne
    {
        return $this->hasOne(Tenant::class);
    }

    /**
     * 檢查是否已啟用 LINE Pay
     *
     * @return bool
     */
    public function hasLinePayEnabled(): bool
    {
        return ($this->line_pay_settings['approval_status'] ?? null) === 'approved'
            && ($this->line_pay_settings['enabled'] ?? false);
    }

    /**
     * 關聯：菜單分類
     *
     * @return HasMany<MenuCategory>
     */
    public function menuCategories(): HasMany
    {
        return $this->hasMany(MenuCategory::class);
    }

    /**
     * 關聯：菜單項目
     *
     * @return HasMany<MenuItem>
     */
    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    /**
     * 關聯：訂單
     *
     * @return HasMany<Order>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * 取得店家類型顯示名稱
     *
     * @return string
     */
    public function getStoreTypeLabelAttribute(): string
    {
        return match($this->store_type) {
            'restaurant' => '餐廳',
            'cafe' => '咖啡廳',
            'snack' => '小吃店',
            'bar' => '酒吧',
            'bakery' => '烘焙店',
            'other' => '其他',
            default => '未分類',
        };
    }

    /**
     * 檢查店家目前是否營業中
     *
     * @return bool
     */
    public function isOpenNow(): bool
    {
        if (!$this->business_hours) {
            return false;
        }

        $now = now();
        $dayOfWeek = strtolower($now->format('l'));
        $currentTime = $now->format('H:i');

        if (!isset($this->business_hours[$dayOfWeek])) {
            return false;
        }

        $dayHours = $this->business_hours[$dayOfWeek];
        if (!$dayHours['is_open'] || empty($dayHours['opens_at']) || empty($dayHours['closes_at'])) {
            return false;
        }

        return $currentTime >= $dayHours['opens_at'] && $currentTime <= $dayHours['closes_at'];
    }

    /**
     * 取得店家完整地址 (用於地圖顯示)
     *
     * @return string
     */
    public function getFullAddressAttribute(): string
    {
        return $this->address;
    }

    /**
     * 檢查是否有店家圖片
     *
     * @return bool
     */
    public function hasImages(): bool
    {
        return !empty($this->logo_url) || !empty($this->cover_image_url);
    }

    /**
     * 取得主要圖片 URL
     *
     * @return string
     */
    public function getPrimaryImageUrlAttribute(): string
    {
        return $this->logo_url ?: $this->cover_image_url ?: asset('images/default-store.png');
    }

    /**
     * 檢查是否為當前用戶的店家
     *
     * @param User|null $user
     * @return bool
     */
    public function isOwnedBy(?User $user): bool
    {
        return $user && $this->user_id === $user->id;
    }
}
