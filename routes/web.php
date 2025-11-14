<?php

use App\Http\Controllers\Auth\LineLoginController;
use App\Livewire\StoreOnboarding;
use App\Livewire\SystemStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Livewire\Auth\EmailVerificationForm;
use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\Frontend\MenuController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\OrderController;
use App\Http\Controllers\Frontend\StoreController;
use App\Http\Controllers\Store\OrderManagementController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Test\EcpayTestController;

/**
 * 全域驗證碼路由 - 所有域名都可訪問
 */
Route::get('/api/captcha', [CaptchaController::class, 'generate'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])->name('captcha.generate');
Route::post('/api/captcha/verify', [CaptchaController::class, 'verify'])->name('captcha.verify');

/**
 * 全域購物車路由 - 所有域名都可訪問
 */
Route::get('/cart', [CartController::class, 'index'])->name('frontend.cart.index');
Route::post('/cart/update', [CartController::class, 'update'])->middleware('prevent.duplicate')->name('frontend.cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->middleware('prevent.duplicate')->name('frontend.cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->middleware('prevent.duplicate')->name('frontend.cart.clear');

/**
 * LINE Login 路由 - 所有域名都可訪問
 */
Route::prefix('auth/line')->middleware('throttle:10,1')->group(function () {
    Route::get('/login', [LineLoginController::class, 'redirect'])->name('line.login');
    Route::get('/callback', [LineLoginController::class, 'callback'])->name('line.callback');
    Route::match(['get', 'post'], '/logout', [LineLoginController::class, 'logout'])->name('line.logout');
    Route::get('/check', [LineLoginController::class, 'check'])->name('line.check');
});

/**
 * 全域測試路由 - 所有域名都可訪問
 */

// ECPay 付款結果測試頁面
Route::get('/test/ecpay-payment', function () {
    return view('test.ecpay-payment-test');
})->name('test.ecpay.payment');

// 設定付款測試 session
Route::post('/test/set-payment-session', [EcpayTestController::class, 'setPaymentSession'])
    ->name('test.set.payment.session');

// LINE Session 診斷頁面
Route::get('/debug/line-session', function () {
    return view('debug.line-session-debug');
})->name('debug.line.session');

// 測試 LINE 回調 URL
Route::get('/debug/line-callback-test', function () {
    $customer = auth('customer')->user();
    $isBlocked = false;
    $blockInfo = null;

    if ($customer) {
        $isBlocked = \App\Models\StoreCustomerBlock::isPlatformBlocked($customer->id);
        if ($isBlocked) {
            $blocks = \App\Models\StoreCustomerBlock::where('customer_id', $customer->id)->get();
            $blockInfo = $blocks->map(function($block) {
                return [
                    'store_id' => $block->store_id,
                    'store_name' => $block->store->name ?? null,
                    'reason' => $block->reason,
                    'created_at' => $block->created_at,
                ];
            });
        }
    }

    return response()->json([
        'current_config' => [
            'LINE_LOGIN_CALLBACK_URL' => config('line.callback_url'),
            'APP_URL' => config('app.url'),
            'current_url' => url()->current(),
            'full_url' => url('/auth/line/callback'),
        ],
        'routes' => [
            'line_callback_route' => route('line.callback'),
            'line_login_route' => route('line.login'),
        ],
        'customer_info' => [
            'authenticated' => auth('customer')->check(),
            'customer_id' => $customer ? $customer->id : null,
            'customer_name' => $customer ? $customer->name : null,
            'line_id' => $customer ? $customer->line_id : null,
        ],
        'platform_block' => [
            'is_blocked' => $isBlocked,
            'block_info' => $blockInfo,
        ],
        'session_test' => [
            'session_id' => session()->getId(),
            'line_login_state' => session('line_login_state'),
            'line_login_nonce' => session('line_login_nonce'),
            'line_logged_in' => session('line_logged_in'),
            'line_user' => session('line_user'),
        ]
    ]);
})->name('debug.line.callback.test');

// 清除付款測試 session
Route::post('/test/clear-payment-session', [EcpayTestController::class, 'clearPaymentSession'])
    ->name('test.clear.payment.session');

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

    // Resend Verification Email Route
    Route::get('/resend-verification', function () {
        return view('auth.resend-verification');
    })->name('verification.resend');

    // Email Verification Routes (Story 1.3)
    Route::get('/email/verify', EmailVerificationForm::class)->name('verification.notice');
    Route::post('/email/verify', [EmailVerificationController::class, 'verify'])->middleware(['throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware(['throttle:6,1'])->name('verification.send');

    Route::get('/system-status', SystemStatus::class)->name('system-status');

    Route::get('/test-captcha', function () {
        return view('test_captcha');
    })->name('captcha.test');

    // 防重複提交測試路由
    Route::get('/test/duplicate-protection', function () {
        return view('test.duplicate-protection');
    })->name('test.duplicate.protection');

    Route::post('/test/duplicate-submit', function (\Illuminate\Http\Request $request) {
        // 模擬處理時間
        sleep(2);

        return response()->json([
            'success' => true,
            'message' => '表單提交成功！',
            'data' => $request->all(),
            'timestamp' => now()->toDateTimeString()
        ]);
    })->middleware('prevent.duplicate')->name('test.duplicate.submit');

    // 訂單提交測試頁面
    Route::get('/test/order-submit', function () {
        return view('test.order-submit');
    })->name('test.order.submit');

    // 調試路由 - 檢查登入狀態
    Route::get('/debug/auth', function () {
        return view('debug.auth-status');
    })->name('debug.auth');

    // 調試路由 - 檢查首頁認證
    Route::get('/debug/home-auth', function () {
        return view('debug.home-auth');
    })->name('debug.home.auth');

    // LINE Login routes (with rate limiting to prevent abuse) - 已移至全域路由，避免衝突

    // Logout route (顧客登出) - 支持 GET 和 POST 方法
    Route::match(['get', 'post'], '/logout', function () {
        Auth::guard('customer')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/')->with('success', '登出成功！');
    })->middleware('auth:customer')->name('logout');

    // 店家清單相關路由 (前台)
    Route::get('/stores', [StoreController::class, 'index'])->name('frontend.stores.index');
    Route::get('/stores/search', [StoreController::class, 'index'])->name('frontend.stores.search');

    // 關於我們頁面
    Route::get('/about', function () {
        return view('frontend.about');
    })->name('frontend.about');

    // 聯絡我們頁面
    Route::get('/contact', [ContactController::class, 'index'])->name('frontend.contact');
    Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');

  
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

    // 測試路由
    Route::get('/test-cart', function() {
        return 'Cart route works!';
    });

    // 訂單歷史相關路由
    Route::get('/my-orders', [OrderController::class, 'index'])->name('frontend.order.index');
    Route::get('/order/{orderNumber}', [OrderController::class, 'show'])->name('frontend.order.show');
    Route::post('/order/{orderNumber}/cancel', [OrderController::class, 'cancel'])->name('frontend.order.cancel');

    // 平台封鎖頁面
    Route::get('/platform-blocked', function () {
        return view('frontend.platform-blocked');
    })->name('platform.blocked');

    // 顧客通知設定相關路由（需要 LINE 登入）
    Route::prefix('customer/notifications')->group(function () {
        Route::get('/settings', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'index'])
            ->name('customer.notifications.settings');
        Route::post('/preferences', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'updatePreferences'])
            ->name('customer.notifications.preferences');
        Route::delete('/subscriptions/{id}', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'removeSubscription'])
            ->name('customer.notifications.subscriptions.remove');
        Route::post('/subscriptions/remove-all', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'removeAllSubscriptions'])
            ->name('customer.notifications.subscriptions.remove-all');
        Route::post('/test', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'sendTestNotification'])
            ->name('customer.notifications.test');
        Route::post('/check-subscription', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'checkSubscription'])
            ->name('customer.notifications.check-subscription');
        Route::post('/cleanup', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'cleanupExpiredSubscriptions'])
            ->name('customer.notifications.cleanup');
        Route::post('/simulate-subscription', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'simulateSubscription'])
            ->name('customer.notifications.simulate-subscription');
        Route::get('/debug', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'debug'])
            ->name('customer.notifications.debug');
    });

    // 購物車相關路由 (店家特定)
    Route::prefix('store/{store_slug}/cart')->group(function () {
        Route::post('/add', [CartController::class, 'add'])->middleware('prevent.duplicate')->name('frontend.cart.add.store');
        Route::post('/update', [CartController::class, 'updateForStore'])->middleware('prevent.duplicate')->name('frontend.cart.update.store');
        Route::post('/remove', [CartController::class, 'removeFromStore'])->middleware('prevent.duplicate')->name('frontend.cart.remove.store');
        Route::get('/', [CartController::class, 'storeCartIndex'])->name('frontend.cart.index.store');
    });

    // 結帳相關路由 (店家特定)
    Route::prefix('store/{store_slug}/checkout')->group(function () {
        Route::get('/', [OrderController::class, 'create'])->name('frontend.order.create');
        Route::post('/', [OrderController::class, 'store'])->middleware('prevent.duplicate')->name('frontend.order.store');
        Route::get('/confirmed/{order}', [OrderController::class, 'confirmed'])->name('frontend.order.confirmed');
    });

    // 注意：店家訂單管理路由已移至後台域名（admin.php）
    // 請訪問 https://cms.oh592meal.test/store/{store_slug}/manage/orders

    // 店家頁面路由 (slug-based) - 放在最後以避免與其他路由衝突
    Route::get('/store/{store_slug}', [StoreController::class, 'storeDetail'])
         ->where('store_slug', '[a-zA-Z0-9-]+')
         ->name('frontend.store.detail');
});

