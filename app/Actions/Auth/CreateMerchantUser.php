<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\StoreQuotaService;
use App\Notifications\WelcomeMerchant;
use App\Notifications\VerifyMerchantEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateMerchantUser
{
    public function __construct(protected StoreQuotaService $quotaService)
    {
    }

    public function execute(string $name, string $email, string $password): User
    {
        return DB::transaction(function () use ($name, $email, $password) {
            // Create the user record
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'user_type' => 'merchant',
                'email_verification_code' => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
                'email_verification_code_expires_at' => now()->addMinutes(60),
            ]);

            // Initialize the default store quota for the new merchant
            $this->quotaService->initializeUserQuota($user);

            // Send a verification notification
            $user->notify(new VerifyMerchantEmail());

            return $user;
        });
    }
}
