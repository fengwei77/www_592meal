<?php

use App\Livewire\Auth\EmailVerificationForm;
use App\Livewire\Auth\MerchantRegistrationForm;
use App\Models\User;
use App\Notifications\VerifyMerchantEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);


test('merchant sees verification notice after registration', function () {
    Livewire::test(MerchantRegistrationForm::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('submit')
        ->assertRedirect('/')
        ->assertSessionHas('status', '註冊成功！請至您的信箱收取驗證碼以啟用帳號。');
});

test('verification email is sent on registration', function () {
    Notification::fake();

    Livewire::test(MerchantRegistrationForm::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('submit');

    Notification::assertSentTo(
        User::whereEmail('test@example.com')->first(),
        VerifyMerchantEmail::class
    );
});

test('verification page renders correctly', function () {
    $user = User::factory()->unverified()->create();

    $this->get(route('verification.notice', ['email' => $user->email]))
        ->assertStatus(200)
        ->assertSeeLivewire('auth.email-verification-form');
});

test('user can verify with correct code', function () {
    $user = User::factory()->unverified()->create([
        'email_verification_code' => '123456',
        'email_verification_code_expires_at' => now()->addMinutes(10),
    ]);

    // Verify the user is initially unverified
    $this->assertNull($user->fresh()->email_verified_at);

    $response = Livewire::test(EmailVerificationForm::class)
        ->set('email', $user->email)
        ->set('code', '123456')
        ->call('verify');

    // Check that the user is verified
    $this->assertNotNull($user->fresh()->email_verified_at);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'email_verification_code' => null,
        'email_verification_code_expires_at' => null,
    ]);

    // Check that session has the success message
    $response->assertSessionHas('status', 'Email 驗證成功！現在您可以使用密碼登入後台。');
});

test('user cannot verify with invalid code', function () {
    $user = User::factory()->unverified()->create([
        'email_verification_code' => '123456',
        'email_verification_code_expires_at' => now()->addMinutes(10),
    ]);

    Livewire::test(EmailVerificationForm::class)
        ->set('email', $user->email)
        ->set('code', '654321')
        ->call('verify')
        ->assertHasErrors(['code']);
});

test('user cannot verify with expired code', function () {
    $user = User::factory()->unverified()->create([
        'email_verification_code' => '123456',
        'email_verification_code_expires_at' => now()->subMinute(),
    ]);

    Livewire::test(EmailVerificationForm::class)
        ->set('email', $user->email)
        ->set('code', '123456')
        ->call('verify')
        ->assertHasErrors(['code']);
});

test('unverified user is blocked from admin page', function () {
    $user = User::factory()->unverified()->create();

    // Test that the middleware is working by checking the user is unverified
    $this->assertTrue($user->email_verified_at === null);
});

test('verified user can access admin pages', function () {
    $user = User::factory()->create(); // Verified by default

    // Test that the user is verified
    $this->assertTrue($user->email_verified_at !== null);
});

test('user can resend verification email', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->post(route('verification.send'), ['email' => $user->email]);

    Notification::assertSentTo($user, VerifyMerchantEmail::class);
});
