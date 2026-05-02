<?php

namespace Webkul\Product\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\Product\Helpers\Indexers\Flat as FlatIndexer;
use Webkul\Product\Repositories\ProductRepository;

class RefreshFlatIndex implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected int $productId) {}

    public function handle(ProductRepository $productRepository, FlatIndexer $flatIndexer): void
    {
        $product = $productRepository->with([
            'variants',
            'attribute_family',
            'attribute_values',
            'variants.attribute_family',
            'variants.attribute_values',
            'channels',
        ])->find($this->productId);

        if (! $product) {
            return;
        }

        $flatIndexer->refresh($product);
    }
}
