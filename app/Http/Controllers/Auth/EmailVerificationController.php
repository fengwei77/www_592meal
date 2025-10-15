<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\VerifyMerchantEmail;
use App\Notifications\SendBackendLoginUrl;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Verify the email using verification code.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|digits:6',
        ]);

        // 確保 email 被正確解碼
        $email = urldecode($request->email);
        $code = $request->code;

        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('verification.notice', ['email' => $email])
                ->withErrors(['email' => '無法找到該 Email 的使用者。']);
        }

        if ($user->email_verified_at) {
            return redirect()->route('verification.notice', ['email' => $email])
                ->with('status', '此 Email 已驗證成功，請直接前往後台登入。');
        }

        // 驗證碼檢查
        if ($user->email_verification_code !== $code) {
            return redirect()->route('verification.notice', ['email' => $email])
                ->withErrors(['code' => '驗證碼無效。']);
        }

        // 檢查驗證碼是否過期
        if (now()->gt($user->email_verification_code_expires_at)) {
            return redirect()->route('verification.notice', ['email' => $email])
                ->withErrors(['code' => '驗證碼已過期，請重新發送。']);
        }

        // 標記為已驗證
        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_code_expires_at' => null,
        ])->save();

        // 發送後台登入連結通知
        $user->notify(new SendBackendLoginUrl());

        // 設定驗證成功的 session 資料
        session()->flash('verification_success', true);
        session()->flash('verified_email', $user->email);
        session()->flash('status', 'Email 驗證成功！現在您可以使用密碼登入後台。');

        // 重新導向到驗證頁面以顯示成功狀態
        return redirect()->route('verification.notice', ['email' => $email]);
    }

    /**
     * Resend the email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resend(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // 確保 email 被正確解碼
        $email = urldecode($request->email);

        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('verification.notice', ['email' => $email])
                ->withErrors(['email' => '無法找到該 Email 的使用者。']);
        }

        if ($user->email_verified_at) {
            return redirect()->route('verification.notice', ['email' => $email])
                ->with('status', '此 Email 已驗證成功，請直接前往後台登入。');
        }

        // Generate new code and expiration
        $user->forceFill([
            'email_verification_code' => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'email_verification_code_expires_at' => now()->addMinutes(60),
        ])->save();

        // Resend notification
        $user->notify(new VerifyMerchantEmail());

        return redirect()->route('verification.notice', ['email' => $email])
            ->with('status', '新的驗證信已寄出！');
    }
}