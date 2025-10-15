<?php

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->label('電子郵件')
                    ->required()
                    ->email()
                    ->autofocus()
                    ->extraInputAttributes(['tabindex' => 1])
                    ->helperText('請輸入您註冊時使用的電子郵件地址'),
                
                \Filament\Forms\Components\Placeholder::make('captcha_label')
                    ->label('驗證碼')
                    ->content('請輸入下方顯示的 6 碼驗證碼'),
                
                \Filament\Forms\Components\TextInput::make('captcha')
                    ->label('驗證碼')
                    ->required()
                    ->extraInputAttributes(['tabindex' => 2])
                    ->helperText('請輸入上方圖片中的字元，不區分大小寫'),
                    
                \Filament\Forms\Components\Placeholder::make('captcha_image')
                    ->label('')
                    ->content(function () {
                        return '<div style="margin: 10px 0;">' . captcha_img() . '</div>';
                    })
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

        // 驗證 Captcha
        if (!$this->validateCaptcha($data['captcha'] ?? '')) {
            throw ValidationException::withMessages([
                'captcha' => '驗證碼錯誤，請重新輸入。',
            ]);
        }

        $status = Password::broker('admins')->sendResetLink([
            'email' => $data['email'],
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->getNotification()?->success(__($status), sent: true);

        $this->form->fill();
    }

    private function validateCaptcha(string $captcha): bool
    {
        return captcha_check($captcha);
    }

    public function getHeading(): string
    {
        return '重設密碼';
    }

    public function getSubheading(): ?string
    {
        return '請輸入您的電子郵件地址，我們將發送密碼重設連結給您';
    }
}