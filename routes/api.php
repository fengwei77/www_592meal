<?php

use App\Http\Controllers\API\PushSubscriptionController;
use App\Http\Controllers\Frontend\StoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Frontend Store Functions
|--------------------------------------------------------------------------
|
| 這些路由提供店家清單、篩選、搜尋等功能的API端點
| 主要為前台店家探索頁面提供數據支援
|
*/

Route::prefix('stores')->group(function () {

    // 店家列表API (支援篩選、搜尋、分頁)
    Route::get('/', [StoreController::class, 'apiIndex']);

    // 店家篩選選項API (縣市、區域、店家類型)
    Route::get('/filters', [StoreController::class, 'getFilters']);

    // 店家搜尋建議API
    Route::get('/search/suggestions', [StoreController::class, 'searchSuggestions']);

    // 地圖模式店家資料API
    Route::get('/map', [StoreController::class, 'mapStores']);

    // 單一店家詳細資訊API
    Route::get('/{store}', [StoreController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| API Routes - Push Notifications
|--------------------------------------------------------------------------
|
| 推播通知訂閱管理 API
|
*/

Route::prefix('push')->group(function () {
    // 訂閱推播通知
    Route::post('/subscribe', [PushSubscriptionController::class, 'subscribe']);

    // 取消推播訂閱
    Route::post('/unsubscribe', [PushSubscriptionController::class, 'unsubscribe']);

    // 查詢推播訂閱狀態（需要認證）
    Route::get('/status', [PushSubscriptionController::class, 'status'])
        ->middleware('auth:customer');

    // 取得用戶的所有訂閱（需要認證）
    Route::get('/list', [PushSubscriptionController::class, 'list'])
        ->middleware('auth:customer');

    // 刪除特定訂閱（需要認證）
    Route::delete('/{id}', [PushSubscriptionController::class, 'delete'])
        ->middleware('auth:customer');
});