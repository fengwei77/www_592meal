<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\WelcomeMerchant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class MerchantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the merchant registration page is accessible.
     */
    public function test_merchant_registration_page_can_be_rendered(): void
    {
        $response = $this->get('/merchant-register');

        $response->assertStatus(200);
        $response->assertSeeLivewire('auth.merchant-registration-form');
    }

    /**
     * Test that a user can register as a merchant successfully.
     */
    public function test_a_user_can_register_as_a_merchant(): void
    {
        Notification::fake();

        Livewire::test('auth.merchant-registration-form')
            ->set('name', 'Test Merchant')
            ->set('email', 'merchant@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('submit')
            ->assertRedirect('/admin/login');

        // Assert user was created correctly
        $this->assertDatabaseHas('users', [
            'email' => 'merchant@example.com',
            'user_type' => 'merchant',
            'max_stores' => 1, // As per AC#5
        ]);

        // Assert quota log was created
        $this->assertDatabaseHas('store_quota_logs', [
            'user_id' => User::whereEmail('merchant@example.com')->first()->id,
            'action_type' => 'system_reset',
            'old_max_stores' => 0,
            'new_max_stores' => 1,
        ]);

        // Assert that a notification was sent
        $user = User::whereEmail('merchant@example.com')->first();
        Notification::assertSentTo($user, WelcomeMerchant::class);
    }

    /**
     * Test that registration fails if the email is already taken.
     */
    public function test_registration_fails_if_email_is_already_taken(): void
    {
        User::factory()->create(['email' => 'merchant@example.com']);

        Livewire::test('auth.merchant-registration-form')
            ->set('name', 'Another Merchant')
            ->set('email', 'merchant@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('submit')
            ->assertHasErrors(['email' => 'unique']);

        $this->assertDatabaseCount('users', 1);
    }

    /**
     * Test that password confirmation is required.
     */
    public function test_password_confirmation_is_required(): void
    {
        Livewire::test('auth.merchant-registration-form')
            ->set('name', 'Test Merchant')
            ->set('email', 'merchant@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'wrong-password')
            ->call('submit')
            ->assertHasErrors(['password' => 'confirmed']);
    }
}
