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
            // Get unique variant product IDs
            $variantIds = DB::table('products')
                ->where('parent_id', $product->product_id)
                ->pluck('id');

            // Get variant base data from product_flat (one row per variant, prefer locale with name)
            $variantFlatRows = DB::table('product_flat')
                ->whereIn('product_id', $variantIds)
                ->where('status', 1)
                ->select('product_id', 'locale', 'name', 'sku', 'price', 'special_price', 'url_key')
                ->get();

            // Group by product_id: pick best base row + collect translations
            $variantData = [];
            foreach ($variantFlatRows as $row) {
                $pid = $row->product_id;
                if (!isset($variantData[$pid])) {
                    $variantData[$pid] = ['base' => $row, 'translations' => []];
                }
                $variantData[$pid]['translations'][$row->locale] = $row->name;
                // Prefer the row that has a name for base data
                if ($row->name && !$variantData[$pid]['base']->name) {
                    $variantData[$pid]['base'] = $row;
                }
            }

            foreach ($variantData as $pid => $data) {
                $base = $data['base'];

                $variantImages = DB::table('product_images')
                    ->where('product_id', $pid)
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

                // Get variant attributes with all locale translations
                $varAttrRows = DB::table('product_attribute_values')
                    ->join('attributes', 'product_attribute_values.attribute_id', '=', 'attributes.id')
                    ->join('attribute_translations', 'attributes.id', '=', 'attribute_translations.attribute_id')
                    ->leftJoin('attribute_option_translations', function ($join) {
                        $join->on('product_attribute_values.integer_value', '=', 'attribute_option_translations.attribute_option_id')
                             ->on('attribute_translations.locale', '=', 'attribute_option_translations.locale');
                    })
                    ->where('product_attribute_values.product_id', $pid)
                    ->whereNotIn('attributes.code', ['sku', 'name', 'url_key', 'price', 'description', 'short_description', 'status', 'visible_individually', 'new', 'featured'])
                    ->select(
                        'attributes.code as attribute_code',
                        'attribute_translations.locale',
                        'attribute_translations.name as attribute_name',
                        'product_attribute_values.text_value',
                        'product_attribute_values.integer_value',
                        'attribute_option_translations.label as option_label'
                    )
                    ->get();

                $varAttrsGrouped = [];
                foreach ($varAttrRows as $attr) {
                    $code = $attr->attribute_code;
                    if (!isset($varAttrsGrouped[$code])) {
                        $varAttrsGrouped[$code] = [
                            'code' => $code,
                            'value' => $attr->option_label ?? $attr->text_value ?? $attr->integer_value,
                            'names' => [],
                            'values' => [],
                        ];
                    }
                    $varAttrsGrouped[$code]['names'][$attr->locale] = $attr->attribute_name;
                    if ($attr->option_label) {
                        $varAttrsGrouped[$code]['values'][$attr->locale] = $attr->option_label;
                    }
                }

                $variants[] = [
                    'id' => $pid,
                    'name' => $base->name,
                    'sku' => $base->sku,
                    'price' => $base->price,
                    'special_price' => $base->special_price,
                    'url_key' => $base->url_key,
                    'images' => $variantImages->toArray(),
                    'attributes' => array_values($varAttrsGrouped),
                    'translations' => $data['translations']
                ];
            }

            // If main product has no images, use first variant's images as fallback
            if (empty($images) && !empty($variants) && !empty($variants[0]['images'])) {
                $images = $variants[0]['images'];
            }
        }

        // Get translatable product fields from product_attribute_values
        $translatableCodes = ['name', 'description', 'short_description', 'meta_title', 'meta_description'];
        $translatableRows = DB::table('product_attribute_values')
            ->join('attributes', 'product_attribute_values.attribute_id', '=', 'attributes.id')
            ->where('product_attribute_values.product_id', $product->product_id)
            ->whereIn('attributes.code', $translatableCodes)
            ->whereNotNull('product_attribute_values.locale')
            ->select(
                'attributes.code',
                'product_attribute_values.locale',
                'product_attribute_values.text_value'
            )
            ->get();

        $translations = [];
        foreach ($translatableRows as $row) {
            if (!isset($translations[$row->locale])) {
                $translations[$row->locale] = array_fill_keys($translatableCodes, null);
            }
            $translations[$row->locale][$row->code] = $row->text_value;
        }

        // Get main product attributes with all locale translations
        $attributeRows = DB::table('product_attribute_values')
            ->join('attributes', 'product_attribute_values.attribute_id', '=', 'attributes.id')
            ->join('attribute_translations', 'attributes.id', '=', 'attribute_translations.attribute_id')
            ->leftJoin('attribute_option_translations', function ($join) {
                $join->on('product_attribute_values.integer_value', '=', 'attribute_option_translations.attribute_option_id')
                     ->on('attribute_translations.locale', '=', 'attribute_option_translations.locale');
            })
            ->where('product_attribute_values.product_id', $product->product_id)
            ->whereNotIn('attributes.code', ['sku', 'name', 'url_key', 'price', 'description', 'short_description', 'status', 'visible_individually', 'new', 'featured'])
            ->select(
                'attributes.code as attribute_code',
                'attribute_translations.locale',
                'attribute_translations.name as attribute_name',
                'product_attribute_values.text_value',
                'product_attribute_values.integer_value',
                'product_attribute_values.boolean_value',
                'attribute_option_translations.label as option_label'
            )
            ->get();

        // Group attributes by code, with per-locale name and option_label
        $attributesGrouped = [];
        foreach ($attributeRows as $attr) {
            $code = $attr->attribute_code;
            if (!isset($attributesGrouped[$code])) {
                $attributesGrouped[$code] = [
                    'code' => $code,
                    'value' => $attr->option_label ?? $attr->text_value ?? $attr->boolean_value ?? $attr->integer_value,
                    'names' => [],
                    'values' => [],
                ];
            }
            $attributesGrouped[$code]['names'][$attr->locale] = $attr->attribute_name;
            if ($attr->option_label) {
                $attributesGrouped[$code]['values'][$attr->locale] = $attr->option_label;
            }
        }
        $attributes = array_values($attributesGrouped);

        // Get categories this product belongs to (all locales)
        $categoryRows = DB::table('product_categories')
            ->join('category_translations', 'product_categories.category_id', '=', 'category_translations.category_id')
            ->where('product_categories.product_id', $product->product_id)
            ->select(
                'category_translations.category_id as id',
                'category_translations.locale',
                'category_translations.name',
                'category_translations.slug',
                'category_translations.url_path'
            )
            ->get();

        // Group by category id, with per-locale names
        $categoriesGrouped = [];
        foreach ($categoryRows as $cat) {
            if (!isset($categoriesGrouped[$cat->id])) {
                $categoriesGrouped[$cat->id] = [
                    'id' => $cat->id,
                    'slug' => $cat->slug,
                    'url_path' => $cat->url_path,
                    'names' => [],
                ];
            }
            $categoriesGrouped[$cat->id]['names'][$cat->locale] = $cat->name;
        }
        $categories = array_values($categoriesGrouped);

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
            'variants' => $variants,
            'categories' => $categories,
            'translations' => $translations
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
