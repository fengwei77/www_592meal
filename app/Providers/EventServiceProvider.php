<?php

namespace App\Providers;

use App\Listeners\LogAdminLogin;
use App\Listeners\LogWebsiteLogin;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Event Service Provider
 *
 * 註冊應用程式的事件監聽器
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // 登入事件監聽
        Login::class => [
            LogWebsiteLogin::class,
            LogAdminLogin::class,
        ],

        Failed::class => [
            LogWebsiteLogin::class,
            LogAdminLogin::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}