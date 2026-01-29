<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class SingleProductController extends Controller
{
    public function show(Request $request, $slug)
    {
        // Increase memory limit for image processing
        ini_set('memory_limit', '512M');
        set_time_limit(60);

        // Get main product by url_key
        $product = DB::table('product_flat')
            ->where('url_key', $slug)
            ->where('status', 1)
            ->first();

            
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        

        // Get product images
        $images = DB::table('product_images')
            ->where('product_id', $product->product_id)
            ->orderBy('position')
            ->get()
            ->map(function($img) {
                return [
                    'id' => $img->id,
                    'position' => $img->position,
                    'original' => '/storage/' . $img->path,
                    'thumbnail' => $this->getOptimizedImage($img->path, 80, 80, 'webp'),
                    'large' => $this->getOptimizedImage($img->path, 800, 800, 'webp')
                ];
            })->toArray();

        // Get variants if this is a configurable product
        $variants = [];
        if ($product->type === 'configurable') {
            // Use products table for parent_id relationship, then join product_flat for details
            $variantProducts = DB::table('products')
                ->join('product_flat', 'products.id', '=', 'product_flat.product_id')
                ->where('products.parent_id', $product->product_id)
                ->where('product_flat.status', 1)
                ->select('product_flat.*')
                ->get();

            foreach ($variantProducts as $variant) {
                $variantImages = DB::table('product_images')
                    ->where('product_id', $variant->product_id)
                    ->orderBy('position')
                    ->get()
                    ->map(function($img) {
                        return [
                            'id' => $img->id,
                            'position' => $img->position,
                            'original' => '/storage/' . $img->path,
                            'thumbnail' => $this->getOptimizedImage($img->path, 80, 80, 'webp'),
                            'large' => $this->getOptimizedImage($img->path, 800, 800, 'webp')
                        ];
                    });

                // Get variant attributes (only custom/visible ones, exclude system attributes)
                $attributes = DB::table('product_attribute_values')
                    ->join('attributes', 'product_attribute_values.attribute_id', '=', 'attributes.id')
                    ->join('attribute_translations', 'attributes.id', '=', 'attribute_translations.attribute_id')
                    ->leftJoin('attribute_option_translations', 'product_attribute_values.integer_value', '=', 'attribute_option_translations.attribute_option_id')
                    ->where('product_attribute_values.product_id', $variant->product_id)
                    ->whereNotIn('attributes.code', ['sku', 'name', 'url_key', 'price', 'description', 'short_description', 'status', 'visible_individually', 'new', 'featured'])
                    ->select(
                        'attributes.code as attribute_code',
                        'attribute_translations.name as attribute_name',
                        'product_attribute_values.text_value',
                        'product_attribute_values.integer_value',
                        'attribute_option_translations.label as option_label'
                    )
                    ->get()
                    ->map(function($attr) {
                        return [
                            'code' => $attr->attribute_code,
                            'name' => $attr->attribute_name,
                            'value' => $attr->option_label ?? $attr->text_value ?? $attr->integer_value
                        ];
                    })->toArray();

                $variants[] = [
                    'id' => $variant->product_id,
                    'name' => $variant->name,
                    'sku' => $variant->sku,
                    'price' => $variant->price,
                    'special_price' => $variant->special_price,
                    'url_key' => $variant->url_key,
                    'images' => $variantImages->toArray(),
                    'attributes' => $attributes
                ];
            }

            // If main product has no images, use first variant's images as fallback
            if (empty($images) && !empty($variants) && !empty($variants[0]['images'])) {
                $images = $variants[0]['images'];
            }
        }

        // Get main product attributes (exclude system attributes)
        $attributes = DB::table('product_attribute_values')
            ->join('attributes', 'product_attribute_values.attribute_id', '=', 'attributes.id')
            ->join('attribute_translations', 'attributes.id', '=', 'attribute_translations.attribute_id')
            ->leftJoin('attribute_option_translations', 'product_attribute_values.integer_value', '=', 'attribute_option_translations.attribute_option_id')
            ->where('product_attribute_values.product_id', $product->product_id)
            ->whereNotIn('attributes.code', ['sku', 'name', 'url_key', 'price', 'description', 'short_description', 'status', 'visible_individually', 'new', 'featured'])
            ->select(
                'attributes.code as attribute_code',
                'attribute_translations.name as attribute_name',
                'product_attribute_values.text_value',
                'product_attribute_values.integer_value',
                'product_attribute_values.boolean_value',
                'attribute_option_translations.label as option_label'
            )
            ->get()
            ->map(function($attr) {
                return [
                    'code' => $attr->attribute_code,
                    'name' => $attr->attribute_name,
                    'value' => $attr->option_label ?? $attr->text_value ?? $attr->boolean_value ?? $attr->integer_value
                ];
            })->toArray();

        return response()->json([
            'id' => $product->product_id,
            'name' => $product->name,
            'sku' => $product->sku,
            'type' => $product->type,
            'price' => $product->price,
            'special_price' => $product->special_price,
            'url_key' => $product->url_key,
            'description' => $product->description,
            'short_description' => $product->short_description,
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
            'images' => $images,
            'attributes' => $attributes,
            'variants' => $variants
        ]);
    }

    private function getOptimizedImage($originalPath, $width, $height, $format)
    {
        if (!$originalPath) {
            return null;
        }

        $pathInfo = pathinfo($originalPath);
        $filename = $pathInfo['filename'];
        $directory = $pathInfo['dirname'];

        $cachedFilename = "{$filename}_{$width}x{$height}.{$format}";
        $cachedPath = "cache/{$directory}/{$cachedFilename}";

        if (Storage::disk('public')->exists($cachedPath)) {
            return "/storage/{$cachedPath}";
        }

        if (!Storage::disk('public')->exists($originalPath)) {
            return null;
        }

        try {
            $fullPath = Storage::disk('public')->path($originalPath);
            $image = Image::make($fullPath);

            // Resize and crop from top to preserve upper part of image
            $image->resize($width, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            if ($image->height() > $height) {
                $image->crop($width, $height, 0, 0);
            }

            if ($format === 'webp') {
                $image->encode('webp', 90);
            } elseif ($format === 'jpg' || $format === 'jpeg') {
                $image->encode('jpg', 90);
            } elseif ($format === 'png') {
                $image->encode('png');
            }

            $cachedFullPath = Storage::disk('public')->path($cachedPath);
            $cachedDirectory = dirname($cachedFullPath);

            if (!file_exists($cachedDirectory)) {
                mkdir($cachedDirectory, 0755, true);
            }

            $image->save($cachedFullPath);

            return "/storage/{$cachedPath}";
        } catch (\Exception $e) {
            return "/storage/{$originalPath}";
        }
    }
}
