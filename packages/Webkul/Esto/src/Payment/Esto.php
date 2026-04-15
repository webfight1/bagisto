<?php

namespace Webkul\Esto\Payment;

use App\Services\MeritInvoiceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Webkul\Payment\Payment\Payment;

class Esto extends Payment
{
    protected $code = 'esto';

    public function getRedirectUrl()
    {
        $cart = $this->getCart();

        $requestedChannelCode = core()->getRequestedChannelCode();
        $defaultChannelCode = core()->getDefaultChannelCode();

        $shopId = (string) core()->getConfigData('sales.payment_methods.esto.shop_id', $requestedChannelCode);
        $secretKey = (string) core()->getConfigData('sales.payment_methods.esto.secret_key', $requestedChannelCode);
        $returnUrl = (string) core()->getConfigData('sales.payment_methods.esto.return_url', $requestedChannelCode);
        $notificationUrl = (string) core()->getConfigData('sales.payment_methods.esto.notification_url', $requestedChannelCode);
        $scheduleType = (string) core()->getConfigData('sales.payment_methods.esto.schedule_type', $requestedChannelCode);
        $sandbox = (bool) core()->getConfigData('sales.payment_methods.esto.sandbox', $requestedChannelCode);

        if (! $shopId || ! $secretKey) {
            $shopId = $shopId ?: (string) core()->getConfigData('sales.payment_methods.esto.shop_id', $defaultChannelCode);
            $secretKey = $secretKey ?: (string) core()->getConfigData('sales.payment_methods.esto.secret_key', $defaultChannelCode);
            $returnUrl = $returnUrl ?: (string) core()->getConfigData('sales.payment_methods.esto.return_url', $defaultChannelCode);
            $notificationUrl = $notificationUrl ?: (string) core()->getConfigData('sales.payment_methods.esto.notification_url', $defaultChannelCode);
            $scheduleType = $scheduleType ?: (string) core()->getConfigData('sales.payment_methods.esto.schedule_type', $defaultChannelCode);
        }

        if (! $scheduleType) {
            $scheduleType = 'ESTO_PAY';
        }

        if (! $shopId || ! $secretKey) {
            throw new \Exception('Esto is not configured');
        }

        $amount = round((float) $cart->grand_total, 2);

        // If Merit invoice integration is enabled, pre-reserve the next invoice number
        // and use it as the Esto reference so it appears in the bank transfer description,
        // allowing Merit to match the payment to the invoice.
        // Falls back to the cart-based reference when Merit is disabled.
        $reference = $this->makeReference($cart->id);

        if (config('merit-invoice.enabled', false)) {
            try {
                $meritService = app(MeritInvoiceService::class);
                $reservedNo   = $meritService->getNextInvoiceNumber();

                if ($reservedNo) {
                    Cache::put(
                        $meritService->reservedInvoiceCacheKey($cart->id),
                        $reservedNo,
                        now()->addHours(24)
                    );
                    // Reverse lookup: invoice number → cart ID (for webhook handler)
                    Cache::put(
                        'esto:cart_for_invoice:' . $reservedNo,
                        $cart->id,
                        now()->addHours(24)
                    );
                    $reference = $reservedNo;

                    Log::info('Esto: reserved Merit invoice number', [
                        'cart_id'    => $cart->id,
                        'invoice_no' => $reservedNo,
                    ]);
                }
            } catch (\Exception $e) {
                // Merit unavailable — fall back to cart reference silently.
                Log::warning('Esto: could not reserve Merit invoice number, using cart reference', [
                    'cart_id' => $cart->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        // Always use the production API endpoint.
        // Sandbox/test mode is controlled by the connection_mode parameter per
        // the official ESTO API documentation – not by a separate URL.
        $baseUrl = 'https://api.esto.ee';

        if (! $returnUrl) {
            $returnUrl = config('app.url');
        }

        if (! $notificationUrl) {
            $notificationUrl = route('esto.callback');
        }

        // Build cart items for the items array (required by /purchase/redirect)
        $items = [];
        foreach ($cart->items ?? [] as $cartItem) {
            $items[] = [
                'name'       => $cartItem->name,
                'unit_price' => round((float) $cartItem->price, 2),
                'quantity'   => (int) $cartItem->quantity,
            ];
        }

        $payload = [
            'amount'           => $amount,
            'currency'         => $cart->cart_currency_code ?? 'EUR',
            'reference'        => $reference,
            'return_url'       => $returnUrl,
            'cancel_url'       => $returnUrl,
            'notification_url' => $notificationUrl,
            'schedule_type'    => $scheduleType,
            'connection_mode'  => $sandbox ? 'test' : 'live',
            'items'            => $items,
            'customer'         => [
                'email'      => $cart->customer_email,
                'phone'      => $cart->billing_address?->phone ?? '',
                'first_name' => $cart->billing_address?->first_name ?? '',
                'last_name'  => $cart->billing_address?->last_name ?? '',
                'address'    => $cart->billing_address?->address1 ?? '',
                'city'       => $cart->billing_address?->city ?? '',
                'post_code'  => $cart->billing_address?->postcode ?? '',
            ],
        ];

        $response = Http::withBasicAuth($shopId, $secretKey)
            ->asJson()
            ->post(rtrim($baseUrl, '/').'/v2/purchase/redirect', $payload);

        if (! $response->successful()) {
            throw new \Exception('Esto create purchase failed: '.$response->body());
        }

        $data = $response->json();

        // /v2/purchase/redirect returns data as a plain JSON object (not an
        // encoded string), so we access it directly.
        $purchaseData = $data['data'] ?? null;
        if (is_string($purchaseData)) {
            // Fallback: handle the (unlikely) case where data is still a string.
            $purchaseData = json_decode($purchaseData, true);
        }

        $purchaseUrl = $purchaseData['purchase_url'] ?? null;
        $purchaseId = $purchaseData['id'] ?? null;

        if (! $purchaseUrl || ! $purchaseId) {
            throw new \Exception('Esto create purchase failed – missing purchase_url/id. Response: ' . $response->body());
        }

        Cache::put($this->cacheKeyForReference($reference), [
            'cart_id'     => $cart->id,
            'purchase_id' => $purchaseId,
            'reference'   => $reference,
        ], now()->addDay());

        Cache::put($this->cacheKeyForPurchaseId($purchaseId), [
            'cart_id'     => $cart->id,
            'purchase_id' => $purchaseId,
            'reference'   => $reference,
        ], now()->addDay());

        return $purchaseUrl;
    }

    protected function makeReference(int $cartId): string
    {
        return 'cart-'.$cartId.'-'.time().'-'.Str::random(6);
    }

    protected function cacheKeyForReference(string $reference): string
    {
        return 'esto:reference:'.$reference;
    }

    protected function cacheKeyForPurchaseId(string $purchaseId): string
    {
        return 'esto:purchase_id:'.$purchaseId;
    }
}