/**
 * 店家子域名路由群組
 *
 * 注意：這些路由目前停用，因為系統改用統一的 /store/{slug} 格式
 * 保留註解以備將來需要子域名功能時使用
 */
/*
Route::domain('{store_subdomain}.' . parse_url(config('app.url'), PHP_URL_HOST))
    ->middleware(['store.tenant'])
    ->group(function () {

        // 店家首頁 - 顯示店家菜單
        Route::get('/', [MenuController::class, 'index'])->name('frontend.menu.index');

        // 菜單分類頁面
        Route::get('/menu/{category:slug}', [MenuController::class, 'category'])->name('frontend.menu.category');

        // 購物車相關路由 (停用以避免路由名稱衝突)
        // Route::post('/cart/add', [CartController::class, 'add'])->name('subdomain.cart.add');
        // Route::post('/cart/update', [CartController::class, 'update'])->name('subdomain.cart.update');
        // Route::post('/cart/remove', [CartController::class, 'remove'])->name('subdomain.cart.remove');
        // Route::get('/cart', [CartController::class, 'index'])->name('subdomain.cart.index');

        // 結帳相關路由 (停用以避免路由名稱衝突)
        // Route::get('/checkout', [OrderController::class, 'create'])->name('subdomain.order.create');
        // Route::post('/checkout', [OrderController::class, 'store'])->name('subdomain.order.store');
        // Route::get('/order/confirmed/{order}', [OrderController::class, 'confirmed'])->name('subdomain.order.confirmed');
    });
*/

// 載入訂閱系統路由
require __DIR__.'/subscription.php';
