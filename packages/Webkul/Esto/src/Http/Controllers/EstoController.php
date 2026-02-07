<?php

namespace Webkul\Esto\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Webkul\Checkout\Facades\Cart;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;

class EstoController
{
    public function __construct(
        protected OrderRepository $orderRepository
    ) {}

    public function callback(Request $request)
    {
        $reference = (string) $request->input('reference');
        $purchaseId = (string) ($request->input('purchase_id') ?? $request->input('id'));
        $status = strtoupper((string) $request->input('status'));

        if (! $reference && ! $purchaseId) {
            return response()->json(['message' => 'Missing reference/purchase_id'], 400);
        }

        $mapping = $reference
            ? Cache::get('esto:reference:'.$reference)
            : Cache::get('esto:purchase_id:'.$purchaseId);

        if (! $mapping || empty($mapping['cart_id'])) {
            return response()->json(['message' => 'Unknown reference'], 404);
        }

        $cartId = (int) $mapping['cart_id'];

        $existingOrder = $this->orderRepository->findOneWhere(['cart_id' => $cartId]);

        if ($existingOrder) {
            $cartRepository = app(\Webkul\Checkout\Repositories\CartRepository::class);
            $cart = $cartRepository->find($cartId);

            if ($cart && $cart->is_active) {
                Cart::setCart($cart);
                Cart::deActivateCart();
            }

            Cache::forget('esto:reference:'.$mapping['reference']);
            Cache::forget('esto:purchase_id:'.$mapping['purchase_id']);

            return response()->json(['message' => 'OK', 'order_id' => $existingOrder->id]);
        }

        if (! $this->isPaymentSuccessful($status)) {
            return response()->json(['message' => 'Payment not successful', 'status' => $status], 409);
        }

        $cartRepository = app(\Webkul\Checkout\Repositories\CartRepository::class);
        $cart = $cartRepository->find($cartId);

        if (! $cart || ! $cart->is_active) {
            return response()->json(['message' => 'Cart not found or inactive'], 404);
        }

        Cart::setCart($cart);
        Cart::collectTotals();

        $data = (new OrderResource(Cart::getCart()))->jsonSerialize();
        $order = $this->orderRepository->create($data);

        Cart::deActivateCart();

        Cache::forget('esto:reference:'.$mapping['reference']);
        Cache::forget('esto:purchase_id:'.$mapping['purchase_id']);

        return response()->json(['message' => 'OK', 'order_id' => $order->id]);
    }

    protected function isPaymentSuccessful(string $status): bool
    {
        if (! $status) {
            return false;
        }

        return in_array($status, ['PAID', 'COMPLETED', 'SETTLED', 'AUTHORIZED'], true);
    }
}
