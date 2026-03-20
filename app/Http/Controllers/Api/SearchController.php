<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        
        if (empty($query)) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'total' => 0,
                    'query' => $query,
                ]
            ]);
        }

        // Search products by name, sku, or description
        $products = DB::table('product_flat')
            ->where('status', 1)
            ->where('visible_individually', 1)
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('sku', 'LIKE', "%{$query}%")
                  ->orWhere('short_description', 'LIKE', "%{$query}%");
            })
            ->select('product_id', 'name', 'sku', 'price', 'special_price', 'url_key')
            ->groupBy('product_id')
            ->limit(50)
            ->get();

        $results = [];
        foreach ($products as $product) {
            // Get first product image
            $image = DB::table('product_images')
                ->where('product_id', $product->product_id)
                ->orderBy('position')
                ->first();

            $results[] = [
                'id' => $product->product_id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'special_price' => $product->special_price,
                'url_key' => $product->url_key,
                'image' => $image ? '/storage/' . $image->path : null,
            ];
        }

        return response()->json([
            'data' => $results,
            'meta' => [
                'total' => count($results),
                'query' => $query,
            ]
        ]);
    }
}
