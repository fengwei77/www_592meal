<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmailVerificationCode extends Model
{
    protected $fillable = [
        'email',
        'code',
        'type',
        'expires_at',
        'verified_at',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * 產生新的驗證碼
     */
    public static function generate(string $email, string $type = 'password_reset', int $expiresInMinutes = 10): self
    {
        // 刪除舊的未驗證的驗證碼
        self::where('email', $email)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->delete();

        // 產生新的6位數驗證碼
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        return self::create([
            'email' => $email,
            'code' => $code,
            'type' => $type,
            'expires_at' => now()->addMinutes($expiresInMinutes),
        ]);
    }

    /**
     * 驗證驗證碼
     */
    public static function verify(string $email, string $code, string $type = 'password_reset'): bool
    {
        $verification = self::where('email', $email)
            ->where('code', $code)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$verification) {
            // 記錄失敗嘗試
            self::where('email', $email)
                ->where('type', $type)
                ->whereNull('verified_at')
                ->increment('attempts');

            return false;
        }

        // 標記為已驗證
        $verification->update(['verified_at' => now()]);

        return true;
    }

    /**
     * 檢查是否已驗證
     */
    public static function isVerified(string $email, string $type = 'password_reset'): bool
    {
        return self::where('email', $email)
            ->where('type', $type)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>', now()->subMinutes(60)) // 驗證後60分鐘內有效
            ->exists();
    }

    /**
     * 清除已驗證的記錄
     */
    public static function clearVerified(string $email, string $type = 'password_reset'): void
    {
        self::where('email', $email)
            ->where('type', $type)
            ->whereNotNull('verified_at')
            ->delete();
    }

    /**
     * 檢查是否過期
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * 檢查嘗試次數是否過多
     */
    public function tooManyAttempts(): bool
    {
        return $this->attempts >= 5;
    }
}
