<?php

namespace Tests\Feature\Auth;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class LineLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that user can redirect to LINE authorization page
     */
    public function test_user_can_redirect_to_line_authorization_page(): void
    {
        $response = $this->get(route('auth.line'));

        $response->assertStatus(302);
        $this->assertStringContainsString('access.line.me/oauth2/v2.1/authorize', $response->headers->get('Location'));
        $this->assertTrue(Session::has('line_login_state'));
    }

    /**
     * Test that user can login with LINE successfully
     */
    public function test_user_can_login_with_line(): void
    {
        // Mock LINE API responses
        Http::fake([
            'api.line.me/oauth2/v2.1/token' => Http::response([
                'access_token' => 'mock_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 2592000,
            ]),
            'api.line.me/v2/profile' => Http::response([
                'userId' => 'U1234567890abcdef',
                'displayName' => 'Test User',
                'pictureUrl' => 'https://example.com/avatar.jpg',
                'email' => 'test@example.com',
            ]),
        ]);

        // Set state in session
        $state = 'test_state_12345';
        Session::put('line_login_state', $state);

        // Simulate LINE callback
        $response = $this->get(route('auth.line.callback', [
            'code' => 'AUTH_CODE_12345',
            'state' => $state,
        ]));

        // Assert user is redirected to home
        $response->assertRedirect('/');
        $response->assertSessionHas('success', '登入成功！');

        // Assert customer is authenticated
        $this->assertAuthenticated('customer');

        // Assert customer was created in database
        $this->assertDatabaseHas('customers', [
            'line_id' => 'U1234567890abcdef',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'avatar_url' => 'https://example.com/avatar.jpg',
        ]);

        // Assert state was removed from session
        $this->assertFalse(Session::has('line_login_state'));
    }

    /**
     * Test that existing customer can login and update their profile
     */
    public function test_existing_user_can_login_and_update_profile(): void
    {
        // Create existing customer
        $customer = Customer::create([
            'line_id' => 'U1234567890abcdef',
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'avatar_url' => 'https://example.com/old_avatar.jpg',
        ]);

        // Mock LINE API responses with updated info
        Http::fake([
            'api.line.me/oauth2/v2.1/token' => Http::response([
                'access_token' => 'mock_access_token',
            ]),
            'api.line.me/v2/profile' => Http::response([
                'userId' => 'U1234567890abcdef',
                'displayName' => 'Updated Name',
                'pictureUrl' => 'https://example.com/new_avatar.jpg',
                'email' => 'new@example.com',
            ]),
        ]);

        $state = 'test_state_67890';
        Session::put('line_login_state', $state);

        $response = $this->get(route('auth.line.callback', [
            'code' => 'AUTH_CODE_67890',
            'state' => $state,
        ]));

        $response->assertRedirect('/');
        $this->assertAuthenticated('customer');

        // Assert customer profile was updated
        $customer->refresh();
        $this->assertEquals('Updated Name', $customer->name);
        $this->assertEquals('new@example.com', $customer->email);
        $this->assertEquals('https://example.com/new_avatar.jpg', $customer->avatar_url);
    }

    /**
     * Test that invalid state parameter is rejected
     */
    public function test_it_rejects_invalid_state_parameter(): void
    {
        Session::put('line_login_state', 'valid_state_12345');

        $response = $this->get(route('auth.line.callback', [
            'code' => 'AUTH_CODE',
            'state' => 'invalid_state_67890',
        ]));

        // Assert redirected to login with error
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', '安全驗證失敗，請重新登入');

        // Assert customer is not authenticated
        $this->assertGuest('customer');
    }

    /**
     * Test that missing state parameter is rejected
     */
    public function test_it_rejects_missing_state_parameter(): void
    {
        Session::put('line_login_state', 'valid_state');

        $response = $this->get(route('auth.line.callback', [
            'code' => 'AUTH_CODE',
            // state parameter is missing
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertGuest('customer');
    }

    /**
     * Test that LINE API token exchange failure is handled
     */
    public function test_it_handles_line_api_token_exchange_failure(): void
    {
        // Mock failed token exchange
        Http::fake([
            'api.line.me/oauth2/v2.1/token' => Http::response([], 400),
        ]);

        $state = 'test_state';
        Session::put('line_login_state', $state);

        $response = $this->get(route('auth.line.callback', [
            'code' => 'INVALID_CODE',
            'state' => $state,
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', '登入失敗，請稍後再試');
        $this->assertGuest('customer');
    }

    /**
     * Test that LINE API profile fetch failure is handled
     */
    public function test_it_handles_line_api_profile_fetch_failure(): void
    {
        // Mock successful token exchange but failed profile fetch
        Http::fake([
            'api.line.me/oauth2/v2.1/token' => Http::response([
                'access_token' => 'mock_token',
            ]),
            'api.line.me/v2/profile' => Http::response([], 500),
        ]);

        $state = 'test_state';
        Session::put('line_login_state', $state);

        $response = $this->get(route('auth.line.callback', [
            'code' => 'AUTH_CODE',
            'state' => $state,
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', '登入失敗，請稍後再試');
        $this->assertGuest('customer');
    }

    /**
     * Test that customer can logout successfully
     */
    public function test_user_can_logout(): void
    {
        // Create and authenticate customer
        $customer = Customer::create([
            'line_id' => 'U1234567890abcdef',
            'name' => 'Test Customer',
            'email' => 'test@example.com',
        ]);

        $this->actingAs($customer, 'customer');
        $this->assertAuthenticated('customer');

        // Logout
        $response = $this->post(route('logout'));

        $response->assertRedirect('/');
        $response->assertSessionHas('success', '登出成功！');
        $this->assertGuest('customer');
    }

    /**
     * Test that user without email can login
     */
    public function test_user_without_email_can_login(): void
    {
        // Mock LINE API without email
        Http::fake([
            'api.line.me/oauth2/v2.1/token' => Http::response([
                'access_token' => 'mock_token',
            ]),
            'api.line.me/v2/profile' => Http::response([
                'userId' => 'U9876543210',
                'displayName' => 'User Without Email',
                'pictureUrl' => 'https://example.com/avatar.jpg',
                // email is not provided
            ]),
        ]);

        $state = 'test_state';
        Session::put('line_login_state', $state);

        $response = $this->get(route('auth.line.callback', [
            'code' => 'AUTH_CODE',
            'state' => $state,
        ]));

        $response->assertRedirect('/');
        $this->assertAuthenticated('customer');

        // Assert customer was created without email
        $this->assertDatabaseHas('customers', [
            'line_id' => 'U9876543210',
            'name' => 'User Without Email',
            'email' => null,
        ]);
    }
}
