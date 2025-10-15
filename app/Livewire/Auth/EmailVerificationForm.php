<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use App\Notifications\SendBackendLoginUrl;

class EmailVerificationForm extends Component
{
    public string $email = '';
    public string $code = '';

    public function mount()
    {
        $this->email = urldecode(request()->query('email', ''));
    }

    public function verify()
    {
        $this->validate([
            'email' => 'required|email',
            'code' => 'required|string|digits:6',
        ]);

        $user = User::where('email', $this->email)->first();

        if (!$user) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => '找不到此郵箱地址。',
            ]);
        }

        if (!$user->email_verification_code || !$user->email_verification_code_expires_at) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'code' => '驗證碼不存在或已過期。',
            ]);
        }

        if ($user->email_verification_code !== $this->code) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'code' => '驗證碼無效。',
            ]);
        }

        if (now()->gt($user->email_verification_code_expires_at)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'code' => '驗證碼已過期，請重新申請。',
            ]);
        }

        $user->email_verified_at = now();
        $user->email_verification_code = null;
        $user->email_verification_code_expires_at = null;
        $user->save();

        $user->notify(new SendBackendLoginUrl());

        // 設定驗證成功的 session 資料
        session()->flash('verification_success', true);
        session()->flash('verified_email', $user->email);
        session()->flash('status', 'Email 驗證成功！現在您可以使用密碼登入後台。');

        // 重導向到後台登入頁面
        return $this->redirect('/admin/login', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.email-verification-form');
    }
}