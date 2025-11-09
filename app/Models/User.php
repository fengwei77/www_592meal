<?php

namespace App\Models;

use Carbon\Carbon;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * User Model (店家、管理員)
 *
 * 使用 spatie/laravel-permission 進行角色與權限管理
 *
 * Roles:
 * - super_admin: 超級管理員（可審核 LINE Pay、管理所有店家）
 * - store_owner: 店家（可管理自己的產品、訂單、設定）
 */
class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type', // Add this
        'max_stores', // Add this
        'line_id', // Add this
        'avatar_url', // Add this
        'email_verification_code',
        'email_verification_code_expires_at',
        'ip_whitelist_enabled',
        'ip_whitelist',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'two_factor_temp_disabled_at',
        'password_reset_attempts',
        'password_reset_last_attempt_at',
        // 訂閱相關欄位
        'trial_ends_at',
        'subscription_ends_at',
        'is_trial_used',
        'last_subscription_reminder_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_code_expires_at' => 'datetime',
            'password' => 'hashed',
            'ip_whitelist_enabled' => 'boolean',
            'ip_whitelist' => 'array',
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_temp_disabled_at' => 'datetime',
            'password_reset_attempts' => 'integer',
            'password_reset_last_attempt_at' => 'datetime',
            // 訂閱相關 casts
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'is_trial_used' => 'boolean',
            'last_subscription_reminder_at' => 'datetime',
            'password_reset_last_attempt_at' => 'datetime',
        ];
    }

    /**
     * 檢查當前 IP 是否在白名單內
     */
    public function isIpAllowed(string $ip): bool
    {
        // 如果未啟用 IP 白名單，則允許所有 IP
        if (!$this->ip_whitelist_enabled) {
            return true;
        }

        // 如果白名單為空，則拒絕所有 IP
        if (empty($this->ip_whitelist)) {
            return false;
        }

        // 檢查 IP 是否在白名單內
        return in_array($ip, $this->ip_whitelist);
    }

    /**
     * 新增 IP 到白名單
     */
    public function addIpToWhitelist(string $ip): void
    {
        $whitelist = $this->ip_whitelist ?? [];

        if (!in_array($ip, $whitelist)) {
            $whitelist[] = $ip;
            $this->ip_whitelist = $whitelist;
            $this->save();
        }
    }

    /**
     * 從白名單移除 IP
     */
    public function removeIpFromWhitelist(string $ip): void
    {
        $whitelist = $this->ip_whitelist ?? [];

        $this->ip_whitelist = array_values(array_filter($whitelist, fn($item) => $item !== $ip));
        $this->save();
    }

    /**
     * 檢查 2FA 是否已設定並啟用（考慮臨時關閉狀態）
     */
    public function hasTwoFactorEnabled(): bool
    {
        // 如果被臨時關閉，檢查是否超過24小時
        if ($this->isTwoFactorTempDisabled()) {
            // 如果超過24小時，自動恢復
            if ($this->two_factor_temp_disabled_at->addHours(24)->isPast()) {
                $this->restoreTwoFactor();
                return true;
            }
            // 仍在臨時關閉期間
            return false;
        }

        return $this->two_factor_enabled && !empty($this->two_factor_secret);
    }

    /**
     * 檢查 2FA 是否被臨時關閉
     */
    public function isTwoFactorTempDisabled(): bool
    {
        return !is_null($this->two_factor_temp_disabled_at);
    }

    /**
     * 臨時關閉 2FA (24小時後自動恢復)
     */
    public function tempDisableTwoFactor(): void
    {
        $this->two_factor_temp_disabled_at = now();
        $this->save();
    }

    /**
     * 恢復被臨時關閉的 2FA
     */
    public function restoreTwoFactor(): void
    {
        $this->two_factor_temp_disabled_at = null;
        $this->save();
    }

    /**
     * 確認 2FA (同時清除臨時關閉狀態)
     */
    public function confirmTwoFactor(): void
    {
        $this->two_factor_confirmed_at = now();
        $this->two_factor_temp_disabled_at = null; // 確認時清除臨時關閉狀態
        $this->save();
    }

    /**
     * 停用 2FA (完全移除)
     */
    public function disableTwoFactor(): void
    {
        $this->two_factor_enabled = false;
        $this->two_factor_secret = null;
        $this->two_factor_recovery_codes = null;
        $this->two_factor_confirmed_at = null;
        $this->two_factor_temp_disabled_at = null;
        $this->save();
    }

    /**
     * 發送密碼重設通知
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\AdminPasswordResetNotification($token));
    }

    /**
     * 使用者擁有的店家 (一對多關聯)
     */
    public function stores()
    {
        return $this->hasMany(\App\Models\Store::class);
    }

    /**
     * 檢查用戶是否可以訪問 Filament 管理面板
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        // 只有超級管理員和店家擁有者可以訪問後台
        return $this->hasRole(['super_admin', 'store_owner']);
    }

    /**
     * 檢查用戶是否為超級管理員
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * 檢查用戶是否為店家擁有者
     */
    public function isStoreOwner(): bool
    {
        return $this->hasRole('store_owner');
    }

    // ========================================
    // 訂閱系統相關方法
    // ========================================

    /**
     * 關聯訂單紀錄
     */
    public function subscriptionOrders()
    {
        return $this->hasMany(SubscriptionOrder::class, 'user_id');
    }

    /**
     * 檢查是否在試用期內
     */
    public function isInTrialPeriod(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * 檢查是否有有效訂閱
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_ends_at && $this->subscription_ends_at->isFuture();
    }

    /**
     * 檢查訂閱是否已過期
     */
    public function hasExpiredSubscription(): bool
    {
        return $this->subscription_ends_at && $this->subscription_ends_at->isPast();
    }

    /**
     * 取得訂閱狀態
     */
    public function getSubscriptionStatus(): string
    {
        if ($this->isInTrialPeriod()) {
            return 'trial';
        } elseif ($this->hasActiveSubscription()) {
            return 'active';
        } else {
            return 'expired';
        }
    }

    /**
     * 取得訂閱狀態標籤
     */
    public function getSubscriptionStatusLabel(): string
    {
        return match ($this->getSubscriptionStatus()) {
            'trial' => '試用期中',
            'active' => '訂閱有效',
            'expired' => '訂閱過期',
            default => '未訂閱',
        };
    }

    /**
     * 取得訂閱狀態顏色
     */
    public function getSubscriptionStatusColor(): string
    {
        return match ($this->getSubscriptionStatus()) {
            'trial' => 'info',
            'active' => 'success',
            'expired' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * 取得訂閱剩餘天數
     */
    public function getSubscriptionRemainingDays(): int
    {
        $endDate = $this->subscription_ends_at ?: $this->trial_ends_at;

        if (!$endDate) {
            return 0;
        }

        return max(0, now()->diffInDays($endDate));
    }

    /**
     * 取得訂閱到期日
     */
    public function getSubscriptionExpiryDate(): ?Carbon
    {
        return $this->subscription_ends_at ?: $this->trial_ends_at;
    }

    /**
     * 檢查訂閱是否即將到期 (預設7天內)
     */
    public function isSubscriptionExpiringSoon(int $days = 7): bool
    {
        $endDate = $this->getSubscriptionExpiryDate();

        if (!$endDate) {
            return false;
        }

        return now()->diffInDays($endDate) <= $days && $endDate->isFuture();
    }

    /**
     * 初始化試用期
     */
    public function initializeTrial(): bool
    {
        if ($this->is_trial_used) {
            return false;
        }

        $trialDays = config('ecpay.trial_days', 30);
        $this->trial_ends_at = now()->addDays($trialDays);
        $this->is_trial_used = true;

        return $this->save();
    }

    /**
     * 延長訂閱
     */
    public function extendSubscription(int $months): bool
    {
        if ($months <= 0) {
            return false;
        }

        $startDate = $this->hasActiveSubscription() || $this->isInTrialPeriod()
            ? $this->getSubscriptionExpiryDate()
            : now();

        $this->subscription_ends_at = $startDate->addDays($months * 30);

        return $this->save();
    }

    /**
     * 設定訂閱到期日
     */
    public function setSubscriptionExpiryDate(Carbon $date): bool
    {
        $this->subscription_ends_at = $date;
        return $this->save();
    }

    /**
     * 檢查是否需要發送到期提醒
     */
    public function needsExpiryReminder(): bool
    {
        if (!$this->getSubscriptionExpiryDate()) {
            return false;
        }

        // 檢查是否已經發送過提醒
        if ($this->last_subscription_reminder_at) {
            $daysSinceReminder = now()->diffInDays($this->last_subscription_reminder_at);
            if ($daysSinceReminder < 7) { // 7天內不重複提醒
                return false;
            }
        }

        // 檢查是否在提醒時間範圍內
        return $this->isSubscriptionExpiringSoon();
    }

    /**
     * 標記已發送到期提醒
     */
    public function markExpiryReminderSent(): bool
    {
        $this->last_subscription_reminder_at = now();
        return $this->save();
    }

    /**
     * 取得最新訂單
     */
    public function getLatestOrder(): ?SubscriptionOrder
    {
        return $this->subscriptionOrders()->latest()->first();
    }

    /**
     * 取得未過期的訂單
     */
    public function getActiveOrders()
    {
        return $this->subscriptionOrders()
            ->where('status', 'pending')
            ->where('expire_date', '>', now())
            ->get();
    }

    /**
     * 檢查是否有未付款的訂單
     */
    public function hasPendingOrders(): bool
    {
        return $this->subscriptionOrders()
            ->where('status', 'pending')
            ->where('expire_date', '>', now())
            ->exists();
    }
}
