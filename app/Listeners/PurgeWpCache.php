<?php

namespace App\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

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
        $productId = is_object($product) ? ($product->id ?? null) : $product;

        // Pre-generate WebP thumbnails for this specific product's images so
        // customers never hit an uncached image (and admins don't need to
        // remember to run `php artisan images:generate-cache` manually).
        if ($productId) {
            $this->generateImageCacheForProduct((int) $productId);
        }

        $this->purge('product-save', ['product_id' => $productId]);
    }

    /**
     * Pre-generate 80/260/496/992 WebP variants for every image of the given
     * product. Same output paths as GenerateProductImageCache command so the
     * frontend always finds a hit.
     */
    private function generateImageCacheForProduct(int $productId): void
    {
        try {
            $images = DB::table('product_images')
                ->where('product_id', $productId)
                ->orderBy('id')
                ->get(['path']);

            if ($images->isEmpty()) {
                return;
            }

            $sizes = [[80, 80], [260, 260], [496, 496], [992, 992]];
            foreach ($images as $image) {
                foreach ($sizes as [$w, $h]) {
                    $this->makeOptimizedWebp($image->path, $w, $h);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[PurgeWpCache] image cache generation failed', [
                'product_id' => $productId,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    private function makeOptimizedWebp(?string $originalPath, int $width, int $height): void
    {
        if (! $originalPath) {
            return;
        }

        $info = pathinfo($originalPath);
        $cachedPath = sprintf('cache/%s/%s_%dx%d.webp', $info['dirname'], $info['filename'], $width, $height);

        $disk = Storage::disk('public');
        if ($disk->exists($cachedPath) || ! $disk->exists($originalPath)) {
            return;
        }

        try {
            $img = Image::make($disk->path($originalPath))
                ->resize($width, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            if ($img->height() > $height) {
                $img->crop($width, $height, 0, 0);
            }
            $img->encode('webp', 90);

            $absolutePath = $disk->path($cachedPath);
            $dir = dirname($absolutePath);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $img->save($absolutePath);
        } catch (\Throwable $e) {
            // Silent — one bad image shouldn't block a whole product save.
        }
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
