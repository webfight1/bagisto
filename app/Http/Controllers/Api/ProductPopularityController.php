<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\ProductFlat;

class ProductPopularityController extends Controller
{
    public function index(Request $request, $limit = 10)
    {
        $limit = max(1, min((int) $limit, 100));

        // Get most popular actual products (variants, not parents)
        $popularProductTotals = DB::table('order_items')
            ->select('order_items.product_id', DB::raw('SUM(order_items.qty_ordered) as total_qty'))
            ->groupBy('order_items.product_id')
            ->orderByDesc('total_qty')
            ->pluck('total_qty', 'product_id')
            ->map(fn ($qty) => (int) $qty)
            ->toArray();

        $popularProductIds = array_keys($popularProductTotals);

        if (empty($popularProductIds)) {
            return response()->json([]);
        }

        $products = ProductFlat::query()
            ->leftJoin('product_images', function ($join) {
                $join->on('product_images.product_id', '=', 'product_flat.product_id')
                    ->whereRaw('product_images.id = (SELECT MIN(pi2.id) FROM product_images pi2 WHERE pi2.product_id = product_flat.product_id)');
            })
            ->whereIn('product_flat.product_id', $popularProductIds)
            ->where('product_flat.status', 1)
            ->where('product_flat.locale', 'et')
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
            ->whereNotNull('product_flat.price')
            ->get()
            ->sortByDesc(fn ($product) => $popularProductTotals[$product->product_id] ?? 0)
            ->take($limit)
            ->values()
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
