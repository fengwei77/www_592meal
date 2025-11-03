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
            'email.required' => '信箱地址為必填項目',
            'email.string' => '信箱地址格式不正確',
            'email.email' => '請輸入有效的信箱格式（例如：example@mail.com）',
            'email.max' => '信箱地址不得超過 255 個字元',
            'email.exists' => '此信箱尚未在系統中註冊，請先完成註冊',
        ];
    }

    /**
     * 自訂驗證屬性名稱
     */
    protected function validationAttributes(): array
    {
        return [
            'email' => '信箱地址',
        ];
    }

    public function mount()
    {
        // Check if email is passed from registration
        if (session()->has('registered_email')) {
            $this->email = session('registered_email');
        }
    }

    /**
     * 移除即時驗證，只在提交時驗證
     * 避免頁面初始加載時觸發驗證錯誤
     */
    // public function updated($propertyName): void
    // {
    //     if ($propertyName === 'email' && !empty($this->email)) {
    //         $this->validateOnly($propertyName);
    //     }
    // }

    public function resend(): void
    {
        // 清除之前的錯誤訊息
        $this->resetErrorBag();

        // 先去除前後空白
        $this->email = trim($this->email);

        // 進行表單驗證
        $this->validate();

        // Rate limiting: 1 request per minute per email
        $key = 'resend-verification:' . $this->email;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('email', "⏰ 發送過於頻繁！請等待 {$seconds} 秒後再試。");
            return;
        }

        try {
            $user = User::where('email', $this->email)->first();

            // 雙重確認用戶存在（雖然 exists 規則已經檢查過）
            if (!$user) {
                $this->addError('email', '❌ 此信箱尚未在系統中註冊，請先完成註冊');
                return;
            }

            // Check if already verified
            if ($user->hasVerifiedEmail()) {
                $this->addError('email', '✅ 此信箱已完成驗證！您可以直接登入使用。');
                return;
            }

            // Regenerate verification code if expired
            if (!$user->email_verification_code_expires_at ||
                $user->email_verification_code_expires_at->isPast()) {
                $user->update([
                    'email_verification_code' => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
                    'email_verification_code_expires_at' => now()->addMinutes(60),
                ]);

                Log::info('Verification code regenerated for expired code', [
                    'email' => $this->email,
                    'user_id' => $user->id,
                ]);
            }

            // Send verification email
            $user->notify(new VerifyMerchantEmail());

            // Set rate limit
            RateLimiter::hit($key, 60); // 60 seconds cooldown

            $this->emailSent = true;

            Log::info('Verification email resent successfully', [
                'email' => $this->email,
                'user_id' => $user->id,
                'code_expires_at' => $user->email_verification_code_expires_at,
            ]);

            session()->flash('status', '✉️ 驗證信已重新發送至您的信箱！');

        } catch (\Exception $e) {
            Log::error('Failed to resend verification email', [
                'email' => $this->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addError('general', '❌ 發送驗證信時發生錯誤，請稍後再試或聯繫客服。');
        }
    }

    public function render()
    {
        return view('livewire.auth.resend-verification-email');
    }
}
