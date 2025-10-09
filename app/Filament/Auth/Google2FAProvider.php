<?php

namespace App\Filament\Auth;

use App\Models\User;
use Filament\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\HtmlString;
use PragmaRX\Google2FA\Google2FA;

/**
 * Google 2FA Provider for Filament v4
 *
 * 處理登入時的雙因素認證驗證
 */
class Google2FAProvider implements MultiFactorAuthenticationProvider
{
    /**
     * 取得 Provider 唯一 ID
     */
    public function getId(): string
    {
        return 'google2fa';
    }

    /**
     * 取得登入表單標籤
     */
    public function getLoginFormLabel(): string
    {
        return 'Google Authenticator';
    }

    /**
     * 檢查用戶是否啟用 2FA
     */
    public function isEnabled(Authenticatable $user): bool
    {
        if (!($user instanceof User)) {
            return false;
        }

        // 檢查是否被臨時關閉
        if ($user->isTwoFactorTempDisabled()) {
            // 如果超過 24 小時，自動恢復（這會在 User model 中處理）
            if ($user->two_factor_temp_disabled_at->addHours(24)->isPast()) {
                $user->restoreTwoFactor();
                return $user->two_factor_enabled && $user->two_factor_confirmed_at !== null;
            }
            // 仍在臨時關閉期間，不要求 2FA
            return false;
        }

        // 檢查是否啟用且已確認
        return $user->two_factor_enabled && $user->two_factor_confirmed_at !== null;
    }

    /**
     * 取得登入時的挑戰表單組件
     *
     * @return array<Component>
     */
    public function getChallengeFormComponents(Authenticatable $user): array
    {
        return [
            TextInput::make('code')
                ->label('驗證碼')
                ->placeholder('請輸入 6 位數驗證碼')
                ->helperText(new HtmlString('請輸入 <strong>Google Authenticator</strong> 顯示的 6 位數驗證碼'))
                ->length(6)
                ->numeric()
                ->required()
                ->autocomplete('one-time-code')
                ->autofocus()
                ->rules([
                    function () use ($user) {
                        return function (string $attribute, $value, $fail) use ($user) {
                            if (!($user instanceof User)) {
                                $fail('驗證失敗');
                                return;
                            }

                            if (empty($value)) {
                                $fail('請輸入驗證碼');
                                return;
                            }

                            // 解密 secret
                            $secret = decrypt($user->two_factor_secret);

                            // 使用 Google2FA 驗證
                            $google2fa = new Google2FA();

                            if (!$google2fa->verifyKey($secret, $value)) {
                                $fail('驗證碼錯誤，請重新輸入');
                            }
                        };
                    },
                ]),
        ];
    }

    /**
     * 取得管理界面的組件（用於用戶自己管理 2FA）
     *
     * 由於我們已經有獨立的 SecuritySettings 頁面，這裡返回空陣列
     *
     * @return array<Component>
     */
    public function getManagementSchemaComponents(): array
    {
        return [];
    }
}
