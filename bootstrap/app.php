<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->domain(parse_url(config('app.admin_url'), PHP_URL_HOST))
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 在測試環境中不檢查網域，避免干擾測試
        // 直接檢查環境變數，避免過早使用 app() 容器
        $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'production';

        if ($env !== 'testing') {
            $middleware->web(append: [
                \App\Http\Middleware\CheckAdminDomain::class,
                \App\Http\Middleware\CheckPlatformBlock::class,
            ]);
        }

        // 註冊店家租戶中間件別名
        $middleware->alias([
            'store.tenant' => \App\Http\Middleware\StoreTenantMiddleware::class,
            'store.access' => \App\Http\Middleware\VerifyStoreAccess::class,
            'staff.auth' => \App\Http\Middleware\StaffAuthenticate::class,
            'prevent.duplicate' => \App\Http\Middleware\PreventDuplicateSubmission::class,
            'platform.block.check' => \App\Http\Middleware\CheckPlatformBlock::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // 每小時檢查並恢復超過 24 小時的臨時關閉 2FA
        $schedule->command('two-factor:restore-expired')->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
