<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Kui Bagistos toode luuakse / muudetakse / kustutatakse, kutsume WordPress-i
 * endpointi mis kustutab kõik nailedit_* transient-id ja tühjendab WP Fastest
 * Cache. Nii kajastuvad muudatused kohe frondis (nailedit.ee) ilma manuaalse
 * cache-kustutamiseta.
 *
 * Config keys (config/services.php või .env):
 *   WP_CACHE_PURGE_URL   — nt https://nailedit.ee/wp-admin/admin-ajax.php
 *   WP_CACHE_PURGE_TOKEN — shared secret (sama väärtus mis WP options'is)
 */
class PurgeWpCache
{
    public function onProductSaved($product): void
    {
        $this->purge('product-save', ['product_id' => is_object($product) ? ($product->id ?? null) : $product]);
    }

    public function onProductDeleted($productId): void
    {
        $this->purge('product-delete', ['product_id' => $productId]);
    }

    public function onCategorySaved($category): void
    {
        $this->purge('category-save', ['category_id' => is_object($category) ? ($category->id ?? null) : $category]);
    }

    private function purge(string $reason, array $context = []): void
    {
        $url = config('services.wp_cache_purge.url') ?: env('WP_CACHE_PURGE_URL');
        $token = config('services.wp_cache_purge.token') ?: env('WP_CACHE_PURGE_TOKEN');

        if (! $url || ! $token) {
            return; // Not configured, no-op.
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->connectTimeout(3)
                ->post($url, [
                    'action' => 'nailedit_purge_cache',
                    'token'  => $token,
                    'reason' => $reason,
                ]);

            Log::info('[PurgeWpCache] triggered', array_merge([
                'reason'   => $reason,
                'status'   => $response->status(),
                'response' => substr($response->body(), 0, 200),
            ], $context));
        } catch (\Throwable $e) {
            Log::warning('[PurgeWpCache] failed', array_merge([
                'reason' => $reason,
                'error'  => $e->getMessage(),
            ], $context));
        }
    }
}
