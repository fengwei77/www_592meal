<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Actions\Auth\CreateMerchantUser;
use Illuminate\Support\Facades\Log;

class MerchantRegistrationForm extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
  
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function updated($propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    public function submit(CreateMerchantUser $creator): void
    {
        $this->validate();

        try {
            $creator->execute($this->name, $this->email, $this->password);

            session()->flash('status', '註冊成功！請至您的信箱收取驗證碼以啟用帳號。');
            session()->flash('registered_email', $this->email);

            // 重導向到首頁
            $this->redirect('/', navigate: true);

        } catch (\Exception $e) {
            Log::error('Merchant registration failed', [
                'email' => $this->email,
                'error' => $e->getMessage(),
            ]);

            $this->addError('general', '註冊過程中發生錯誤，請稍後再試。');
        }
    }

    public function render()
    {
        return view('livewire.auth.merchant-registration-form');
    }
}
