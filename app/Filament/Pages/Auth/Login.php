<?php

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
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
                    ->autocomplete('email'),

                TextInput::make('password')
                    ->label('密碼')
                    ->required()
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->extraInputAttributes(['tabindex' => 2])
                    ->autocomplete('current-password'),

                Checkbox::make('remember')
                    ->label('記住我')
                    ->extraInputAttributes(['tabindex' => 3]),
            ]);
    }

    protected function getForgotPasswordHint(): ?\Illuminate\Support\HtmlString
    {
        // 直接返回忘記密碼連結，不檢查 hasPasswordReset
        $resetUrl = filament()->getRequestPasswordResetUrl();

        if (empty($resetUrl)) {
            return null;
        }

        return new \Illuminate\Support\HtmlString(
            '<a href="' . e($resetUrl) . '" class="text-primary-600 hover:text-primary-500 text-sm font-medium" tabindex="4">忘記密碼？</a>'
        );
    }

    public function authenticate(): ?\Filament\Auth\Http\Responses\Contracts\LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        // 直接調用父類別的 authenticate 方法，不進行驗證碼檢查
        return parent::authenticate();
    }

    public function getHeading(): string
    {
        return '登入';
    }

    public function getSubheading(): ?string
    {
        return '請輸入您的憑證以存取系統';
    }
}