<?php

namespace Webkul\Esto\Payment;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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
        $reference = $this->makeReference($cart->id);

        $baseUrl = $sandbox ? 'https://api-sandbox.esto.ee' : 'https://api.esto.ee';

        if (! $returnUrl) {
            $returnUrl = config('app.url');
        }

        if (! $notificationUrl) {
            $notificationUrl = route('esto.callback');
        }

        $payload = [
            'amount'          => $amount,
            'currency'        => $cart->cart_currency_code ?? 'EUR',
            'reference'       => $reference,
            'return_url'      => $returnUrl,
            'notification_url'=> $notificationUrl,
            'schedule_type'   => $scheduleType,
            'customer'        => [
                'email'      => $cart->customer_email,
                'phone'      => $cart->billing_address?->phone,
                'first_name' => $cart->billing_address?->first_name,
                'last_name'  => $cart->billing_address?->last_name,
            ],
        ];

        $response = Http::withBasicAuth($shopId, $secretKey)
            ->asJson()
            ->post(rtrim($baseUrl, '/').'/v2/purchase', $payload);

        if (! $response->successful()) {
            throw new \Exception('Esto create purchase failed: '.$response->body());
        }

        $data = $response->json();
        $purchaseData = isset($data['data']) ? json_decode($data['data'], true) : null;

        $purchaseUrl = $purchaseData['purchase_url'] ?? null;
        $purchaseId = $purchaseData['id'] ?? null;

        if (! $purchaseUrl || ! $purchaseId) {
            throw new \Exception('Esto response missing purchase_url/id');
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
