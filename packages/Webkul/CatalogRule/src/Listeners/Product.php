<?php

namespace Webkul\CatalogRule\Listeners;

use Webkul\CatalogRule\Jobs\UpdateCreateProductIndex as UpdateCreateProductIndexJob;

class Product
{
    /**
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return void
     */
    public function afterUpdate($product)
    {
        $startTime = microtime(true);
        \Log::info('[PERF] CatalogRule Product listener afterUpdate started', ['product_id' => $product->id]);

        UpdateCreateProductIndexJob::dispatch($product);

        $totalTime = microtime(true);
        \Log::info('[PERF] CatalogRule Product listener afterUpdate completed', ['total_time' => round(($totalTime - $startTime) * 1000, 2) . 'ms']);
    }
}
