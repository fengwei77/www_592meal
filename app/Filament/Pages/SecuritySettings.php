<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Actions as FormActions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithHeaderActions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

/**
 * 安全設定頁面 (個人)
 *
 * 允許店家管理自己的 2FA 設定
 * 注意：IP 白名單由 Super Admin 在店家管理中設定
 */
class SecuritySettings extends Page
{
    use InteractsWithForms;
    use InteractsWithHeaderActions;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected string $view = 'filament.pages.security-settings';

    protected static ?string $navigationLabel = '安全設定';

    protected static ?string $title = '安全設定';

    protected static ?int $navigationSort = 99;

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();

        $this->form->fill([
            'two_factor_code' => '',
        ]);
    }

    /**
     * 確認 2FA 驗證碼
     */
    public function confirmTwoFactorCode(): void
    {
        $user = Auth::user();
        $data = $this->form->getState();
        $code = $data['two_factor_code'] ?? '';

        if (empty($code)) {
            Notification::make()
                ->title('請輸入驗證碼')
                ->danger()
                ->send();
            return;
        }

        $google2fa = new Google2FA();
        $secret = decrypt($user->two_factor_secret);

        // 驗證碼檢查
        $valid = $google2fa->verifyKey($secret, $code);

        if ($valid) {
            $user->confirmTwoFactor();

            Notification::make()
                ->title('2FA 確認成功')
                ->body('您的雙因素認證已成功設定')
                ->success()
                ->send();

            $this->redirect(static::getUrl());
        } else {
            Notification::make()
                ->title('驗證碼錯誤')
                ->body('請檢查驗證碼是否正確')
                ->danger()
                ->send();
        }
    }

    public function form(Schema $schema): Schema
    {
        $user = Auth::user();

        return $schema
            ->schema([
                Section::make('雙因素認證 (2FA)')
                    ->description('使用 Google Authenticator 提升帳號安全性')
                    ->schema([
                        Placeholder::make('two_factor_status')
                            ->label('狀態')
                            ->content(function () use ($user) {
                                if (!$user->two_factor_enabled) {
                                    return '❌ 未啟用 (管理員已停用此功能)';
                                }

                                if ($user->two_factor_confirmed_at) {
                                    return '✅ 已確認 ('. $user->two_factor_confirmed_at->format('Y-m-d H:i') .')';
                                }

                                if ($user->two_factor_secret) {
                                    return '⚠️ 已生成密鑰，請掃描 QR Code 並確認';
                                }

                                return '📱 尚未設定';
                            }),

                        ViewField::make('two_factor_qr_code')
                            ->label('掃描 QR Code')
                            ->view('filament.forms.components.two-factor-qr-code')
                            ->visible(fn () => $user->two_factor_enabled && $user->two_factor_secret && !$user->two_factor_confirmed_at),

                        TextInput::make('two_factor_code')
                            ->label('驗證碼')
                            ->placeholder('請輸入 6 位數驗證碼')
                            ->helperText('請輸入 Google Authenticator 顯示的 6 位數驗證碼，然後點擊右上角的「確認 2FA」按鈕')
                            ->length(6)
                            ->numeric()
                            ->visible(fn () => $user->two_factor_enabled && $user->two_factor_secret && !$user->two_factor_confirmed_at),

                        Placeholder::make('two_factor_confirmed_notice')
                            ->label('')
                            ->content('✅ 您的 2FA 已成功設定並確認')
                            ->visible(fn () => $user->two_factor_enabled && $user->two_factor_confirmed_at),

                        Placeholder::make('two_factor_disabled_notice')
                            ->label('')
                            ->content('⚠️ 管理員已停用 2FA 功能')
                            ->visible(fn () => !$user->two_factor_enabled),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        $user = Auth::user();
        $actions = [];

        // 如果管理員已停用 2FA 功能，不顯示任何按鈕
        if (!$user->two_factor_enabled) {
            return $actions;
        }

        // 情況 1: 尚未設定 secret - 顯示「啟用我的 2FA」
        if (!$user->two_factor_secret) {
            $actions[] = Action::make('enable2FA')
                ->label('啟用我的 2FA')
                ->color('primary')
                ->icon('heroicon-o-shield-check')
                ->action(function () {
                    $user = Auth::user();
                    $google2fa = new Google2FA();

                    // 生成 secret
                    $secret = $google2fa->generateSecretKey();
                    $user->two_factor_secret = encrypt($secret);
                    $user->save();

                    Notification::make()
                        ->title('已生成 2FA 密鑰')
                        ->body('請掃描下方 QR Code 並輸入驗證碼確認')
                        ->success()
                        ->send();

                    $this->redirect(static::getUrl());
                });
        }
        // 情況 2: 已生成 secret 但未確認 - 顯示「確認 2FA」和「取消設定」
        elseif (!$user->two_factor_confirmed_at) {
            // 確認按鈕
            $actions[] = Action::make('confirm2FA')
                ->label('確認 2FA')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->action(fn () => $this->confirmTwoFactorCode());

            // 取消按鈕
            $actions[] = Action::make('cancel2FA')
                ->label('取消設定')
                ->color('gray')
                ->icon('heroicon-o-x-mark')
                ->requiresConfirmation()
                ->modalHeading('取消 2FA 設定')
                ->modalDescription('確定要取消 2FA 設定嗎？您需要重新開始設定流程。')
                ->action(function () {
                    $user = Auth::user();

                    // 清除 secret（但保留 two_factor_enabled = true）
                    $user->two_factor_secret = null;
                    $user->save();

                    Notification::make()
                        ->title('已取消 2FA 設定')
                        ->body('您可以隨時重新啟用')
                        ->warning()
                        ->send();

                    $this->redirect(static::getUrl());
                });
        }
        // 情況 3: 已確認 - 顯示「停用我的 2FA」
        else {
            $actions[] = Action::make('disable2FA')
                ->label('停用我的 2FA')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalHeading('停用雙因素認證')
                ->modalDescription('您確定要停用雙因素認證嗎？這會降低帳號安全性。')
                ->action(function () {
                    $user = Auth::user();

                    // 清除所有 2FA 相關資料（但保留 two_factor_enabled = true）
                    $user->two_factor_secret = null;
                    $user->two_factor_recovery_codes = null;
                    $user->two_factor_confirmed_at = null;
                    $user->two_factor_temp_disabled_at = null;
                    $user->save();

                    Notification::make()
                        ->title('2FA 已停用')
                        ->body('您可以隨時重新啟用')
                        ->warning()
                        ->send();

                    $this->redirect(static::getUrl());
                });
        }

        return $actions;
    }

    /**
     * 所有已登入的 User 都可以訪問此頁面
     */
    public static function canAccess(): bool
    {
        return Auth::check();
    }
}
