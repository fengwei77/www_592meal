<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Store Model (店家資訊)
 *
 * 儲存在 Public Schema，包含店家基本資訊與設定
 * 每個 Store 對應一個 Tenant (一對一關係)
 */
class Store extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

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
        'is_featured',
        // Phase 1 新增欄位
        'description',
        'store_type',
        'latitude',
        'longitude',
        'business_hours',
        'special_hours',
        'social_links',
        // Story 2.1 新增欄位
        'service_mode',
        // 店家清單功能新增欄位
        'city',
        'area',
        // 圖片欄位
        'store_logo',
        'store_cover_image',
        'store_photos',
        // Slug 相關欄位
        'store_slug_name',
        // 店員密碼
        'staff_password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        // staff_password 移除 hidden，因為需要在管理介面中顯示
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
        'is_featured' => 'boolean',
        // Phase 1 新增類型轉換
        'business_hours' => 'array',
        'special_hours' => 'array',
        'social_links' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        // 圖片欄位類型轉換
        'store_photos' => 'array',
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
     * 獲取今日營業結束時間
     *
     * @return \Carbon\Carbon|null
     */
    public function getTodayClosingTime(): ?\Carbon\Carbon
    {
        if (!$this->business_hours) {
            return null;
        }

        $today = now();
        $dayOfWeek = strtolower($today->format('l'));

        if (!isset($this->business_hours[$dayOfWeek])) {
            return null;
        }

        $dayHours = $this->business_hours[$dayOfWeek];
        if (!$dayHours['is_open'] || empty($dayHours['closes_at'])) {
            return null;
        }

        return $today->copy()->setTimeFromTimeString($dayHours['closes_at']);
    }

    /**
     * 獲取訂單的預訂日期（如果不在營業時間內）
     *
     * @return array ['is_scheduled' => bool, 'scheduled_date' => Carbon|null, 'message' => string]
     */
    public function getOrderScheduleInfo(): array
    {
        if (!$this->business_hours) {
            return [
                'is_scheduled' => false,
                'scheduled_date' => null,
                'message' => '即時訂單'
            ];
        }

        $now = now();
        $dayOfWeek = strtolower($now->format('l'));
        $currentTime = $now->format('H:i');

        // 檢查今天是否營業
        if (isset($this->business_hours[$dayOfWeek])) {
            $todayHours = $this->business_hours[$dayOfWeek];

            // 今天有營業且目前在營業時間內
            if ($todayHours['is_open'] &&
                !empty($todayHours['opens_at']) &&
                !empty($todayHours['closes_at']) &&
                $currentTime >= $todayHours['opens_at'] &&
                $currentTime <= $todayHours['closes_at']) {
                return [
                    'is_scheduled' => false,
                    'scheduled_date' => null,
                    'message' => '即時訂單'
                ];
            }
        }

        // 不在營業時間，尋找下一個營業日
        $nextOpenDate = $this->getNextOpenDate();

        if ($nextOpenDate) {
            $dayName = $nextOpenDate->locale('zh_TW')->isoFormat('M月D日 (ddd)');
            return [
                'is_scheduled' => true,
                'scheduled_date' => $nextOpenDate,
                'message' => "此為 {$dayName} 的預訂單"
            ];
        }

        // 沒有找到下一個營業日（可能店家未設定營業時間）
        return [
            'is_scheduled' => false,
            'scheduled_date' => null,
            'message' => '即時訂單'
        ];
    }

    /**
     * 獲取下一個營業日
     *
     * @return \Carbon\Carbon|null
     */
    public function getNextOpenDate(): ?\Carbon\Carbon
    {
        if (!$this->business_hours) {
            return null;
        }

        $checkDate = now()->addDay(); // 從明天開始檢查
        $maxDays = 7; // 最多檢查7天

        for ($i = 0; $i < $maxDays; $i++) {
            $dayOfWeek = strtolower($checkDate->format('l'));

            if (isset($this->business_hours[$dayOfWeek])) {
                $dayHours = $this->business_hours[$dayOfWeek];

                if ($dayHours['is_open'] && !empty($dayHours['opens_at'])) {
                    return $checkDate->copy()->setTimeFromTimeString($dayHours['opens_at']);
                }
            }

            $checkDate->addDay();
        }

        return null;
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
        return $this->getFirstMedia('store-logo') || $this->getFirstMedia('store-cover') || $this->getMedia('store-photos')->isNotEmpty();
    }

    /**
     * 取得主要圖片 URL
     *
     * @return string
     */
    public function getPrimaryImageUrlAttribute(): string
    {
        // 嘗試獲取 logo
        $logo = $this->getFirstMedia('store-logo');
        if ($logo) {
            return $logo->getUrl();
        }

        // 嘗試獲取封面圖
        $cover = $this->getFirstMedia('store-cover');
        if ($cover) {
            return $cover->getUrl();
        }

        // 嘗試獲取第一張商家照片
        $firstPhoto = $this->getMedia('store-photos')->first();
        if ($firstPhoto) {
            return $firstPhoto->getUrl();
        }

        return asset('images/default-store.svg');
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

    /**
     * 取得今日營業時間
     *
     * @return array|null
     */
    public function getTodayBusinessHours(): ?array
    {
        if (!$this->business_hours) {
            return null;
        }

        $today = strtolower(now('Asia/Taipei')->englishDayOfWeek);
        return $this->business_hours[$today] ?? null;
    }

    /**
     * 判斷今日是否營業
     *
     * @return bool
     */
    public function isOpenToday(): bool
    {
        $hours = $this->getTodayBusinessHours();
        return $hours && ($hours['is_open'] ?? false);
    }

    /**
     * 判斷目前是否在營業時間內
     *
     * @return bool
     */
    public function isCurrentlyOpen(): bool
    {
        if (!$this->isOpenToday()) {
            return false;
        }

        $hours = $this->getTodayBusinessHours();
        $now = now('Asia/Taipei');
        $openTime = \Carbon\Carbon::parse($hours['open_time'] ?? $hours['opens_at'], 'Asia/Taipei');
        $closeTime = \Carbon\Carbon::parse($hours['close_time'] ?? $hours['closes_at'], 'Asia/Taipei');

        // 處理跨午夜情況 (如 22:00 - 02:00)
        if ($closeTime->lessThan($openTime)) {
            $closeTime->addDay();
        }

        return $now->between($openTime, $closeTime);
    }

    /**
     * 取得服務模式顯示名稱
     *
     * @return string
     */
    public function getServiceModeLabelAttribute(): string
    {
        return match($this->service_mode) {
            'pickup' => '店址取餐',
            'onsite' => '駐點服務',
            'hybrid' => '混合模式',
            default => '未設定',
        };
    }

    /**
     * 判斷是否支援店址取餐
     *
     * @return bool
     */
    public function supportsPickup(): bool
    {
        return in_array($this->service_mode, ['pickup', 'hybrid']);
    }

    /**
     * 判斷是否支援駐點服務
     *
     * @return bool
     */
    public function supportsOnsite(): bool
    {
        return in_array($this->service_mode, ['onsite', 'hybrid']);
    }

    /**
     * 取得特定日期的營業時間（優先檢查特殊節日設定）
     *
     * @param \Carbon\Carbon|string|null $date
     * @return array|null
     */
    public function getBusinessHoursForDate($date = null): ?array
    {
        $date = $date ? \Carbon\Carbon::parse($date, 'Asia/Taipei') : now('Asia/Taipei');
        $dateString = $date->format('Y-m-d');

        // 1. 優先檢查特殊節日設定
        if ($this->special_hours) {
            foreach ($this->special_hours as $specialDay) {
                if (isset($specialDay['date']) && $specialDay['date'] === $dateString) {
                    return $specialDay;
                }
            }
        }

        // 2. 使用正常營業時間
        $dayOfWeek = strtolower($date->englishDayOfWeek);
        return $this->business_hours[$dayOfWeek] ?? null;
    }

    /**
     * 判斷特定日期是否營業
     *
     * @param \Carbon\Carbon|string|null $date
     * @return bool
     */
    public function isOpenOnDate($date = null): bool
    {
        $hours = $this->getBusinessHoursForDate($date);
        return $hours && ($hours['is_open'] ?? false);
    }

    /**
     * 註冊 Spatie Media Library Collections
     */
    public function registerMediaCollections(): void
    {
        //店家 Logo
        $this->addMediaCollection('store-logo')
             ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
             ->singleFile()
             ->useDisk('public')
             ->withResponsiveImages();

        // 店家封面圖
        $this->addMediaCollection('store-cover')
             ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
             ->singleFile()
             ->useDisk('public')
             ->withResponsiveImages();

        // 商家照片
        $this->addMediaCollection('store-photos')
             ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
             ->useDisk('public')
             ->withResponsiveImages();
    }

    /**
     * 註冊 Media Conversions (額外的轉換)
     */
    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        // Logo 轉換
        $this->addMediaConversion('thumb')
             ->width(150)
             ->height(150)
             ->sharpen(10)
             ->performOnCollections('store-logo');

        // 封面圖轉換
        $this->addMediaConversion('thumb')
             ->width(400)
             ->height(300)
             ->sharpen(10)
             ->performOnCollections('store-cover');

        // 商家照片轉換
        $this->addMediaConversion('thumb')
             ->width(200)
             ->height(200)
             ->sharpen(10)
             ->performOnCollections('store-photos');

        // 中等尺寸轉換
        $this->addMediaConversion('medium')
             ->width(600)
             ->height(600)
             ->sharpen(10)
             ->performOnCollections('store-logo', 'store-cover', 'store-photos');

        // 大尺寸轉換
        $this->addMediaConversion('large')
             ->width(1200)
             ->height(900)
             ->sharpen(10)
             ->performOnCollections('store-cover', 'store-photos');
    }

    /**
     * 取得店家 Logo URL
     *
     * @return string
     */
    public function getLogoUrlAttribute(): string
    {
        if ($this->store_logo) {
            // 根據當前請求的域名生成正確的 URL
            $baseUrl = request()->getSchemeAndHttpHost();
            return $baseUrl . '/storage/' . $this->store_logo;
        }
        return request()->getSchemeAndHttpHost() . '/images/default-store.svg';
    }

    /**
     * 取得封面圖片 URL
     *
     * @return string
     */
    public function getCoverImageUrlAttribute(): string
    {
        if ($this->store_cover_image) {
            // 根據當前請求的域名生成正確的 URL
            $baseUrl = request()->getSchemeAndHttpHost();
            return $baseUrl . '/storage/' . $this->store_cover_image;
        }
        return request()->getSchemeAndHttpHost() . '/images/default-store-cover.jpg';
    }

    /**
     * 處理 store_logo 屬性（儲存檔名）
     *
     * @param mixed $value
     * @return void
     */
    public function setStoreLogoAttribute($value): void
    {
        // 處理陣列格式（Filament 可能傳遞的不同格式）
        if (is_array($value)) {
            if (isset($value[0])) {
                // 格式: ['filename.jpg']
                $this->attributes['store_logo'] = $value[0];
            } elseif (isset($value['file_name'])) {
                // 格式: ['file_name' => 'filename.jpg']
                $this->attributes['store_logo'] = $value['file_name'];
            } else {
                // 其他陣列格式，取第一個字串值
                $stringValue = null;
                foreach ($value as $item) {
                    if (is_string($item) && !empty($item)) {
                        $stringValue = $item;
                        break;
                    }
                }
                $this->attributes['store_logo'] = $stringValue;
            }
        } elseif (is_string($value) && !empty($value)) {
            // 檢查是否已經包含正確的目錄路徑
            if (str_starts_with($value, 'store-logos/')) {
                // 如果已經包含正確的路徑，直接儲存
                $this->attributes['store_logo'] = $value;
            } else {
                // 如果只有檔名，添加目錄前綴
                $this->attributes['store_logo'] = 'store-logos/' . basename($value);
            }
        } else {
            // 處理空值或無效值
            $this->attributes['store_logo'] = null;
        }
    }

    /**
     * 處理 store_cover_image 屬性（儲存檔名）
     *
     * @param mixed $value
     * @return void
     */
    public function setStoreCoverImageAttribute($value): void
    {
        // 處理單檔案上傳
        if (is_string($value)) {
            // 檢查是否已經包含正確的目錄路徑
            if (str_starts_with($value, 'store-covers/')) {
                // 如果已經包含正確的路徑，直接儲存
                $this->attributes['store_cover_image'] = $value;
            } else {
                // 如果只有檔名，添加目錄前綴
                $this->attributes['store_cover_image'] = 'store-covers/' . basename($value);
            }
        } elseif (is_array($value) && isset($value[0])) {
            $coverPath = $value[0];
            if (!str_starts_with($coverPath, 'store-covers/')) {
                $coverPath = 'store-covers/' . basename($coverPath);
            }
            $this->attributes['store_cover_image'] = $coverPath;
        } elseif (empty($value)) {
            $this->attributes['store_cover_image'] = null;
        }
    }

    /**
     * 處理 store_photos 屬性（儲存檔名陣列）
     *
     * @param mixed $value
     * @return void
     */
    public function setStorePhotosAttribute($value): void
    {
        if (is_array($value)) {
            $filenames = [];
            foreach ($value as $file) {
                if (is_array($file) && isset($file['file_name'])) {
                    $filename = $file['file_name'];
                    if (!str_starts_with($filename, 'store-photos/')) {
                        $filename = 'store-photos/' . basename($filename);
                    }
                    $filenames[] = $filename;
                } elseif (is_string($file)) {
                    if (!str_starts_with($file, 'store-photos/')) {
                        $file = 'store-photos/' . basename($file);
                    }
                    $filenames[] = $file;
                }
            }
            $this->attributes['store_photos'] = json_encode($filenames);
        } elseif (is_string($value)) {
            // 處理單個字串（可能是 JSON 或單一檔名）
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->attributes['store_photos'] = json_encode($decoded);
            } else {
                // 單一檔名，轉為陣列
                $this->attributes['store_photos'] = json_encode([$value]);
            }
        } elseif (empty($value)) {
            $this->attributes['store_photos'] = json_encode([]);
        }
    }

    /**
     * 取得商家照片陣列
     *
     * @return array
     */
    // 暫時完全禁用此方法
    // public function getStorePhotosAttribute(): array
    // {
    //     return [];
    // }

    /**
     * 取得第一張商家照片（主要照片）
     *
     * @return string
     */
    public function getFirstPhotoUrlAttribute(): string
    {
        try {
            $photos = $this->getStorePhotosAttribute();

            if (!empty($photos) && isset($photos[0]['url'])) {
                return $photos[0]['url'];
            }

            return request()->getSchemeAndHttpHost() . '/images/default-store-cover.jpg';
        } catch (\Exception $e) {
            // 發生錯誤時回傳預設圖片
            return request()->getSchemeAndHttpHost() . '/images/default-store-cover.jpg';
        }
    }

    /**
     * 取得店家類型標籤
     *
     * @return string
     */
    public function getTypeLabel(): string
    {
        return $this->getStoreTypeLabelAttribute();
    }

    /**
     * 取得營業時間文字描述
     *
     * @return string
     */
    public function getOpenHoursText(): string
    {
        if (!$this->isCurrentlyOpen()) {
            $hours = $this->getTodayBusinessHours();
            if (!$hours || !($hours['is_open'] ?? false)) {
                return '今日休息';
            }
            return '休息中';
        }

        $hours = $this->getTodayBusinessHours();
        if (!$hours) {
            return '未設定營業時間';
        }

        $now = now('Asia/Taipei');
        $closeTime = $hours['close_time'] ?? $hours['closes_at'] ?? null;

        if ($closeTime) {
            $closeTime = \Carbon\Carbon::parse($closeTime, 'Asia/Taipei');
            return "營業至 {$closeTime->format('H:i')}";
        }

        return '營業中';
    }

    /**
     * 取得服務模式標籤
     *
     * @return string
     */
    public function getServiceModeLabel(): string
    {
        return $this->getServiceModeLabelAttribute();
    }

    /**
     * 取得平均評分 (暫時回傳預設值)
     *
     * @return float
     */
    public function getAverageRating(): float
    {
        // TODO: 實作真實的評分計算邏輯
        return 4.5;
    }

    /**
     * 取得店家服務模式圖示
     *
     * @return string
     */
    public function getServiceIcon(): string
    {
        return match($this->service_mode) {
            'pickup' => '🥡',
            'onsite' => '🍽️',
            'hybrid' => '🍴',
            default => '🏪'
        };
    }

    /**
     * 取得店家摘要資訊
     *
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'subdomain' => $this->subdomain,
            'type' => $this->store_type,
            'type_label' => $this->getTypeLabel(),
            'address' => $this->address,
            'city' => $this->city,
            'area' => $this->area,
            'phone' => $this->phone,
            'description' => $this->description,
            'logo_url' => $this->logo_url,
            'cover_image_url' => $this->cover_image_url,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_open' => $this->isCurrentlyOpen(),
            'open_hours_text' => $this->getOpenHoursText(),
            'service_mode' => $this->service_mode,
            'service_mode_label' => $this->getServiceModeLabel(),
            'is_featured' => $this->is_featured,
            'created_at' => $this->created_at->format('Y-m-d'),
        ];
    }

    /**
     * 取得店家 slug (store_slug_name)
     *
     * @return string
     */
    public function getStoreSlugAttribute(): string
    {
        return $this->store_slug_name ?: $this->generateDefaultSlug();
    }

    // mutator 暫時移除，改用 EditStore 頁面處理

    /**
     * 生成店家 slug
     *
     * @param string $value
     * @return string
     */
    private function generateSlug(string $value): string
    {
        // 移除非中文字符以外的符號，並轉換為小寫
        // 使用正確的Unicode語法支援中文字符
        $slug = preg_replace('/[^a-zA-Z0-9\x{4e00}-\x{9fa5}-]/u', '-', $value);
        $slug = strtolower($slug);

        // 移除開頭和結尾的連字符
        $slug = trim($slug, '-');

        // 將多個連字符合併為一個
        $slug = preg_replace('/-+/', '-', $slug);

        return $slug;
    }

    /**
     * 生成預設 slug (基於 id)
     *
     * @return string
     */
    private function generateDefaultSlug(): string
    {
        return 's' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * 取得店家完整 URL
     *
     * @return string
     */
    public function getStoreUrlAttribute(): string
    {
        $baseUrl = config('app.url');
        $slug = $this->getStoreSlugAttribute();

        return "{$baseUrl}/store/{$slug}";
    }

    /**
     * 檢查 slug 是否可用
     *
     * @param string $slug
     * @param int|null $excludeId
     * @return bool
     */
    public static function isSlugAvailable(string $slug, ?int $excludeId = null): bool
    {
        $query = static::where('store_slug_name', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return !$query->exists();
    }

    /**
     * 生成唯一的 slug
     *
     * @param string $value
     * @param int|null $excludeId
     * @return string
     */
    public static function generateUniqueSlug(string $value, ?int $excludeId = null): string
    {
        $slug = (new static)->generateSlug($value);
        $originalSlug = $slug;
        $count = 1;

        while (!static::isSlugAvailable($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
