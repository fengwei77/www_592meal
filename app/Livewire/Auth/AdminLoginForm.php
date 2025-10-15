<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AdminLoginForm extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function login(): void
    {
        $this->validate();

        if (!Auth::guard('web')->attempt($this->only(['email', 'password']), $this->remember)) {
            $this->addError('email', trans('auth.failed'));

            return;
        }

        session()->regenerate();

        $this->redirect('/admin/dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.admin-login-form');
    }
}
