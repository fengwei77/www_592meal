<?php

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Auth\Pages\PasswordReset\ResetPassword as BaseResetPassword;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class ResetPassword extends BaseResetPassword
{
    // 使用父類別的 mount 和 resetPassword 方法

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
                    ->autocomplete('email')
                    ->disabled(),

                TextInput::make('password')
                    ->label('新密碼')
                    ->required()
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->extraInputAttributes(['tabindex' => 2])
                    ->autocomplete('new-password')
                    ->same('passwordConfirmation')
                    ->validationAttribute('新密碼'),

                TextInput::make('passwordConfirmation')
                    ->label('確認新密碼')
                    ->required()
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->extraInputAttributes(['tabindex' => 3])
                    ->autocomplete('new-password')
                    ->dehydrated(false)
                    ->validationAttribute('確認新密碼'),
            ]);
    }

    public function getHeading(): string
    {
        return '重設密碼';
    }

    public function getSubheading(): ?string
    {
        return '請輸入您的新密碼';
    }
}