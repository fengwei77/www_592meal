<?php

use App\Http\Controllers\Auth\LineLoginController;
use App\Livewire\StoreOnboarding;
use App\Livewire\SystemStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Livewire\Auth\EmailVerificationForm;

/**
 * 前台路由群組
 *
 * 這些路由只在前台網域 (oh592meal.test) 上響應
 * 後台網域 (cms.oh592meal.test) 不會匹配這些路由
 */
Route::domain(parse_url(config('app.url'), PHP_URL_HOST))->group(function () {
    Route::get('/', function () {
        return view('home');
    })->name('home');

    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    // Merchant Registration Route (Story 1.2)
    Route::get('/merchant-register', function () {
        return view('auth.merchant-register');
    })->name('merchant.register');

    // Email Verification Routes (Story 1.3)
    Route::get('/email/verify', EmailVerificationForm::class)->name('verification.notice');
    Route::post('/email/verify', [EmailVerificationController::class, 'verify'])->middleware(['throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware(['throttle:6,1'])->name('verification.send');

    Route::get('/system-status', SystemStatus::class)->name('system-status');

    // LINE Login routes (with rate limiting to prevent abuse)
    Route::middleware('throttle:10,1')->group(function () {
        Route::get('/auth/line', [LineLoginController::class, 'redirect'])->name('auth.line');
        Route::get('/auth/line/callback', [LineLoginController::class, 'callback'])->name('auth.line.callback');
    });

    // Logout route (顧客登出)
    Route::post('/logout', function () {
        Auth::guard('customer')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/')->with('success', '登出成功！');
    })->middleware('auth:customer')->name('logout');



    Route::middleware(['auth'])->group(function () {
        // 店家儀表板（需要登入）
        Route::get('/dashboard', function () {
            return view('dashboard.welcome');
        })->name('dashboard');

        // 舊的 onboarding 路由（保留向下相容，重定向到新路由）
        Route::get('/onboarding', function () {
            return redirect()->route('store.register');
        })->name('onboarding');
    });
});