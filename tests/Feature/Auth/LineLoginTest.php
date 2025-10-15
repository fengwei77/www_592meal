<?php

namespace Tests\Feature\Auth;

use App\Models\Customer; // Use the Customer model
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LineLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the redirect to LINE works correctly.
     */
    public function test_user_is_redirected_to_line(): void
    {
        $response = $this->get('/auth/line');

        $response->assertStatus(302);
        $this->assertStringStartsWith('https://access.line.me/oauth2/v2.1/authorize', $response->headers->get('Location'));
        $this->assertNotNull(session('line_login_state'));
    }

    /**
     * Test that a new customer can successfully log in via LINE callback.
     */
    public function test_a_new_customer_can_login_with_line(): void
    {
        // Arrange: Mock the LINE API responses
        Http::fake([
            'api.line.me/oauth2/v2.1/token' => Http::response([
                'access_token' => 'mock_access_token',
                'id_token' => 'mock_id_token',
            ]),
            'api.line.me/v2/profile' => Http::response([
                'userId' => 'U1234567890ABCDEF',
                'displayName' => 'Test Customer',
                'pictureUrl' => 'https://example.com/avatar.jpg',
            ]),
        ]);

        $state = 'test_state_string';
        session(['line_login_state' => $state]);

        // Act: Simulate the user being redirected back from LINE
        $response = $this->get('/auth/line/callback?code=test_code&state=' . $state);

        // Assert
        $response->assertRedirect('/');
        $this->assertTrue(Auth::guard('customer')->check(), 'Customer should be authenticated');
        $this->assertDatabaseHas('customers', [ // Check the customers table
            'line_id' => 'U1234567890ABCDEF',
            'name' => 'Test Customer',
            'avatar_url' => 'https://example.com/avatar.jpg',
        ]);
    }

    /**
     * Test that an existing customer can log in.
     */
    public function test_an_existing_customer_can_login_with_line(): void
    {
        // Arrange: Create an existing customer and mock LINE API
        $customer = Customer::factory()->create(['line_id' => 'U1234567890ABCDEF']);

        Http::fake([
            'api.line.me/oauth2/v2.1/token' => Http::response(['access_token' => 'mock_access_token', 'id_token' => 'mock_id_token']),
            'api.line.me/v2/profile' => Http::response(['userId' => 'U1234567890ABCDEF', 'displayName' => 'Updated Name']),
        ]);

        $state = 'test_state_string';
        session(['line_login_state' => $state]);

        // Act
        $response = $this->get('/auth/line/callback?code=test_code&state=' . $state);

        // Assert
        $response->assertRedirect('/');
        $this->assertTrue(Auth::guard('customer')->check(), 'Customer should be authenticated');
        $this->assertEquals($customer->id, Auth::guard('customer')->id());
        $this->assertDatabaseCount('customers', 1);
    }

    /**
     * Test that the login fails if the state is invalid.
     */
    public function test_it_rejects_an_invalid_state_parameter(): void
    {
        // Arrange: Set a valid state in session
        session(['line_login_state' => 'the_real_state']);

        // Act: Attempt callback with a fake state
        $response = $this->get('/auth/line/callback?code=test_code&state=fake_state');

        // Assert
        $response->assertRedirect('/login'); // Should redirect with an error
        $this->assertFalse(Auth::guard('customer')->check(), 'Customer should not be authenticated');
        $this->assertTrue(session()->has('error'));
    }
}