<?php

namespace Webkul\Everypay\Payment;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Webkul\Payment\Payment\Payment;

class Everypay extends Payment
{
    protected $code = 'everypay';

    public function getRedirectUrl()
    {
        $cart = $this->getCart();

        $apiUsername = (string) $this->getConfigData('api_username');
        $apiSecret = (string) $this->getConfigData('api_secret');
        $accountName = (string) $this->getConfigData('account_name');

        if (! $apiUsername || ! $apiSecret || ! $accountName) {
            throw new \Exception('Everypay is not configured');
        }

        $baseUrl = $this->getConfigData('sandbox') ? 'https://igw-demo.every-pay.com' : 'https://igw.every-pay.com';

        $orderReference = $this->makeOrderReference($cart->id);
        $nonce = (string) Str::uuid();
        $timestamp = now()->toIso8601String();

        $amount = round((float) $cart->grand_total, 2);

        $customerUrl = (string) $this->getConfigData('customer_url');
        if (! $customerUrl) {
            $customerUrl = config('app.url');
        }

        $callbackUrl = route('everypay.callback');

        $payload = [
            'api_username'     => $apiUsername,
            'account_name'     => $accountName,
            'amount'           => $amount,
            'order_reference'  => $orderReference,
            'nonce'            => $nonce,
            'timestamp'        => $timestamp,
            'customer_url'     => $customerUrl,
        ];

        $response = Http::asForm()
            ->withBasicAuth($apiUsername, $apiSecret)
            ->post(rtrim($baseUrl, '/').'/api/v4/payments/oneoff', $payload);

        if (! $response->successful()) {
            throw new \Exception('Everypay create payment failed: '.$response->body());
        }

        $data = $response->json();

        $paymentLink = $data['payment_link'] ?? null;
        $paymentReference = $data['payment_reference'] ?? null;

        if (! $paymentLink || ! $paymentReference) {
            throw new \Exception('Everypay response missing payment_link/payment_reference');
        }

        Cache::put($this->cacheKeyForOrderReference($orderReference), [
            'cart_id'           => $cart->id,
            'payment_reference' => $paymentReference,
        ], now()->addDay());

        Cache::put($this->cacheKeyForPaymentReference($paymentReference), [
            'cart_id'          => $cart->id,
            'order_reference'  => $orderReference,
        ], now()->addDay());

        return $paymentLink;
    }

    protected function makeOrderReference(int $cartId): string
    {
        return 'cart-'.$cartId.'-'.time();
    }

    protected function cacheKeyForOrderReference(string $orderReference): string
    {
        return 'everypay:order_reference:'.$orderReference;
    }

    protected function cacheKeyForPaymentReference(string $paymentReference): string
    {
        return 'everypay:payment_reference:'.$paymentReference;
    }
}
