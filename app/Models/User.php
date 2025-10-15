<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
class User extends Authenticatable
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
            'password' => 'hashed',
            'ip_whitelist_enabled' => 'boolean',
            'ip_whitelist' => 'array',
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_temp_disabled_at' => 'datetime',
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
}
