<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartParcelLocker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Webkul\Checkout\Facades\Cart;
use Webkul\Payment\Facades\Payment;
use Webkul\Shipping\Facades\Shipping;
use Webkul\Shop\Http\Resources\CartResource;

class CustomerCheckoutController extends Controller
{
    public function saveAddress(Request $request): JsonResponse|JsonResource
    {
        // Sanctum authenticates via its own guard; Cart::initCart() uses the default
        // 'customer' guard (session-based) which returns null for token requests.
        // Re-initialize the cart explicitly with the sanctum-authenticated user.
        if ($customer = $request->user()) {
            Cart::initCart($customer);
        }

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

    public function saveShipping(Request $request): JsonResponse|JsonResource
    {
        if ($customer = $request->user()) {
            Cart::initCart($customer);
        }

        if (Cart::hasError()) {
            return (new JsonResource([
                'message' => Cart::getErrors()['message'] ?? 'Cart has errors',
            ]))->response()->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        $validated = $request->validate([
            'shipping_method' => ['required'],
            'parcel_locker' => ['nullable', 'array'],
            'parcel_locker.locker_id' => ['nullable', 'string'],
            'parcel_locker.locker_name' => ['nullable', 'string'],
            'parcel_locker.locker_address' => ['nullable', 'string'],
            'parcel_locker.locker_city' => ['nullable', 'string'],
            'parcel_locker.locker_postcode' => ['nullable', 'string'],
            'parcel_locker.locker_country' => ['nullable', 'string'],
        ]);

        Cart::collectTotals();

        $cart = Cart::getCart();

        if ($cart?->haveStockableItems() && ! $cart->shipping_address) {
            return (new JsonResource([
                'message' => 'Shipping address is required before selecting shipping method',
            ]))->response()->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($cart?->haveStockableItems() && ! Shipping::collectRates()) {
            return (new JsonResource([
                'message' => 'Unable to collect shipping rates',
            ]))->response()->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        if (! Shipping::isMethodCodeExists($validated['shipping_method'])) {
            return (new JsonResource([
                'message' => 'Shipping method is not available for this cart',
            ]))->response()->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (Cart::hasError() || ! Cart::saveShippingMethod($validated['shipping_method'])) {
            return (new JsonResource([
                'message' => 'Unable to save shipping method',
            ]))->response()->setStatusCode(Response::HTTP_FORBIDDEN);
        }

        $isParcelLockerMethod = str_contains($validated['shipping_method'], 'omniva')
            || str_contains($validated['shipping_method'], 'smartpost')
            || str_contains($validated['shipping_method'], 'dpd');

        if (isset($validated['parcel_locker']) && !empty($validated['parcel_locker'])) {
            $carrier = null;
            if (str_contains($validated['shipping_method'], 'omniva')) {
                $carrier = 'omniva';
            } elseif (str_contains($validated['shipping_method'], 'smartpost')) {
                $carrier = 'smartpost';
            } elseif (str_contains($validated['shipping_method'], 'dpd')) {
                $carrier = 'dpd';
            }

            $parcelLockerData = array_merge($validated['parcel_locker'], ['carrier' => $carrier]);

            CartParcelLocker::updateOrCreate(
                ['cart_id' => $cart->id],
                $parcelLockerData
            );

            Log::info('Parcel locker selected (customer)', [
                'cart_id' => $cart->id,
                'customer_id' => $customer?->id,
                'carrier' => $carrier,
                'locker_id' => $parcelLockerData['locker_id'] ?? null,
                'locker_name' => $parcelLockerData['locker_name'] ?? null,
            ]);
        } elseif (! $isParcelLockerMethod) {
            CartParcelLocker::where('cart_id', $cart->id)->delete();
            Log::info('Parcel locker cleared (customer - non-locker method)', [
                'cart_id' => $cart->id,
                'customer_id' => $customer?->id,
            ]);
        }

        Cart::collectTotals();

        return new JsonResource([
            'data' => [
                'cart'            => new CartResource(Cart::getCart()),
                'payment_methods' => Payment::getSupportedPaymentMethods(),
            ],
        ]);
    }
}
