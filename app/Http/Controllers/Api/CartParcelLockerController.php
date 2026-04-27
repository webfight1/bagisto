<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartParcelLocker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Repositories\CartRepository;

/**
 * Returns the parcel-locker that is currently persisted on the active cart.
 *
 * Used by the WordPress frontend as a safety gate: right before placing the
 * order it asks this endpoint to confirm that Bagisto really has stored the
 * locker the user picked. If `data` is null or its `locker_id` does not
 * match the user's selection, the frontend refuses to place the order so
 * we never end up with a paid order that has no pickup location.
 */
class CartParcelLockerController extends Controller
{
    public function __construct(protected CartRepository $cartRepository) {}

    /**
     * Authenticated customer flow (auth:sanctum).
     */
    public function showForCustomer(Request $request): JsonResponse
    {
        // Sanctum authenticates via its own guard; Cart::initCart() defaults
        // to the session 'customer' guard which is empty for token requests.
        // Re-initialize with the sanctum-authenticated user.
        if ($customer = $request->user()) {
            Cart::initCart($customer);
        }

        return $this->respond(Cart::getCart());
    }

    /**
     * Guest flow (X-Cart-Token header).
     */
    public function showForGuest(Request $request): JsonResponse
    {
        $cart = $this->getCartFromToken($request);

        return $this->respond($cart);
    }

    /**
     * Build the response payload for a (possibly null) cart.
     *
     * All three outcomes are logged on the `parcel-locker` channel so VPS
     * operators can see exactly what the frontend gate observed. The most
     * important signal is `no_locker` on a cart that exists: that means
     * save-shipping did NOT persist the locker, which is the original bug
     * this endpoint was built to catch.
     */
    protected function respond($cart): JsonResponse
    {
        if (! $cart) {
            Log::channel('parcel-locker')->info('verify: no_cart (token missing/invalid)');
            return response()->json([
                'success' => true,
                'data'    => null,
                'reason'  => 'no_cart',
            ]);
        }

        $locker = CartParcelLocker::where('cart_id', $cart->id)->first();

        if (! $locker) {
            Log::channel('parcel-locker')->warning('verify: no_locker on existing cart — save-shipping likely failed to persist', [
                'cart_id'         => $cart->id,
                'shipping_method' => optional($cart->selected_shipping_rate)->method,
                'is_active'       => $cart->is_active,
            ]);
            return response()->json([
                'success' => true,
                'data'    => null,
                'reason'  => 'no_locker',
                'cart_id' => $cart->id,
            ]);
        }

        Log::channel('parcel-locker')->info('verify: ok', [
            'cart_id'         => $cart->id,
            'carrier'         => $locker->carrier,
            'locker_id'       => $locker->locker_id,
            'shipping_method' => optional($cart->selected_shipping_rate)->method,
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'cart_id'         => (int) $cart->id,
                'carrier'         => $locker->carrier,
                // Always cast to string — the WP frontend compares with
                // String() and a numeric drift would cause a false mismatch.
                'locker_id'       => (string) $locker->locker_id,
                'locker_name'     => $locker->locker_name,
                'locker_address'  => $locker->locker_address,
                'locker_city'     => $locker->locker_city,
                'locker_postcode' => $locker->locker_postcode,
                'locker_country'  => $locker->locker_country,
                'shipping_method' => optional($cart->selected_shipping_rate)->method,
                'updated_at'      => optional($locker->updated_at)->toIso8601String(),
            ],
        ]);
    }

    /**
     * Mirrors GuestCheckoutController::getCartFromToken — accepts the token via
     * X-Cart-Token header, ?cart_token= query string, or POST/JSON body.
     */
    protected function getCartFromToken(Request $request)
    {
        $token = $request->header('X-Cart-Token')
            ?: $request->query('cart_token')
            ?: $request->input('cart_token');

        if (! $token) {
            return null;
        }

        try {
            $cartId = (int) Crypt::decryptString($token);
        } catch (\Throwable $e) {
            return null;
        }

        $cart = $this->cartRepository->find($cartId);

        // NB: we deliberately do NOT check $cart->is_active here. Bagisto may
        // flip is_active=0 during the checkout lifecycle even while the cart
        // still exists and its parcel_locker row is present. This endpoint is
        // strictly read-only (no mutation), and the token itself is the
        // authorization boundary, so active-status is irrelevant.
        if (! $cart || ! $cart->is_guest) {
            return null;
        }

        return $cart;
    }
}
