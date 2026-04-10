<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Webkul\Checkout\Facades\Cart;
use Webkul\Payment\Facades\Payment;
use Webkul\Shipping\Facades\Shipping;
use Webkul\Shop\Http\Resources\CartResource;

class CustomerCheckoutController extends Controller
{
    public function saveAddress(Request $request): JsonResponse|JsonResource
    {
        if (Cart::hasError()) {
            $errors = Cart::getErrors();
            return (new JsonResource([
                'message'    => $errors['message'] ?? 'Cart has errors',
                'error_code' => $errors['error_code'] ?? 'UNKNOWN',
                'cart'       => Cart::getCart() ? 'found' : 'null',
            ]))->response()->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        $validated = $request->validate([
            'billing'                      => ['required', 'array'],
            'billing.use_for_shipping'     => ['nullable', 'boolean'],
            'billing.company_name'         => ['nullable'],
            'billing.first_name'           => ['required'],
            'billing.last_name'            => ['required'],
            'billing.email'                => ['required', 'email'],
            'billing.address'              => ['required', 'array', 'min:1'],
            'billing.city'                 => ['nullable'],
            'billing.country'              => core()->isCountryRequired() ? ['required'] : ['nullable'],
            'billing.state'                => core()->isStateRequired() ? ['required'] : ['nullable'],
            'billing.postcode'             => core()->isPostCodeRequired() ? ['required'] : ['nullable'],
            'billing.phone'                => ['required'],
            'billing.vat_id'               => ['nullable'],
            'shipping'                     => ['nullable', 'array'],
            'shipping.company_name'        => ['nullable'],
            'shipping.first_name'          => ['nullable'],
            'shipping.last_name'           => ['nullable'],
            'shipping.email'               => ['nullable', 'email'],
            'shipping.address'             => ['nullable', 'array'],
            'shipping.city'                => ['nullable'],
            'shipping.country'             => ['nullable'],
            'shipping.state'               => ['nullable'],
            'shipping.postcode'            => ['nullable'],
            'shipping.phone'               => ['nullable'],
            'shipping.additional'          => ['nullable', 'array'],
        ]);

        $billing = $validated['billing'];

        if (! isset($billing['use_for_shipping'])) {
            $billing['use_for_shipping'] = true;
        }

        $params = ['billing' => $billing];

        if (! $billing['use_for_shipping']) {
            $shipping = $validated['shipping'] ?? null;

            if (! $shipping) {
                return (new JsonResource([
                    'message' => 'Shipping address is required when billing.use_for_shipping is false',
                ]))->response()->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $requiredShipping = $request->validate([
                'shipping.first_name' => ['required'],
                'shipping.last_name'  => ['required'],
                'shipping.email'      => ['required', 'email'],
                'shipping.address'    => ['required', 'array', 'min:1'],
                'shipping.city'       => ['nullable'],
                'shipping.country'    => core()->isCountryRequired() ? ['required'] : ['nullable'],
                'shipping.state'      => core()->isStateRequired() ? ['required'] : ['nullable'],
                'shipping.postcode'   => core()->isPostCodeRequired() ? ['required'] : ['nullable'],
                'shipping.phone'      => ['required'],
                'shipping.additional' => ['nullable', 'array'],
            ]);

            $params['shipping'] = $requiredShipping['shipping'];
        }

        Cart::saveAddresses($params);

        Cart::collectTotals();

        $cart = Cart::getCart();

        if ($cart->haveStockableItems()) {
            $rates = Shipping::collectRates();

            if (! $rates) {
                return (new JsonResource([
                    'message' => 'Unable to collect shipping rates',
                ]))->response()->setStatusCode(Response::HTTP_BAD_REQUEST);
            }

            return new JsonResource([
                'data' => [
                    'cart'           => new CartResource($cart),
                    'shipping_rates' => $rates,
                ],
            ]);
        }

        return new JsonResource([
            'data' => [
                'cart'            => new CartResource($cart),
                'payment_methods' => Payment::getSupportedPaymentMethods(),
            ],
        ]);
    }
}
