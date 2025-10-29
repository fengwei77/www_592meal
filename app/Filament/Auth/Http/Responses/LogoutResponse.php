<?php

namespace App\Filament\Auth\Http\Responses;

use Filament\Auth\Http\Responses\LogoutResponse as BaseLogoutResponse;

class LogoutResponse extends BaseLogoutResponse
{
    public function toResponse($request): \Illuminate\Http\RedirectResponse|\Livewire\Features\SupportRedirects\Redirector
    {
        // 簡化重導向邏輯，直接重導向到後台登入頁面
        return redirect('/login');
    }
}