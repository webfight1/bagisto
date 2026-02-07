<?php

namespace App\Http\Controllers\Api;

use App\Models\EstoWebhook;
use App\Services\EstoMacValidator;
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
        protected CartRepository $cartRepository,
        protected EstoMacValidator $macValidator
    ) {}

    public function handle(Request $request)
    {
        Log::info('ESTO webhook received', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'raw' => $request->getContent(),
        ]);

        $payload = $request->all();
        $secret = (string) config('esto.webhook_secret');

        if (! $this->macValidator->isValid($payload, $secret)) {
            return response()->json(['message' => 'Invalid MAC'], 403);
        }

        $dataRaw = $payload['data'] ?? null;
        $data = is_string($dataRaw) ? json_decode($dataRaw, true) : (array) $dataRaw;

        if (! is_array($data)) {
            return response()->json(['message' => 'Invalid payload data'], 400);
        }

        $reference = (string) ($data['reference'] ?? '');
        $status = strtoupper((string) ($data['status'] ?? ''));
        $amount = isset($data['amount']) ? (float) $data['amount'] : null;
        $currency = (string) ($data['currency'] ?? '');

        if (! $reference) {
            return response()->json(['message' => 'Missing reference'], 400);
        }

        $existingWebhook = EstoWebhook::where('reference', $reference)->first();

        if ($existingWebhook?->order_id) {
            return response()->json(['message' => 'OK', 'order_id' => $existingWebhook->order_id]);
        }

        if (! $this->isPaymentApproved($status)) {
            $this->storeWebhook($reference, null, $status, $amount, $currency, $payload);
            return response()->json(['message' => 'Payment not approved', 'status' => $status], 409);
        }

        $cartId = $this->extractCartIdFromReference($reference);

        if (! $cartId) {
            $this->storeWebhook($reference, null, $status, $amount, $currency, $payload);
            return response()->json(['message' => 'Invalid reference'], 422);
        }

        $existingOrder = $this->orderRepository->findOneWhere(['cart_id' => $cartId]);

        if ($existingOrder) {
            $this->storeWebhook($reference, $existingOrder->id, $status, $amount, $currency, $payload);
            return response()->json(['message' => 'OK', 'order_id' => $existingOrder->id]);
        }

        $cart = $this->cartRepository->find($cartId);

        if (! $cart || ! $cart->is_active) {
            $this->storeWebhook($reference, null, $status, $amount, $currency, $payload);
            return response()->json(['message' => 'Cart not found or inactive'], 404);
        }

        if (! $this->amountMatchesCart($cart->grand_total, $amount) ||
            ! $this->currencyMatchesCart($cart->cart_currency_code, $currency)) {
            $this->storeWebhook($reference, null, $status, $amount, $currency, $payload);
            return response()->json(['message' => 'Amount or currency mismatch'], 409);
        }

        Cart::setCart($cart);
        Cart::collectTotals();

        $data = (new OrderResource(Cart::getCart()))->jsonSerialize();
        $order = $this->orderRepository->create($data);

        $order->status = Order::STATUS_PROCESSING;
        $order->save();

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

        $this->storeWebhook($reference, $order->id, $status, $amount, $currency, $payload);

        Cache::forget('esto:reference:'.$reference);

        return response()->json(['message' => 'OK', 'order_id' => $order->id]);
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

    protected function isPaymentApproved(string $status): bool
    {
        return in_array($status, ['APPROVED', 'PAID', 'COMPLETED', 'SETTLED', 'AUTHORIZED'], true);
    }

    protected function extractCartIdFromReference(string $reference): ?int
    {
        if (preg_match('/^cart-(\d+)-/', $reference, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    protected function amountMatchesCart(float $cartTotal, ?float $amount): bool
    {
        if ($amount === null) {
            return false;
        }
        return round($cartTotal, 2) === round($amount, 2);
    }

    protected function currencyMatchesCart(?string $cartCurrency, ?string $currency): bool
    {
        if (! $cartCurrency || ! $currency) {
            return false;
        }
        return strtoupper($cartCurrency) === strtoupper($currency);
    }
}
