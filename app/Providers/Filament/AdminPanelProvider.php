<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Google2FAProvider;
use App\Http\Middleware\CheckIpWhitelist;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
// use Filament\Pages\Dashboard; // 移除主控台
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use App\Filament\Pages\Dashboard;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->domain(parse_url(config('app.admin_url'), PHP_URL_HOST)) // 限制只在後台域名響應
            ->path('/') // 後台網域的根路徑，不使用 /admin
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->passwordReset(
                requestAction: \App\Filament\Pages\Auth\RequestPasswordReset::class,
                resetAction: \App\Filament\Pages\Auth\ResetPassword::class
            )
            ->colors([
                'primary' => Color::Amber,
            ])
            ->multiFactorAuthentication([
                new Google2FAProvider(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->pages([
                Dashboard::class,
                // 手動列出所有頁面，避免自動發現已刪除的 StoreSettings
                \App\Filament\Pages\EditProfile::class,
                \App\Filament\Pages\SecuritySettings::class,
                \App\Filament\Pages\ManageSubscription::class,
                \App\Filament\Pages\SystemManagement::class,
                \App\Filament\Pages\SubscriptionManagement::class,
                \App\Filament\Pages\OrderManagement::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                \App\Filament\Widgets\CustomAccountWidget::class,
               // FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                // \App\Http\Middleware\EnsureEmailIsVerified::class, // Story 1.3: Check for email verification
                // CheckIpWhitelist::class, // IP 白名單檢查
            ]);
    }
}
