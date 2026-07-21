<?php

namespace App\Listeners;

use App\Services\MetaCapiService;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Contracts\Order;

/**
 * Fires Meta Conversions API Purchase event when an order is created.
 *
 * Client-side pixel already fires Purchase on the thank-you page. We reuse
 * order->increment_id as event_id in BOTH places so Meta deduplicates when
 * both signals arrive (typical case) or keeps only ours when the browser
 * signal is blocked (ad-blocker, iOS ITP, tab closed after Esto redirect).
 */
class SendMetaPurchase
{
    public function __construct(protected MetaCapiService $meta) {}

    public function handle(Order $order): void
    {
        try {
            $shipping = $order->shipping_address ?? $order->billing_address;
            $billing  = $order->billing_address;

            // Build content list from order items.
            $contents = [];
            $contentIds = [];
            $numItems = 0;
            foreach ($order->items as $item) {
                $qty = (int) $item->qty_ordered;
                $price = round((float) ($item->price ?? 0), 2);
                $sku = (string) ($item->product_id ?: ($item->sku ?: $item->id));
                $contents[] = ['id' => $sku, 'quantity' => $qty, 'item_price' => $price];
                $contentIds[] = $sku;
                $numItems += $qty;
            }

            $ok = $this->meta->sendPurchase([
                'event_id'         => (string) $order->increment_id,
                'event_source_url' => rtrim(config('app.url'), '/') . '/product/',
                'currency'         => $order->order_currency_code ?: 'EUR',
                'value'            => round((float) $order->grand_total, 2),
                'content_ids'      => $contentIds,
                'contents'         => $contents,
                'num_items'        => $numItems,
                'email'            => $order->customer_email,
                'first_name'       => $order->customer_first_name ?? ($billing?->first_name ?? null),
                'last_name'        => $order->customer_last_name ?? ($billing?->last_name ?? null),
                'phone'            => $billing?->phone ?? $shipping?->phone,
                'city'             => $billing?->city,
                'postcode'         => $billing?->postcode,
                'country'          => $billing?->country,
            ]);

            if (! $ok) {
                Log::warning('[SendMetaPurchase] send returned false', ['order_id' => $order->id]);
            }
        } catch (\Throwable $e) {
            // Never let CAPI break order creation.
            Log::warning('[SendMetaPurchase] failed', [
                'order_id' => $order->id ?? null,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
