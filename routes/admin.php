<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Store\OrderManagementController;
use App\Http\Controllers\Store\StaffAuthController;

// Admin Routes - 目前不需要額外路由，全部使用 Filament 系統
// 所有認證、登入、登出都由 Filament 處理

// 店員登入路由（無需認證）
Route::prefix('store/{storeSlug}')
    ->middleware(['web'])
    ->group(function () {
        // 顯示店員登入頁面
        Route::get('/staff/login', [StaffAuthController::class, 'showLoginForm'])
            ->name('store.staff.login');

        // 處理店員登入
        Route::post('/staff/login', [StaffAuthController::class, 'login'])
            ->name('store.staff.login.submit');

        // 店員登出
        Route::post('/staff/logout', [StaffAuthController::class, 'logout'])
            ->name('store.staff.logout');
    });

// 店家訂單管理路由（後台域名專用）
// 需要登入後台並擁有店家權限，或使用店員密碼登入
Route::prefix('store/{store_slug}/manage')
    ->middleware(['web', 'store.access'])
    ->group(function () {
        // 訂單管理主頁面
        Route::get('/orders', [OrderManagementController::class, 'index'])
            ->name('store.orders.index');

        // 訂單狀態更新 API
        Route::post('/orders/{orderNumber}/confirm', [OrderManagementController::class, 'confirm'])
            ->name('store.orders.confirm');

        Route::post('/orders/{orderNumber}/reject', [OrderManagementController::class, 'reject'])
            ->name('store.orders.reject');

        Route::post('/orders/{orderNumber}/ready', [OrderManagementController::class, 'markReady'])
            ->name('store.orders.ready');

        Route::post('/orders/{orderNumber}/complete', [OrderManagementController::class, 'complete'])
            ->name('store.orders.complete');

        Route::post('/orders/{orderNumber}/abandon', [OrderManagementController::class, 'abandon'])
            ->name('store.orders.abandon');

        // 訂單統計
        Route::get('/stats', [OrderManagementController::class, 'getStats'])
            ->name('store.orders.stats');

        // 檢查新訂單（輪詢用）
        Route::get('/orders/check-new', [OrderManagementController::class, 'checkNewOrders'])
            ->name('store.orders.check-new');
    });
