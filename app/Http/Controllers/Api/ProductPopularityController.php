<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\ProductFlat;

class ProductPopularityController extends Controller
{
    public function index(Request $request, $minQty = 20)
    {
        $minQty = (int) $minQty;

        $popularProductTotals = DB::table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->select(DB::raw('COALESCE(products.parent_id, products.id) as popular_product_id'), DB::raw('SUM(order_items.qty_ordered) as total_qty'))
            ->groupBy('popular_product_id')
            ->having('total_qty', '>=', $minQty)
            ->pluck('total_qty', 'popular_product_id')
            ->map(fn ($qty) => (int) $qty)
            ->toArray();

        $popularProductIds = array_keys($popularProductTotals);

        if (empty($popularProductIds)) {
            return response()->json([]);
        }

        $products = ProductFlat::query()
            ->leftJoin('product_images', 'product_images.product_id', '=', 'product_flat.product_id')
            ->whereIn('product_flat.product_id', $popularProductIds)
            ->where('product_flat.status', 1)
            ->where('product_flat.visible_individually', 1)
            ->select([
                'product_flat.id',
                'product_flat.product_id',
                'product_flat.sku',
                'product_flat.name',
                'product_flat.short_description',
                'product_flat.price',
                'product_flat.url_key',
                'product_images.path as image_path',
            ])
            ->groupBy(
                'product_flat.id',
                'product_flat.product_id',
                'product_flat.sku',
                'product_flat.name',
                'product_flat.short_description',
                'product_flat.price',
                'product_flat.url_key',
                'product_images.path'
            )
            ->get()
            ->map(function ($product) use ($popularProductTotals) {
                return [
                    'id'                => $product->id,
                    'sku'               => $product->sku,
                    'name'              => $product->name,
                    'short_description' => $product->short_description,
                    'price'             => $product->price,
                    'total_qty'         => $popularProductTotals[$product->product_id] ?? 0,
                    'image_url'         => $product->image_path
                        ? url('storage/' . $product->image_path)
                        : null,
                    'url_key'           => $product->url_key,
                ];
            });

        return response()->json($products);
    }
}
