<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

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

        // Search products by name, sku, or description - exclude variant products
        $products = DB::table('product_flat')
            ->where('status', 1)
            ->where('visible_individually', 1)
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('sku', 'LIKE', "%{$query}%")
                  ->orWhere('short_description', 'LIKE', "%{$query}%");
            })
            ->whereNotIn('product_id', function($query) {
                // Exclude variant products (products that have a parent_id)
                $query->select('id')
                      ->from('products')
                      ->whereNotNull('parent_id');
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

            $optimizedImage = null;
            if ($image) {
                $optimizedImage = $this->getOptimizedImage($image->path, 200, 200, 'webp');
            }

            $results[] = [
                'id' => $product->product_id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'special_price' => $product->special_price,
                'url_key' => $product->url_key,
                'image' => $optimizedImage,
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

    protected function getOptimizedImage($imagePath, $width, $height, $format)
    {
        $sourcePath = storage_path('app/public/' . $imagePath);
        
        if (!file_exists($sourcePath)) {
            return null;
        }

        $pathInfo = pathinfo($imagePath);
        $cacheDir = 'cache/' . $pathInfo['dirname'];
        $cacheName = $pathInfo['filename'] . "_{$width}x{$height}.{$format}";
        $cachePath = $cacheDir . '/' . $cacheName;
        $cacheFullPath = storage_path('app/public/' . $cachePath);

        if (!file_exists($cacheFullPath)) {
            if (!file_exists(dirname($cacheFullPath))) {
                mkdir(dirname($cacheFullPath), 0755, true);
            }

            $img = Image::make($sourcePath);
            
            // Resize and crop from top to preserve upper part of image
            $img->resize($width, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            if ($img->height() > $height) {
                $img->crop($width, $height, 0, 0);
            }

            if ($format === 'webp') {
                $img->encode('webp', 85);
            } elseif ($format === 'jpg' || $format === 'jpeg') {
                $img->encode('jpg', 85);
            } elseif ($format === 'png') {
                $img->encode('png');
            }

            $img->save($cacheFullPath);
        }

        return '/storage/' . $cachePath;
    }
}
