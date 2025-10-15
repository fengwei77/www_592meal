<?php

use Illuminate\Support\Facades\Route;

// Admin Routes

Route::get('/login', function() {
    return view('auth.admin-login');
})->name('admin.login');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard.welcome');
    })->name('admin.dashboard');
});
