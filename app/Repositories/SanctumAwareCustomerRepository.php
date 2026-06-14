<?php

namespace App\Repositories;

use Laravel\Sanctum\PersonalAccessToken;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Repositories\CustomerRepository;

/**
 * Override vendor CustomerRepository so getCurrentGroup() returns the correct
 * group even when the request is authenticated via a Sanctum bearer token
 * (default `customer` guard is session-based and is empty for token-only
 * requests). This is critical for Cart::addProduct so it stores
 * group-discounted prices in cart_items.
 */
class SanctumAwareCustomerRepository extends CustomerRepository
{
    public function getCurrentGroup()
    {
        // 1) Session-authenticated customer (default flow).
        $customer = auth('customer')->user();

        // 2) Sanctum bearer token fallback.
        if (! $customer) {
            $request = request();
            $bearer = $request?->bearerToken();
            if ($bearer) {
                try {
                    $accessToken = PersonalAccessToken::findToken($bearer);
                    if ($accessToken && $accessToken->tokenable instanceof Customer) {
                        $customer = $accessToken->tokenable;
                    }
                } catch (\Throwable $e) {
                    // Ignore — fall through to guest.
                }
            }
        }

        return $customer->group ?? core()->getGuestCustomerGroup();
    }
}
