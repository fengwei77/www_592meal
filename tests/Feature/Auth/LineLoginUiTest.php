<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UI Tests for LINE Login
 *
 * Tests UI elements for AC1 (Login button) and AC7 (User profile display)
 */
class LineLoginUiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test AC1: Login page displays LINE login button
     *
     * Given: A guest user visits the login page
     * When: The page loads
     * Then: The LINE login button should be visible with correct styling
     */
    public function test_login_page_displays_line_login_button(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);

        // Verify LINE login button text
        $response->assertSee('使用 LINE 登入', false);

        // Verify LINE brand color is used
        $response->assertSee('#06C755', false);

        // Verify LINE login route is present
        $response->assertSee(route('auth.line'), false);

        // Verify page title
        $response->assertSee('登入', false);
        $response->assertSee('592Meal', false);
    }

    /**
     * Test AC1: Login button has correct structure and attributes
     */
    public function test_line_login_button_has_correct_attributes(): void
    {
        $response = $this->get(route('login'));

        // Verify button is a link to LINE auth route
        $response->assertSee('<a href="' . route('auth.line') . '"', false);

        // Verify LINE icon SVG is present
        $response->assertSee('viewBox="0 0 24 24"', false);

        // Verify background color
        $response->assertSee('background-color: #06C755', false);
    }

    /**
     * Test AC7: Authenticated user's name is displayed in navigation
     *
     * Given: A user is logged in
     * When: They visit any page
     * Then: Their name should be displayed in the navigation bar
     */
    public function test_authenticated_user_name_is_displayed(): void
    {
        // Create and authenticate a user
        $user = User::create([
            'line_id' => 'U1234567890',
            'name' => 'Test User Name',
            'email' => 'test@example.com',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);

        // Verify user name is displayed
        $response->assertSee('Test User Name', false);

        // Verify logout button is present
        $response->assertSee('登出', false);
        $response->assertSee(route('logout'), false);
    }

    /**
     * Test AC7: Authenticated user's avatar is displayed in navigation
     *
     * Given: A user with avatar is logged in
     * When: They visit any page
     * Then: Their avatar image should be displayed in the navigation bar
     */
    public function test_authenticated_user_avatar_is_displayed(): void
    {
        // Create user with avatar
        $user = User::create([
            'line_id' => 'U9876543210',
            'name' => 'Avatar User',
            'email' => 'avatar@example.com',
            'avatar_url' => 'https://example.com/avatar.jpg',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);

        // Verify avatar image is rendered
        $response->assertSee($user->avatar_url, false);
        $response->assertSee('<img src="' . $user->avatar_url . '"', false);

        // Verify alt text contains user name
        $response->assertSee('alt="' . $user->name . '"', false);
    }

    /**
     * Test AC7: User without avatar displays fallback UI
     *
     * Given: A user without avatar is logged in
     * When: They visit any page
     * Then: A fallback avatar (initials) should be displayed
     */
    public function test_user_without_avatar_displays_fallback(): void
    {
        // Create user without avatar
        $user = User::create([
            'line_id' => 'U1111111111',
            'name' => '測試用戶',
            'email' => 'no-avatar@example.com',
            'avatar_url' => null,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);

        // Verify user name is still displayed
        $response->assertSee('測試用戶', false);

        // Verify initials are displayed (first 2 characters)
        $response->assertSee('測試', false);
    }

    /**
     * Test: Guest user sees login link in navigation
     */
    public function test_guest_user_sees_login_link_in_navigation(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        // Verify guest sees login link
        $response->assertSee('LINE 登入', false);
        $response->assertSee(route('login'), false);

        // Verify guest does not see logout button
        $response->assertDontSee('登出', false);
    }

    /**
     * Test: Login page has proper security attributes
     */
    public function test_login_page_has_csrf_token(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);

        // Verify CSRF meta tag is present
        $response->assertSee('csrf-token', false);
    }

    /**
     * Test: Login page displays privacy policy links
     */
    public function test_login_page_displays_policy_links(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);

        // Verify terms and privacy policy text
        $response->assertSee('服務條款', false);
        $response->assertSee('隱私政策', false);
    }
}
