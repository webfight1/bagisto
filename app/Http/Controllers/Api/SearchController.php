<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use OpenApi\Attributes as OA;

class SearchController extends Controller
{
    #[OA\Get(
        path: '/api/v1/search',
        operationId: 'aiamaailmSearch',
        summary: 'Product search (name, SKU, short description)',
        description: 'SQL LIKE-based search over active, individually-visible products. Excludes variant children. Hard-capped at 50 results. Returns each product with optimized 200x200 webp image.',
        tags: ['Aiamaailm Search'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: false, description: 'Search query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'sku', type: 'string'),
                        new OA\Property(property: 'price', type: 'string'),
                        new OA\Property(property: 'special_price', type: 'string', nullable: true),
                        new OA\Property(property: 'url_key', type: 'string'),
                        new OA\Property(property: 'image', type: 'string', nullable: true),
                    ])),
                    new OA\Property(property: 'meta', type: 'object', properties: [
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(property: 'query', type: 'string'),
                    ]),
                ]
            )),
        ]
    )]
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
            ->select('product_id', 'name', 'sku', 'price', 'special_price', 'url_key', 'featured', 'new')
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
                'featured' => (bool) $product->featured,
                'new' => (bool) $product->new,
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
