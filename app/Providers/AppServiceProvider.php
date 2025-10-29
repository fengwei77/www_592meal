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
}
