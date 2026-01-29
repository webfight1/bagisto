<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class CategoryProductController extends Controller
{
    public function index(Request $request, $slug)
    {
        $category = DB::table('category_translations')
            ->where('slug', $slug)
            ->first();

        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }

        $width = $request->query('width', 260);
        $height = $request->query('height', 220);
        $format = $request->query('format', 'webp');

        $products = DB::table('product_flat')
            ->join('product_categories', 'product_flat.product_id', '=', 'product_categories.product_id')
            ->leftJoin('product_images', function($join) {
                $join->on('product_flat.product_id', '=', 'product_images.product_id')
                    ->where('product_images.position', '=', 1);
            })
            ->where('product_categories.category_id', $category->category_id)
            ->where('product_flat.status', 1)
            ->where('product_flat.visible_individually', 1)
            ->select(
                'product_flat.product_id as id',
                'product_flat.name',
                'product_flat.sku',
                'product_flat.price',
                'product_flat.type',
                'product_flat.url_key',
                'product_images.path as original_image'
            )
            ->get();

        $products = $products->map(function($product) use ($width, $height, $format) {
            // Handle configurable products - get first variant price
            if ($product->type === 'configurable') {
                $firstVariant = DB::table('product_flat')
                    ->join('products', 'product_flat.product_id', '=', 'products.id')
                    ->where('products.parent_id', $product->id)
                    ->where('product_flat.status', 1)
                    ->orderBy('product_flat.product_id')
                    ->select('product_flat.price')
                    ->first();
                
                if ($firstVariant) {
                    $product->price = $firstVariant->price;
                }
            }
            
            if ($product->original_image) {
                $product->image = $this->getOptimizedImage($product->original_image, $width, $height, $format);
            } else {
                $product->image = null;
            }
            unset($product->original_image);
            unset($product->type);
            return $product;
        });

        return response()->json($products);
    }

    public function categories()
    {
        $categories = DB::table('category_translations')
            ->join('categories', 'category_translations.category_id', '=', 'categories.id')
            ->where('category_translations.locale', 'en')
            ->where('categories.status', 1)
            ->where('categories.display_mode', 'products')
            ->whereNotNull('categories.parent_id')
            ->select(
                'category_translations.category_id as id',
                'category_translations.name',
                'category_translations.slug',
                'category_translations.url_path',
                'categories.parent_id',
                'categories.position'
            )
            ->orderBy('categories.position')
            ->orderBy('category_translations.name')
            ->get();

        return response()->json($categories);
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
