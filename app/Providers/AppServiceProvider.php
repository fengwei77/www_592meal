<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use App\Models\Store;
use App\Policies\StorePolicy;
use App\Observers\StoreObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 註冊自定義登出響應
        $this->app->singleton(
            \Filament\Auth\Http\Responses\LogoutResponse::class,
            \App\Filament\Auth\Http\Responses\LogoutResponse::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 註冊 Store Policy
        Gate::policy(Store::class, StorePolicy::class);

        // 註冊 Store Observer
        Store::observe(StoreObserver::class);

        // 定義 Filament 權限 Gates
        $this->defineFilamentGates();

        // 添加 Filament 需要的登入路由別名
        $this->app->booted(function () {
            if (!Route::has('filament.admin.auth.login')) {
                Route::domain(parse_url(config('app.admin_url'), PHP_URL_HOST))
                    ->get('/filament-login', function () {
                        return redirect('/login');
                    })
                    ->name('filament.admin.auth.login');
            }
        });
    }

    /**
     * 定義 Filament 權限 Gates
     */
    private function defineFilamentGates(): void
    {
        // 定義訪問管理面板的權限
        Gate::define('access-admin-panel', function ($user) {
            return $user && $user->hasRole(['super_admin', 'store_owner']);
        });

        // 定義管理員權限 Gates
        Gate::define('manage-stores', function ($user) {
            return $user && $user->hasPermissionTo('manage-stores');
        });

        Gate::define('manage-users', function ($user) {
            return $user && $user->hasPermissionTo('manage-users');
        });

        Gate::define('manage-orders', function ($user) {
            return $user && $user->hasPermissionTo('manage-orders');
        });

        Gate::define('manage-menu-items', function ($user) {
            return $user && $user->hasPermissionTo('manage-menu-items');
        });

        Gate::define('view-reports', function ($user) {
            return $user && $user->hasPermissionTo('view-reports');
        });

        Gate::define('view-dashboard', function ($user) {
            return $user && $user->hasPermissionTo('view-dashboard');
        });

        // 超級管理員可以訪問所有功能
        Gate::before(function ($user, $ability) {
            if ($user && $user->hasRole('super_admin')) {
                return true;
            }
        });
    }
}
