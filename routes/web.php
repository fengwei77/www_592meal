<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Livewire\SystemStatus;
use App\Http\Controllers\Auth\LineLoginController;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

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
