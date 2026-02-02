<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Repositories\CartRepository;
use Webkul\Payment\Facades\Payment;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;
use Webkul\Shipping\Facades\Shipping;
use Webkul\Shop\Http\Resources\CartResource;

class GuestCheckoutController extends Controller
{
    public function __construct(
        protected CartRepository $cartRepository,
        protected OrderRepository $orderRepository,
    ) {}

    public function storeAddresses(Request $request): JsonResponse|JsonResource
    {
        $cart = $this->requireCartFromToken($request);
        Cart::setCart($cart);

        if (Cart::hasError()) {
            return (new JsonResource([
                'message' => Cart::getErrors()['message'] ?? 'Cart has errors',
            ]))->response()->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        $validated = $request->validate([
            'billing'                     => ['required', 'array'],
            'billing.use_for_shipping'     => ['nullable', 'boolean'],
            'billing.company_name'         => ['nullable'],
            'billing.first_name'           => ['required'],
            'billing.last_name'            => ['required'],
            'billing.email'                => ['required', 'email'],
            'billing.address'              => ['required', 'array', 'min:1'],
            'billing.city'                 => ['required'],
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
                'shipping.city'       => ['required'],
                'shipping.country'    => core()->isCountryRequired() ? ['required'] : ['nullable'],
                'shipping.state'      => core()->isStateRequired() ? ['required'] : ['nullable'],
                'shipping.postcode'   => core()->isPostCodeRequired() ? ['required'] : ['nullable'],
                'shipping.phone'      => ['required'],
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
                    'cart'            => new CartResource($cart),
                    'shipping_rates'  => $rates,
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

    public function shippingMethods(Request $request): JsonResponse|JsonResource
    {
        $cart = $this->requireCartFromToken($request);
        Cart::setCart($cart);

        Cart::collectTotals();

        if (! Cart::getCart()?->haveStockableItems()) {
            return new JsonResource([
                'data' => [
                    'shipping_rates' => [],
                ],
            ]);
        }

        $rates = Shipping::collectRates();

        if (! $rates) {
            return (new JsonResource([
                'message' => 'Unable to collect shipping rates',
            ]))->response()->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        return new JsonResource([
            'data' => [
                'shipping_rates' => $rates,
            ],
        ]);
    }

    public function storeShippingMethod(Request $request): JsonResponse|JsonResource
    {
        $cart = $this->requireCartFromToken($request);
        Cart::setCart($cart);

        if (Cart::hasError()) {
            return (new JsonResource([
                'message' => Cart::getErrors()['message'] ?? 'Cart has errors',
            ]))->response()->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        $validated = $request->validate([
            'shipping_method' => ['required'],
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

        Cart::collectTotals();

        return new JsonResource([
            'data' => [
                'cart'            => new CartResource(Cart::getCart()),
                'payment_methods' => Payment::getSupportedPaymentMethods(),
            ],
        ]);
    }

    public function paymentMethods(Request $request): JsonResponse|JsonResource
    {
        $cart = $this->requireCartFromToken($request);
        Cart::setCart($cart);

        if (Cart::hasError()) {
            return (new JsonResource([
                'message' => Cart::getErrors()['message'] ?? 'Cart has errors',
            ]))->response()->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        return new JsonResource([
            'data' => [
                'payment_methods' => Payment::getSupportedPaymentMethods(),
            ],
        ]);
    }

    public function storePaymentMethod(Request $request): JsonResponse|JsonResource
    {
        $cart = $this->requireCartFromToken($request);
        Cart::setCart($cart);

        $validated = $request->validate([
            'payment' => ['required', 'array'],
            'payment.method' => ['required'],
        ]);

        if (Cart::hasError() || ! Cart::savePaymentMethod($validated['payment'])) {
            return (new JsonResource([
                'message' => 'Unable to save payment method',
            ]))->response()->setStatusCode(Response::HTTP_FORBIDDEN);
        }

        Cart::collectTotals();

        return new JsonResource([
            'data' => [
                'cart' => new CartResource(Cart::getCart()),
            ],
        ]);
    }

    public function placeOrder(Request $request): JsonResponse|JsonResource
    {
        $cart = $this->requireCartFromToken($request);
        Cart::setCart($cart);

        if (Cart::hasError()) {
            return (new JsonResource([
                'message' => Cart::getErrors()['message'] ?? 'Cart has errors',
            ]))->response()->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        Cart::collectTotals();

        try {
            $this->validateOrder();
        } catch (\Exception $e) {
            return (new JsonResource([
                'message' => $e->getMessage(),
            ]))->response()->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        $cart = Cart::getCart();

        if ($redirectUrl = Payment::getRedirectUrl($cart)) {
            return new JsonResource([
                'data' => [
                    'redirect'     => true,
                    'redirect_url' => $redirectUrl,
                ],
            ]);
        }

        $data = (new OrderResource($cart))->jsonSerialize();

        $order = $this->orderRepository->create($data);

        Cart::deActivateCart();

        return new JsonResource([
            'data' => [
                'redirect' => false,
                'order_id' => $order->id,
            ],
        ]);
    }

    public function paymentStatus(Request $request): JsonResource
    {
        $cart = $this->getCartFromToken($request);

        if (! $cart) {
            return new JsonResource([
                'data' => [
                    'status'   => 'missing_cart',
                    'order_id' => null,
                ],
            ]);
        }

        $order = $this->orderRepository->findOneWhere(['cart_id' => $cart->id]);

        if (! $order) {
            return new JsonResource([
                'data' => [
                    'status'   => 'pending',
                    'order_id' => null,
                ],
            ]);
        }

        return new JsonResource([
            'data' => [
                'status'   => 'paid',
                'order_id' => $order->id,
            ],
        ]);
    }

    protected function validateOrder(): void
    {
        $cart = Cart::getCart();

        $minimumOrderAmount = core()->getConfigData('sales.order_settings.minimum_order.minimum_order_amount') ?: 0;

        if (! Cart::haveMinimumOrderAmount()) {
            throw new \Exception(trans('shop::app.checkout.cart.minimum-order-message', ['amount' => core()->currency($minimumOrderAmount)]));
        }

        if ($cart->haveStockableItems() && ! $cart->shipping_address) {
            throw new \Exception(trans('shop::app.checkout.onepage.address.check-shipping-address'));
        }

        if (! $cart->billing_address) {
            throw new \Exception(trans('shop::app.checkout.onepage.address.check-billing-address'));
        }

        if ($cart->haveStockableItems() && ! $cart->selected_shipping_rate) {
            throw new \Exception(trans('shop::app.checkout.cart.specify-shipping-method'));
        }

        if (! $cart->payment) {
            throw new \Exception(trans('shop::app.checkout.cart.specify-payment-method'));
        }
    }

    protected function getCartFromToken(Request $request)
    {
        $token = $request->header('X-Cart-Token') ?: $request->query('cart_token') ?: $request->input('cart_token');

        if (! $token) {
            return null;
        }

        try {
            $cartId = (int) Crypt::decryptString($token);
        } catch (\Exception $e) {
            return null;
        }

        $cart = $this->cartRepository->find($cartId);

        if (! $cart) {
            return null;
        }

        if (! $cart->is_active) {
            return null;
        }

        if (! $cart->is_guest) {
            return null;
        }

        return $cart;
    }

    protected function requireCartFromToken(Request $request)
    {
        $cart = $this->getCartFromToken($request);

        abort_if(! $cart, 401, 'Guest cart token missing or invalid');

        return $cart;
    }
}
