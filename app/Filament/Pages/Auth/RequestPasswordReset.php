<?php

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    public ?string $captchaCode = null;

    public function mount(): void
    {
        parent::mount();

        // 只有在 session 中沒有驗證碼時才生成新的
        if (!session()->has('password_reset_captcha')) {
            $this->generateCaptcha();
        } else {
            // 從 session 讀取現有的驗證碼
            $this->captchaCode = session('password_reset_captcha');
        }
    }

    public function generateCaptcha(): void
    {
        $this->captchaCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        session()->put('password_reset_captcha', $this->captchaCode);
    }

    public function refreshCaptcha(): void
    {
        $this->generateCaptcha();
        // 清空驗證碼輸入框
        $this->form->fill(['verification_code' => '']);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('email')
                ->label('電子郵件')
                ->required()
                ->email()
                ->autofocus()
                ->extraInputAttributes(['tabindex' => 1])
                ->autocomplete('email')
                ->helperText('請輸入您註冊時使用的電子郵件地址'),

            ViewField::make('captcha_display')
                ->label('驗證碼')
                ->view('components.captcha-display')
                ->viewData(fn() => [
                    'code' => $this->captchaCode ?? session('password_reset_captcha', '000000'),
                ]),

            TextInput::make('verification_code')
                ->label('請輸入上方驗證碼')
                ->required()
                ->length(6)
                ->numeric()
                ->extraInputAttributes(['tabindex' => 2])
                ->helperText('請輸入上方圖片中顯示的 6 位數驗證碼'),

            \Filament\Forms\Components\Placeholder::make('security_notice')
                ->label('')
                ->content(fn() => new \Illuminate\Support\HtmlString(
                    '<div style="background-color: #fef3c7; border: 1px solid #f59e0b; border-radius: 6px; padding: 12px; margin: 16px 0;">
                        <div style="display: flex; align-items: center; margin-bottom: 8px;">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="color: #d97706;">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <strong style="margin-left: 8px; color: #92400e;">安全提示</strong>
                        </div>
                        <p style="margin: 0; color: #92400e; font-size: 14px; line-height: 1.5;">
                            驗證成功後，系統會發送密碼重設連結到您的信箱，連結將在 60 分鐘內有效。
                        </p>
                    </div>'
                ))
                ->html(),
        ]);
    }

    public function request(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            throw ValidationException::withMessages([
                'email' => __('filament::pages/auth/password-reset/request-password-reset.messages.throttled', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]),
            ]);
        }

        $data = $this->form->getState();
        $email = $data['email'];
        $code = trim((string) ($data['verification_code'] ?? ''));

        // 驗證圖形驗證碼
        $sessionCaptcha = trim((string) session('password_reset_captcha'));

        // 除錯日誌
        \Log::info('Password Reset Captcha Check', [
            'input_code' => $code,
            'input_type' => gettype($code),
            'session_code' => $sessionCaptcha,
            'session_type' => gettype($sessionCaptcha),
            'match' => $code === $sessionCaptcha,
        ]);

        if (empty($code) || $code !== $sessionCaptcha) {
            $this->generateCaptcha();

            throw ValidationException::withMessages([
                'data.verification_code' => '驗證碼錯誤，請重新輸入。(輸入: ' . $code . ', 正確: ' . $sessionCaptcha . ')',
            ]);
        }

        // 清除驗證碼
        session()->forget('password_reset_captcha');

        // 驗證用戶是否存在
        $user = \App\Models\User::where('email', $email)->first();
        if (!$user) {
            $this->generateCaptcha();

            throw ValidationException::withMessages([
                'data.email' => '找不到使用此電子郵件的帳號，請確認輸入正確的信箱地址。',
            ]);
        }

        // 驗證用戶帳號狀態
        if (!$user->hasVerifiedEmail()) {
            $this->generateCaptcha();

            throw ValidationException::withMessages([
                'data.email' => '此帳號尚未驗證電子郵件，請先驗證信箱後再重設密碼。',
            ]);
        }

        // 發送密碼重設連結
        $status = Password::broker(config('auth.defaults.passwords'))->sendResetLink([
            'email' => $email,
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            $this->generateCaptcha();

            throw ValidationException::withMessages([
                'data.email' => __($status),
            ]);
        }

        \Filament\Notifications\Notification::make()
            ->title('密碼重設連結已發送')
            ->body('密碼重設連結已發送到您的信箱，請於 60 分鐘內完成密碼重設。')
            ->success()
            ->send();

        $this->form->fill();
        $this->generateCaptcha();
    }

    public function getHeading(): string
    {
        return '重設密碼';
    }

    public function getSubheading(): ?string
    {
        return '請輸入您的電子郵件地址和驗證碼';
    }
}
