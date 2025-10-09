<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Exceptions\InvalidStateException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LineLoginController extends Controller
{
    /**
     * Redirect user to LINE OAuth authorization page
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect(): \Illuminate\Http\RedirectResponse
    {
        // Generate and store state to prevent CSRF attacks
        $state = Str::random(40);
        session(['line_login_state' => $state]);

        // Build LINE authorization URL
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.line.client_id'),
            'redirect_uri' => config('services.line.redirect'),
            'state' => $state,
            'scope' => 'profile openid email',
        ]);

        $authorizationUrl = 'https://access.line.me/oauth2/v2.1/authorize?' . $query;

        return redirect($authorizationUrl);
    }

    /**
     * Handle LINE OAuth callback
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws InvalidStateException
     */
    public function callback(Request $request): \Illuminate\Http\RedirectResponse
    {
        try {
            // Validate state parameter (CSRF protection)
            $this->validateState($request);

            // Exchange authorization code for access token
            $accessToken = $this->getAccessToken($request->code);

            // Get user profile from LINE
            $lineUser = $this->getLineUserProfile($accessToken);

            // Create or update customer
            $customer = $this->findOrCreateCustomer($lineUser);

            // Login customer with customer guard
            Auth::guard('customer')->login($customer, true);

            // Regenerate session to prevent session fixation attacks
            session()->regenerate();

            // Clear state from session
            session()->forget('line_login_state');

            return redirect('/')->with('success', '登入成功！');

        } catch (InvalidStateException $e) {
            Log::warning('LINE Login: Invalid state parameter', [
                'expected' => session('line_login_state'),
                'received' => $request->state,
            ]);

            return redirect('/login')
                ->with('error', '安全驗證失敗，請重新登入');

        } catch (\Exception $e) {
            Log::error('LINE Login failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect('/login')
                ->with('error', '登入失敗，請稍後再試');
        }
    }

    /**
     * Validate state parameter to prevent CSRF
     *
     * @param Request $request
     * @throws InvalidStateException
     */
    private function validateState(Request $request): void
    {
        $sessionState = session('line_login_state');
        $requestState = $request->state;

        if (empty($sessionState) || $sessionState !== $requestState) {
            throw new InvalidStateException('Invalid state parameter');
        }
    }

    /**
     * Exchange authorization code for access token
     *
     * @param string $code
     * @return string
     * @throws \Exception
     */
    private function getAccessToken(string $code): string
    {
        $response = Http::timeout(10)
            ->retry(3, 100)
            ->asForm()
            ->post('https://api.line.me/oauth2/v2.1/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => config('services.line.redirect'),
                'client_id' => config('services.line.client_id'),
                'client_secret' => config('services.line.client_secret'),
            ]);

        if ($response->failed()) {
            throw new \Exception('Failed to get access token from LINE');
        }

        return $response->json('access_token');
    }

    /**
     * Get user profile from LINE API
     *
     * @param string $accessToken
     * @return array
     * @throws \Exception
     */
    private function getLineUserProfile(string $accessToken): array
    {
        $response = Http::timeout(5)
            ->retry(3, 100)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])
            ->get('https://api.line.me/v2/profile');

        if ($response->failed()) {
            throw new \Exception('Failed to get user profile from LINE');
        }

        return $response->json();
    }

    /**
     * Find or create customer from LINE profile
     *
     * @param array $lineUser
     * @return Customer
     */
    private function findOrCreateCustomer(array $lineUser): Customer
    {
        $customer = Customer::where('line_id', $lineUser['userId'])->first();

        if ($customer) {
            // Update existing customer
            $customer->update([
                'name' => $lineUser['displayName'],
                'avatar_url' => $lineUser['pictureUrl'] ?? null,
                'email' => $lineUser['email'] ?? $customer->email,
            ]);
        } else {
            // Create new customer
            $customer = Customer::create([
                'line_id' => $lineUser['userId'],
                'name' => $lineUser['displayName'],
                'email' => $lineUser['email'] ?? null,
                'avatar_url' => $lineUser['pictureUrl'] ?? null,
            ]);
        }

        return $customer;
    }
}
