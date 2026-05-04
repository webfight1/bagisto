<?php

namespace Webkul\Product\Listeners;

use Illuminate\Support\Facades\Bus;
use Webkul\Product\Helpers\Indexers\Flat as FlatIndexer;
use Webkul\Product\Jobs\ElasticSearch\DeleteIndex as DeleteElasticSearchIndexJob;
use Webkul\Product\Jobs\ElasticSearch\UpdateCreateIndex as UpdateCreateElasticSearchIndexJob;
use Webkul\Product\Jobs\RefreshFlatIndex as RefreshFlatIndexJob;
use Webkul\Product\Jobs\UpdateCreateInventoryIndex as UpdateCreateInventoryIndexJob;
use Webkul\Product\Jobs\UpdateCreatePriceIndex as UpdateCreatePriceIndexJob;
use Webkul\Product\Repositories\ProductBundleOptionProductRepository;
use Webkul\Product\Repositories\ProductGroupedProductRepository;
use Webkul\Product\Repositories\ProductRepository;

class Product
{
    /**
     * Create a new listener instance.
     *
     * @return void
     */
    public function __construct(
        protected ProductRepository $productRepository,
        protected ProductBundleOptionProductRepository $productBundleOptionProductRepository,
        protected ProductGroupedProductRepository $productGroupedProductRepository,
        protected FlatIndexer $flatIndexer
    ) {}

    /**
     * Update or create product indices
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return void
     */
    public function afterCreate($product)
    {
        $this->flatIndexer->refresh($product);

        $productIds = $this->getAllRelatedProductIds($product);

        UpdateCreateElasticSearchIndexJob::dispatch($productIds);
    }

    /**
     * Update or create product indices
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return void
     */
    public function afterUpdate($product)
    {
        $startTime = microtime(true);
        \Log::info('[PERF] Product listener afterUpdate started', ['product_id' => $product->id]);

        RefreshFlatIndexJob::dispatch($product->id);
        $t1 = microtime(true);
        \Log::info('[PERF] RefreshFlatIndexJob dispatched', ['time' => round(($t1 - $startTime) * 1000, 2) . 'ms']);

        $productIds = $this->getAllRelatedProductIds($product);
        $t2 = microtime(true);
        \Log::info('[PERF] getAllRelatedProductIds completed', ['time' => round(($t2 - $t1) * 1000, 2) . 'ms', 'product_count' => count($productIds)]);

        Bus::chain([
            new UpdateCreateInventoryIndexJob($productIds),
            new UpdateCreatePriceIndexJob($productIds),
            new UpdateCreateElasticSearchIndexJob($productIds),
        ])->dispatch();
        $t3 = microtime(true);
        \Log::info('[PERF] Job chain dispatched', ['time' => round(($t3 - $t2) * 1000, 2) . 'ms']);

        $totalTime = microtime(true);
        \Log::info('[PERF] Product listener afterUpdate completed', ['total_time' => round(($totalTime - $startTime) * 1000, 2) . 'ms']);
    }

    /**
     * Delete product indices
     *
     * @param  int  $productId
     * @return void
     */
    public function beforeDelete($productId)
    {
        if (core()->getConfigData('catalog.products.search.engine') != 'elastic') {
            return;
        }

        $product = $this->productRepository->find($productId);

        if (! $product) {
            return;
        }

        $productIds = $this->getAllRelatedProductIds($product);

        DeleteElasticSearchIndexJob::dispatch($productIds);
    }

    /**
     * Returns parents bundle product ids associated with simple product
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return array
     */
    public function getAllRelatedProductIds($product)
    {
        $productIds = [$product->id];

        if ($product->type == 'simple') {
            if ($product->parent_id) {
                $productIds[] = $product->parent_id;
            }

            $productIds = array_merge(
                $productIds,
                $this->getParentBundleProductIds($product),
                $this->getParentGroupProductIds($product)
            );
        } elseif ($product->type == 'configurable') {
            $productIds = [
                ...$product->variants->pluck('id')->toArray(),
                ...$productIds,
            ];
        }

        return $productIds;
    }

    /**
     * Returns parents bundle product ids associated with simple product
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return array
     */
    public function getParentBundleProductIds($product)
    {
        $bundleOptionProducts = $this->productBundleOptionProductRepository->findWhere([
            'product_id' => $product->id,
        ]);

        $productIds = [];

        foreach ($bundleOptionProducts as $bundleOptionProduct) {
            $productIds[] = $bundleOptionProduct->bundle_option->product_id;
        }

        return $productIds;
    }

    /**
     * Returns parents group product ids associated with simple product
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return array
     */
    public function getParentGroupProductIds($product)
    {
        $groupedOptionProducts = $this->productGroupedProductRepository->findWhere([
            'associated_product_id' => $product->id,
        ]);

        return $groupedOptionProducts->pluck('product_id')->toArray();
    }
}
