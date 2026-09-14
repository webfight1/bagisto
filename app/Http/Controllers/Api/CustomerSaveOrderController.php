<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Webkul\Checkout\Facades\Cart;
use Webkul\Payment\Facades\Payment;
use Webkul\RestApi\Http\Resources\V1\Shop\Sales\OrderResource;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource as OrderTransformer;

/**
 * Override Bagisto vendor's save-order so that orders created via a Bearer
 * token (Sanctum) get the customer_id persisted on cart + order.
 *
 * The vendor implementation calls Cart::getCart() but never Cart::initCart($user)
 * — so when the request comes from a Bearer-token SPA (no session), the cart
 * has customer_id=NULL and so does the resulting order. That made the order
 * invisible in /api/v1/customer/orders (which filters by customer_id).
 */
class CustomerSaveOrderController extends Controller
{
    public function saveOrder(Request $request, OrderRepository $orderRepository): Response
    {
        // Bind cart to the authenticated customer so customer_id is persisted.
        if ($customer = $request->user()) {
            Cart::initCart($customer);
        }

        if (Cart::hasError()) {
            \Log::warning('save-order: Cart::hasError', ['errors' => Cart::getErrors()]);
            return response()->json([
                'message' => Cart::getErrors()['message'] ?? 'Cart has errors',
            ], 400);
        }

        Cart::collectTotals();

        $cart = Cart::getCart();

        // Backfill `checkout_method` if missing — vendor save-address normally sets
        // this but Bearer-token guest-cart flow sometimes ends up without it.
        if ($cart && empty($cart->checkout_method)) {
            $cart->checkout_method = $customer ? 'register' : 'guest';
            $cart->save();
            \Log::info('save-order: backfilled checkout_method', ['cart_id' => $cart->id, 'method' => $cart->checkout_method]);
        }

        if (! $cart) {
            \Log::warning('save-order: no cart');
            return response()->json(['message' => 'No active cart'], 400);
        }

        if (! $cart->shipping_address || ! $cart->billing_address) {
            \Log::warning('save-order: addresses missing', ['cart_id' => $cart->id]);
            return response()->json(['message' => 'Shipping or billing address is missing'], 400);
        }

        if (empty($cart->shipping_method)) {
            \Log::warning('save-order: shipping_method missing', ['cart_id' => $cart->id]);
            return response()->json(['message' => 'Shipping method not selected'], 400);
        }

        if (! $cart->payment) {
            \Log::warning('save-order: payment missing', ['cart_id' => $cart->id]);
            return response()->json(['message' => 'Payment method not selected'], 400);
        }

        // Backfill on cart in case initCart didn't (race conditions on session-less requests).
        if ($customer && empty($cart->customer_id)) {
            $cart->customer_id    = $customer->id;
            $cart->customer_email = $customer->email;
            $cart->customer_first_name = $cart->customer_first_name ?: $customer->first_name;
            $cart->customer_last_name  = $cart->customer_last_name  ?: $customer->last_name;
            $cart->is_guest = 0;
            $cart->save();
        }

        if ($redirectUrl = Payment::getRedirectUrl($cart)) {
            // Wrap in a 'data' envelope so the SPA (placeOrder in cart.ts) reads
            // redirect_url exactly as it does for the guest place-order response.
            // Without this wrapper the customer redirect_url was invisible to the
            // frontend, so Esto / card checkouts skipped the bank and jumped
            // straight to the thank-you page.
            return response([
                'data' => [
                    'redirect'     => true,
                    'redirect_url' => $redirectUrl,
                ],
            ]);
        }

        $payload = (new OrderTransformer($cart))->jsonSerialize();

        // Belt + suspenders: ensure customer_id is on the order payload even if
        // OrderTransformer missed it (e.g. cart was just saved above).
        if ($customer) {
            $payload['customer_id']    = $customer->id;
            $payload['customer_email'] = $customer->email;
            $payload['is_guest']       = 0;
        }

        $order = $orderRepository->create($payload);

        Cart::deActivateCart();

        // OrderResource always serializes relations (items, addresses, payment, customer,
        // invoices, shipments, channel). Lazy-loading them inside the resource on a fresh
        // model triggers a malformed query in some Eloquent versions — so eager-load here.
        $order->load([
            'items.product',
            'addresses',
            'payment',
            'channel',
            'customer',
            'invoices',
            'shipments',
        ]);

        return response([
            'data' => [
                'order' => new OrderResource($order),
            ],
        ]);
    }
}
