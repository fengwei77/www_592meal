<?php

use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\EcpayController;
use App\Http\Controllers\AdminSubscriptionController;
use App\Http\Controllers\EcpayTestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 訂閱系統路由
|--------------------------------------------------------------------------
*/

// 老闆訂閱相關路由
Route::middleware(['auth'])->prefix('subscription')->name('subscription.')->group(function () {
    Route::get('/', [SubscriptionController::class, 'index'])->name('index');
    Route::get('/renew', [SubscriptionController::class, 'renew'])->name('renew');
    Route::post('/order', [SubscriptionController::class, 'createOrder'])->name('createOrder');
    Route::get('/order-created/{order}', [SubscriptionController::class, 'orderCreated'])->name('order-created');
    Route::get('/payment-confirm/{order}', [SubscriptionController::class, 'paymentConfirm'])->name('payment-confirm');
    Route::get('/confirm/{order}', [SubscriptionController::class, 'confirm'])->name('confirm');
    Route::post('/pay/{order}', [SubscriptionController::class, 'pay'])->name('pay');
    Route::get('/history', [SubscriptionController::class, 'history'])->name('history');
    Route::get('/show/{order}', [SubscriptionController::class, 'showOrder'])->name('show');
    Route::get('/repay/{order}', [SubscriptionController::class, 'repay'])->name('repay');
    Route::post('/cancel/{order}', [SubscriptionController::class, 'cancel'])->name('cancel');
    Route::get('/status', [SubscriptionController::class, 'getStatus'])->name('status');
});

// 綠界金流回傳路由 (無需認證)
Route::middleware(['web'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->prefix('ecpay')->name('ecpay.')
    ->group(function () {
    Route::post('/return', [EcpayController::class, 'returnUrl'])->name('return');
    Route::post('/payment-info', [EcpayController::class, 'paymentInfo'])->name('paymentInfo');
    Route::get('/payment-result', function (\Illuminate\Http\Request $request) {
    // 簡化版處理函數，直接顯示視圖
    $paymentResult = session('payment_result') ?: session('ecpay_payment_result');

    if (!$paymentResult) {
        // 如果沒有 session 資料，檢查 URL 參數
        $orderNumber = $request->input('order_number');
        $success = $request->input('success', false);
        $message = $request->input('message', '');

        if (!$orderNumber) {
            return view('ecpay.payment-result', [
                'success' => false,
                'message' => '找不到付款結果資訊',
                'orderNumber' => null,
                'order' => null,
            ]);
        }

        return view('ecpay.payment-result', [
            'success' => (bool)$success,
            'message' => $message,
            'orderNumber' => $orderNumber,
            'order' => null,
        ]);
    }

    // 從 session 取得資料
    $success = $paymentResult['success'];
    $message = $paymentResult['message'];
    $order = $paymentResult['order'] ?? null;
    $orderNumber = $paymentResult['order_number'] ?? ($order ? $order->order_number : null);

    // 清除 session 中的付款結果
    session()->forget('payment_result');
    session()->forget('ecpay_payment_result');

    return view('ecpay.payment-result', [
        'success' => $success,
        'message' => $message,
        'orderNumber' => $orderNumber,
        'order' => $order,
    ]);
})->name('payment.result');
    Route::get('/client-return', [EcpayController::class, 'clientReturn'])->name('clientReturn');

    // 測試路由 (僅開發環境)
    if (app()->environment('local', 'testing')) {
        Route::post('/test-return', [EcpayController::class, 'testReturn'])->name('testReturn');
        Route::post('/test-payment-info', [EcpayController::class, 'testPaymentInfo'])->name('testPaymentInfo');
    }
});

// 管理員訂閱管理路由
Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/subscriptions', [AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('/subscriptions/{user}', [AdminSubscriptionController::class, 'show'])->name('subscriptions.show');
    Route::post('/subscriptions/{user}/extend', [AdminSubscriptionController::class, 'extend'])->name('subscriptions.extend');
    Route::post('/subscriptions/{user}/adjust-expiry', [AdminSubscriptionController::class, 'adjustExpiry'])->name('subscriptions.adjustExpiry');
    Route::post('/subscriptions/{user}/initialize-trial', [AdminSubscriptionController::class, 'initializeTrial'])->name('subscriptions.initializeTrial');
    Route::get('/subscriptions/order/{order}', [AdminSubscriptionController::class, 'showOrder'])->name('subscriptions.showOrder');
    Route::get('/subscriptions/payment-logs', [AdminSubscriptionController::class, 'showPaymentLogs'])->name('subscriptions.paymentLogs');
    Route::get('/subscriptions/statistics', [AdminSubscriptionController::class, 'statistics'])->name('subscriptions.statistics');
    Route::post('/subscriptions/bulk-action', [AdminSubscriptionController::class, 'bulkAction'])->name('subscriptions.bulkAction');
});

// ECPay 測試路由 (無需登入) - 排除 POST 回傳路由（已在 bootstrap/app.php 中定義）
Route::prefix('ecpay/test')->name('ecpay.test.')->group(function () {
    Route::get('/', [EcpayTestController::class, 'index'])->name('index');
    Route::post('/generate', [EcpayTestController::class, 'generatePayment'])->name('generate');
    Route::get('/payment-info', [EcpayTestController::class, 'paymentInfoTest'])->name('payment-info-test');
    Route::get('/client-return', [EcpayTestController::class, 'clientReturn'])->name('client-return');
});

// API路由 (可選，供前端AJAX使用)
Route::middleware(['auth'])->prefix('api/subscription')->name('api.subscription.')->group(function () {
    Route::get('/status', [SubscriptionController::class, 'getStatus'])->name('api.status');
});