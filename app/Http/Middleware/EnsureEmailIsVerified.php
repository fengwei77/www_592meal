<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ensure the user is authenticated before checking verification
        if (Auth::check() && ! Auth::user()->hasVerifiedEmail()) {
            
            // Check if the user is a merchant, as this rule only applies to them
            if (Auth::user()->user_type === 'merchant') {
                Auth::logout();

                return redirect()->route('verification.notice', ['email' => Auth::user()->email])
                    ->withErrors(['email' => '您必須先驗證您的 Email 地址才能登入後台。']);
            }
        }

        return $next($request);
    }
}