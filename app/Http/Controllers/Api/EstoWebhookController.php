<?php

namespace App\Http\Controllers\Api;

use App\Models\EstoWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Repositories\CartRepository;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;

class EstoWebhookController
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected CartRepository $cartRepository
    ) {}

    public function handle(Request $request)
    {
        // ------------------------------------------------------------------
        // 1. Parse URL-encoded body (application/x-www-form-urlencoded)
        // ------------------------------------------------------------------
        $rawBody = $request->getContent();

        Log::info('ESTO webhook received');

        parse_str($rawBody, $payload);

        if (! is_array($payload)) {
            Log::error('ESTO: Invalid body format');
            return response()->json(['message' => 'Invalid body'], 400);
        }

        $jsonData = $payload['json'] ?? null;
        $mac      = $payload['mac'] ?? null;

        if (! $jsonData || ! $mac) {
            return response()->json(['message' => 'Missing json or mac'], 400);
        }

        // ------------------------------------------------------------------
        // 2. Validate MAC (UPPERCASE(SHA512(json + API_SECRET)))
        // ------------------------------------------------------------------
        $secret = config('services.esto.secret') ?? config('esto.webhook_secret');

        $expectedMac = strtoupper(
            hash('sha512', $jsonData . $secret)
        );

        if (! hash_equals(strtoupper($mac), $expectedMac)) {
            Log::error('ESTO: Invalid MAC', [
                'received' => $mac,
                'expected' => $expectedMac,
            ]);

            return response()->json(['message' => 'Invalid MAC'], 403);
        }

        Log::info('ESTO: MAC valid');

        // ------------------------------------------------------------------
        // 3. Decode ESTO json payload
        // ------------------------------------------------------------------
        $data = json_decode($jsonData, true);

        if (! is_array($data)) {
            return response()->json(['message' => 'Invalid json payload'], 400);
        }

        $reference = (string) ($data['reference'] ?? '');
        $status    = strtoupper((string) ($data['status'] ?? ''));
        $amount    = isset($data['amount']) ? (float) $data['amount'] : null;
        $currency  = strtoupper((string) ($data['currency'] ?? ''));

        if (! $reference) {
            return response()->json(['message' => 'Missing reference'], 400);
        }

        // ------------------------------------------------------------------
        // 4. Idempotency – already processed?
        // ------------------------------------------------------------------
        $existingWebhook = EstoWebhook::where('reference', $reference)->first();

        if ($existingWebhook?->order_id) {
            return response()->json([
                'message'  => 'OK',
                'order_id' => $existingWebhook->order_id,
            ]);
        }

        // ------------------------------------------------------------------
        // 5. Check payment status
        // ------------------------------------------------------------------
        if (! in_array($status, ['APPROVED', 'PAID', 'COMPLETED', 'SETTLED'], true)) {
            $this->storeWebhook($reference, null, $status, $amount, $currency, $payload);
            return response()->json(['message' => 'Payment not approved'], 409);
        }

        // ------------------------------------------------------------------
        // 6. Extract cart ID from reference (cart-123-xxxx)
        // ------------------------------------------------------------------
        if (! preg_match('/^cart-(\d+)-/', $reference, $m)) {
            $this->storeWebhook($reference, null, $status, $amount, $currency, $payload);
            return response()->json(['message' => 'Invalid reference'], 422);
        }

        $cartId = (int) $m[1];

        // ------------------------------------------------------------------
        // 7. Existing order?
        // ------------------------------------------------------------------
        $existingOrder = $this->orderRepository->findOneWhere(['cart_id' => $cartId]);

        if ($existingOrder) {
            $this->storeWebhook($reference, $existingOrder->id, $status, $amount, $currency, $payload);
            return response()->json([
                'message'  => 'OK',
                'order_id' => $existingOrder->id,
            ]);
        }

        // ------------------------------------------------------------------
        // 8. Load cart
        // ------------------------------------------------------------------
        $cart = $this->cartRepository->find($cartId);

        if (! $cart || ! $cart->is_active) {
            $this->storeWebhook($reference, null, $status, $amount, $currency, $payload);
            return response()->json(['message' => 'Cart not found'], 404);
        }

        // Optional but recommended: amount & currency check
        if (
            $amount === null ||
            round($cart->grand_total, 2) !== round($amount, 2) ||
            strtoupper($cart->cart_currency_code) !== $currency
        ) {
            $this->storeWebhook($reference, null, $status, $amount, $currency, $payload);
            return response()->json(['message' => 'Amount or currency mismatch'], 409);
        }

        // ------------------------------------------------------------------
        // 9. Create order
        // ------------------------------------------------------------------
        Cart::setCart($cart);
        Cart::collectTotals();

        $orderData = (new OrderResource(Cart::getCart()))->jsonSerialize();
        $order = $this->orderRepository->create($orderData);

        $order->status = Order::STATUS_PROCESSING;
        $order->save();

        // Attach ESTO info to payment
        if ($order->payment) {
            $additional = $order->payment->additional ?? [];
            $additional['esto'] = [
                'reference' => $reference,
                'status'    => $status,
                'payload'   => $payload,
            ];
            $order->payment->additional = $additional;
            $order->payment->save();
        }

        Cart::deActivateCart();

        // ------------------------------------------------------------------
        // 10. Store webhook & cleanup
        // ------------------------------------------------------------------
        $this->storeWebhook($reference, $order->id, $status, $amount, $currency, $payload);
        Cache::forget('esto:reference:' . $reference);

        Log::info('ESTO: Order created', ['order_id' => $order->id]);

        return response()->json([
            'message'  => 'OK',
            'order_id' => $order->id,
        ]);
    }

    protected function storeWebhook(
        string $reference,
        ?int $orderId,
        ?string $status,
        ?float $amount,
        ?string $currency,
        array $payload
    ): EstoWebhook {
        return EstoWebhook::updateOrCreate(
            ['reference' => $reference],
            [
                'order_id' => $orderId,
                'status'   => $status,
                'amount'   => $amount,
                'currency' => $currency,
                'payload'  => $payload,
            ]
        );
    }
}