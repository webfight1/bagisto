<?php

namespace Webkul\Everypay\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Webkul\Checkout\Facades\Cart;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;

class EverypayController
{
    public function __construct(
        protected OrderRepository $orderRepository
    ) {}

    public function callback(Request $request)
    {
        $paymentReference = (string) $request->input('payment_reference');
        $orderReference = (string) $request->input('order_reference');

        if (! $paymentReference || ! $orderReference) {
            return response()->json(['message' => 'Missing payment_reference/order_reference'], 400);
        }

        $mapping = Cache::get('everypay:order_reference:'.$orderReference);

        if (! $mapping || empty($mapping['cart_id'])) {
            return response()->json(['message' => 'Unknown order_reference'], 404);
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

            Cache::forget('everypay:order_reference:'.$orderReference);
            Cache::forget('everypay:payment_reference:'.$paymentReference);

            return response()->json(['message' => 'OK', 'order_id' => $existingOrder->id]);
        }

        // Load cart into Bagisto Cart facade
        $cartRepository = app(\Webkul\Checkout\Repositories\CartRepository::class);
        $cart = $cartRepository->find($cartId);

        if (! $cart || ! $cart->is_active) {
            return response()->json(['message' => 'Cart not found or inactive'], 404);
        }

        Cart::setCart($cart);

        // Validate payment status from Everypay
        $apiUsername = (string) core()->getConfigData('sales.payment_methods.everypay.api_username');
        $apiSecret = (string) core()->getConfigData('sales.payment_methods.everypay.api_secret');
        $isSandbox = (bool) core()->getConfigData('sales.payment_methods.everypay.sandbox');

        $baseUrl = $isSandbox ? 'https://igw-demo.every-pay.com' : 'https://igw.every-pay.com';

        $statusResp = Http::withBasicAuth($apiUsername, $apiSecret)
            ->get(rtrim($baseUrl, '/').'/api/v4/payments/'.$paymentReference);

        if (! $statusResp->successful()) {
            return response()->json(['message' => 'Unable to fetch payment status'], 502);
        }

        $status = $statusResp->json('payment_state');

        // Everypay uses states like "settled" for success (exact values depend on account)
        if (! in_array($status, ['settled', 'authorised', 'paid'], true)) {
            return response()->json(['message' => 'Payment not successful', 'payment_state' => $status], 409);
        }

        // Create order
        Cart::collectTotals();

        $data = (new OrderResource(Cart::getCart()))->jsonSerialize();
        $order = $this->orderRepository->create($data);

        Cart::deActivateCart();

        // Cleanup cache
        Cache::forget('everypay:order_reference:'.$orderReference);
        Cache::forget('everypay:payment_reference:'.$paymentReference);

        return response()->json(['message' => 'OK', 'order_id' => $order->id]);
    }
}
