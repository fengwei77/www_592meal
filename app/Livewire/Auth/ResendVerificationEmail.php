<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Models\User;
use App\Notifications\VerifyMerchantEmail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

class ResendVerificationEmail extends Component
{
    public string $email = '';
    public bool $emailSent = false;

    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'exists:users,email'],
        ];
    }

    protected function messages(): array
    {
        return [
            'email.required' => '請輸入信箱地址',
            'email.email' => '請輸入有效的信箱地址',
            'email.exists' => '此信箱尚未註冊',
        ];
    }

    public function mount()
    {
        // Check if email is passed from registration
        if (session()->has('registered_email')) {
            $this->email = session('registered_email');
        }
    }

    public function updated($propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    public function resend(): void
    {
        $this->validate();

        // Rate limiting: 1 request per minute per email
        $key = 'resend-verification:' . $this->email;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('email', "請稍後再試，您需要等待 {$seconds} 秒後才能再次發送。");
            return;
        }

        try {
            $user = User::where('email', $this->email)->first();

            if (!$user) {
                $this->addError('email', '找不到此信箱的用戶');
                return;
            }

            // Check if already verified
            if ($user->hasVerifiedEmail()) {
                $this->addError('email', '此信箱已經驗證過，請直接登入。');
                return;
            }

            // Regenerate verification code if expired
            if (!$user->email_verification_code_expires_at ||
                $user->email_verification_code_expires_at->isPast()) {
                $user->update([
                    'email_verification_code' => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
                    'email_verification_code_expires_at' => now()->addMinutes(60),
                ]);
            }

            // Send verification email
            $user->notify(new VerifyMerchantEmail());

            // Set rate limit
            RateLimiter::hit($key, 60); // 60 seconds cooldown

            $this->emailSent = true;

            Log::info('Verification email resent', [
                'email' => $this->email,
                'user_id' => $user->id,
            ]);

            session()->flash('status', '驗證信已重新發送！請至您的信箱查收。');

        } catch (\Exception $e) {
            Log::error('Failed to resend verification email', [
                'email' => $this->email,
                'error' => $e->getMessage(),
            ]);

            $this->addError('general', '發送驗證信時發生錯誤，請稍後再試。');
        }
    }

    public function render()
    {
        return view('livewire.auth.resend-verification-email');
    }
}
