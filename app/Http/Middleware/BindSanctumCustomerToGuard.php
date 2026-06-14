<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Webkul\Customer\Models\Customer;

/**
 * Bagisto's default auth guard is `customer` (session-based). When a request
 * authenticates via a Sanctum bearer token, the user is bound to the `sanctum`
 * guard — not to `customer`. That breaks code like
 * `CustomerRepository::getCurrentGroup()` which calls
 * `auth()->guard()->user()` (the default `customer` guard) and gets null,
 * silently falling back to guest pricing.
 *
 * This middleware resolves the Sanctum token (when present) and explicitly
 * binds the customer to the `customer` guard so every downstream lookup —
 * including Cart::addProduct's price computation — sees the correct customer
 * group and applies group-specific catalog rule prices.
 */
class BindSanctumCustomerToGuard
{
    public function handle(Request $request, Closure $next)
    {
        // Only act if there is a bearer token and the customer guard is not
        // already populated (session login already handled).
        if (auth('customer')->check()) {
            return $next($request);
        }

        $bearer = $request->bearerToken();
        if (! $bearer) {
            return $next($request);
        }

        try {
            $accessToken = PersonalAccessToken::findToken($bearer);
            if ($accessToken && $accessToken->tokenable instanceof Customer) {
                auth('customer')->setUser($accessToken->tokenable);
            }
        } catch (\Throwable $e) {
            // Never block the request because of this convenience binding.
        }

        return $next($request);
    }
}
