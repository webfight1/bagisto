<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class SingleProductController extends Controller
{
    /**
     * Active catalog rules for the current customer group, cached per request.
     * Each entry: ['action_type', 'discount_amount', 'end_other_rules'].
     */
    private ?array $activeCatalogRules = null;

    public function show(Request $request, $slug)
    {
        // Increase memory limit for image processing
        ini_set('memory_limit', '512M');
        set_time_limit(60);

        $customerGroupId = $this->resolveCustomerGroupId($request);
        $this->loadActiveCatalogRules($customerGroupId);

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
                    'medium' => $this->getOptimizedImage($img->path, 496, 496, 'webp'),
                    'large' => $this->getOptimizedImage($img->path, 992, 992, 'webp')
                ];
            })->toArray();

        // Get product videos
        $videos = DB::table('product_videos')
            ->where('product_id', $product->product_id)
            ->orderBy('position')
            ->get()
            ->map(function($video) {
                return [
                    'id' => $video->id,
                    'type' => $video->type,
                    'position' => $video->position,
                    'path' => '/storage/' . $video->path,
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

            // Group by product_id: pick best base row
            $variantData = [];
            foreach ($variantFlatRows as $row) {
                $pid = $row->product_id;
                if (!isset($variantData[$pid])) {
                    $variantData[$pid] = ['base' => $row];
                }
                // Prefer the row that has a name for base data
                if ($row->name && !$variantData[$pid]['base']->name) {
                    $variantData[$pid]['base'] = $row;
                }
            }

            // Get variant name translations from product_attribute_values
            $variantNameRows = DB::table('product_attribute_values')
                ->join('attributes', 'product_attribute_values.attribute_id', '=', 'attributes.id')
                ->whereIn('product_attribute_values.product_id', $variantIds)
                ->where('attributes.code', 'name')
                ->whereNotNull('product_attribute_values.locale')
                ->select(
                    'product_attribute_values.product_id',
                    'product_attribute_values.locale',
                    'product_attribute_values.text_value'
                )
                ->get();

            // Attach translations to variantData
            foreach ($variantNameRows as $row) {
                if (isset($variantData[$row->product_id])) {
                    $variantData[$row->product_id]['translations'][$row->locale] = $row->text_value;
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
                            'medium' => $this->getOptimizedImage($img->path, 496, 496, 'webp'),
                            'large' => $this->getOptimizedImage($img->path, 992, 992, 'webp')
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

                // Get variant inventory
                $variantInventory = DB::table('product_inventory_indices')
                    ->where('product_id', $pid)
                    ->where('channel_id', 1)
                    ->value('qty') ?? 0;

                $variants[] = [
                    'id' => $pid,
                    'name' => $base->name,
                    'sku' => $base->sku,
                    'price' => $this->applyCatalogRules((float) $base->price),
                    'regular_price' => $base->price,
                    'special_price' => $base->special_price !== null
                        ? $this->applyCatalogRules((float) $base->special_price)
                        : null,
                    'url_key' => $base->url_key,
                    'inventory' => $variantInventory,
                    'images' => $variantImages->toArray(),
                    'attributes' => array_values($varAttrsGrouped),
                    'translations' => $data['translations'] ?? []
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

        // Get prices from product_attribute_values (the correct source)
        $prices = $this->getProductPrices($product->product_id);

        // Get inventory/stock for the product
        $inventory = DB::table('product_inventory_indices')
            ->where('product_id', $product->product_id)
            ->where('channel_id', 1) // Default channel
            ->value('qty') ?? 0;

        // Get related products (same format as /v1/products list — used by WP products view)
        $relatedProducts = $this->getRelatedProducts($product->product_id);

        // Configurable products have no own price — use minimum variant price.
        $regularPrice = (float) $prices['price'];
        if ($regularPrice <= 0 && $product->type === 'configurable' && !empty($variants)) {
            $variantPrices = array_filter(array_column($variants, 'regular_price'));
            if (!empty($variantPrices)) {
                $regularPrice = (float) min($variantPrices);
            }
        }

        $finalPrice = $this->applyCatalogRules($regularPrice);
        $finalSpecialPrice = $prices['special_price'] !== null
            ? $this->applyCatalogRules((float) $prices['special_price'])
            : null;

        return response()->json([
            'id' => $product->product_id,
            'name' => $product->name,
            'sku' => $product->sku,
            'type' => $product->type,
            'price' => $finalPrice,
            'regular_price' => $regularPrice,
            'special_price' => $finalSpecialPrice,
            'customer_group_id' => $customerGroupId,
            'url_key' => $product->url_key,
            'description' => $product->description,
            'short_description' => $product->short_description,
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
            'inventory' => $inventory,
            'images' => $images,
            'videos' => $videos,
            'attributes' => $attributes,
            'variants' => $variants,
            'categories' => $categories,
            'translations' => $translations,
            'related_products' => $relatedProducts,
        ]);
    }

    /**
     * Get related products in the same format as /v1/products list endpoint.
     * Returns id, name, sku, price, special_price, url_key, description,
     * base_image and images (with small/medium/large/original URLs).
     */
    private function getRelatedProducts($productId): array
    {
        // Relation ids from product_relations pivot (parent_id -> child_id)
        $relatedIds = DB::table('product_relations')
            ->where('parent_id', $productId)
            ->pluck('child_id');

        if ($relatedIds->isEmpty()) {
            return [];
        }

        // Get base flat rows (one per product, prefer locale that has a name)
        $flatRows = DB::table('product_flat')
            ->whereIn('product_id', $relatedIds)
            ->where('status', 1)
            ->select('product_id', 'locale', 'name', 'sku', 'price', 'special_price', 'url_key', 'description', 'short_description')
            ->get();

        // Group by product_id, prefer row with a name
        $byProduct = [];
        foreach ($flatRows as $row) {
            $pid = $row->product_id;
            if (!isset($byProduct[$pid])) {
                $byProduct[$pid] = $row;
            } elseif ($row->name && !$byProduct[$pid]->name) {
                $byProduct[$pid] = $row;
            }
        }

        $result = [];
        foreach ($byProduct as $pid => $row) {
            $imageRows = DB::table('product_images')
                ->where('product_id', $pid)
                ->orderBy('position')
                ->get();

            $images = [];
            foreach ($imageRows as $img) {
                $images[] = [
                    'id'                 => $img->id,
                    'path'               => $img->path,
                    'small_image_url'    => $this->getOptimizedImage($img->path, 200, 200, 'webp'),
                    'medium_image_url'   => $this->getOptimizedImage($img->path, 400, 400, 'webp'),
                    'large_image_url'    => $this->getOptimizedImage($img->path, 800, 800, 'webp'),
                    'original_image_url' => '/storage/' . $img->path,
                ];
            }

            $baseImage = !empty($images) ? [
                'small_image_url'    => $images[0]['small_image_url'],
                'medium_image_url'   => $images[0]['medium_image_url'],
                'large_image_url'    => $images[0]['large_image_url'],
                'original_image_url' => $images[0]['original_image_url'],
            ] : null;

            // Prefer price from product_attribute_values (correct source)
            $relPrices = $this->getProductPrices($pid);

            $baseRegular = (float) ($relPrices['price'] ?: $row->price);
            $baseSpecial = $relPrices['special_price'] ?: $row->special_price;

            $result[] = [
                'id'                => $pid,
                'name'              => $row->name,
                'sku'               => $row->sku,
                'price'             => $this->applyCatalogRules($baseRegular),
                'regular_price'     => $baseRegular,
                'special_price'     => $baseSpecial !== null
                    ? $this->applyCatalogRules((float) $baseSpecial)
                    : null,
                'url_key'           => $row->url_key,
                'description'       => $row->description,
                'short_description' => $row->short_description,
                'base_image'        => $baseImage,
                'images'            => $images,
            ];
        }

        return $result;
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

    /**
     * Get product prices from product_attribute_values table
     */
    private function getProductPrices($productId)
    {
        $priceAttributes = DB::table('product_attribute_values')
            ->join('attributes', 'product_attribute_values.attribute_id', '=', 'attributes.id')
            ->where('product_attribute_values.product_id', $productId)
            ->whereIn('attributes.code', ['price', 'special_price'])
            ->select('attributes.code', 'product_attribute_values.text_value', 'product_attribute_values.float_value')
            ->get()
            ->keyBy('code');

        return [
            'price' => $priceAttributes['price']->float_value ?? 0,
            'special_price' => $priceAttributes['special_price']->float_value ?? null,
        ];
    }

    /**
     * Resolve current customer group id from the request.
     * Tries Sanctum bearer token first, falls back to session customer guard,
     * and finally to the 'guest' group.
     */
    private function resolveCustomerGroupId(Request $request): int
    {
        try {
            // Resolve from Sanctum bearer token manually since this route
            // doesn't have the auth:sanctum middleware applied.
            $bearer = $request->bearerToken();
            if ($bearer) {
                $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($bearer);
                if ($accessToken && $accessToken->tokenable) {
                    $user = $accessToken->tokenable;
                    if (!empty($user->customer_group_id)) {
                        return (int) $user->customer_group_id;
                    }
                }
            }

            // Fallback to session-authenticated customer guard
            $user = auth('customer')->user();
            if ($user && !empty($user->customer_group_id)) {
                return (int) $user->customer_group_id;
            }
        } catch (\Throwable $e) {
            // Fall through to guest
        }

        $guestId = DB::table('customer_groups')->where('code', 'guest')->value('id');
        return (int) ($guestId ?? 1);
    }

    /**
     * Load active catalog rules for the customer group into a per-request cache.
     * Only loads rules with no per-product conditions — the gold/silver "X% off everything"
     * use case. Rules with conditions are skipped (their effect would need full condition eval).
     */
    private function loadActiveCatalogRules(int $customerGroupId, int $channelId = 1): void
    {
        $today = now()->toDateString();

        $rules = DB::table('catalog_rules')
            ->join('catalog_rule_customer_groups', 'catalog_rules.id', '=', 'catalog_rule_customer_groups.catalog_rule_id')
            ->join('catalog_rule_channels', 'catalog_rules.id', '=', 'catalog_rule_channels.catalog_rule_id')
            ->where('catalog_rules.status', 1)
            ->where('catalog_rule_customer_groups.customer_group_id', $customerGroupId)
            ->where('catalog_rule_channels.channel_id', $channelId)
            ->where(function ($q) use ($today) {
                $q->whereNull('catalog_rules.starts_from')
                  ->orWhere('catalog_rules.starts_from', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('catalog_rules.ends_till')
                  ->orWhere('catalog_rules.ends_till', '>=', $today);
            })
            ->orderBy('catalog_rules.sort_order')
            ->select(
                'catalog_rules.action_type',
                'catalog_rules.discount_amount',
                'catalog_rules.end_other_rules',
                'catalog_rules.conditions'
            )
            ->get();

        $this->activeCatalogRules = [];
        foreach ($rules as $rule) {
            // Only support unconditional rules for now (most common: "X% off everything for group Y")
            $conditions = $rule->conditions;
            if (!empty($conditions) && $conditions !== '[]' && $conditions !== 'null') {
                continue;
            }
            $this->activeCatalogRules[] = [
                'action_type'      => $rule->action_type,
                'discount_amount'  => (float) $rule->discount_amount,
                'end_other_rules'  => (bool) $rule->end_other_rules,
            ];
        }
    }

    /**
     * Apply currently loaded catalog rules to a base price.
     * Returns the discounted price (rounded to 2 decimals).
     * If no rules apply, returns the original price unchanged.
     */
    private function applyCatalogRules(?float $basePrice): ?float
    {
        if ($basePrice === null || $basePrice <= 0) {
            return $basePrice;
        }
        if (empty($this->activeCatalogRules)) {
            return $basePrice;
        }

        $price = (float) $basePrice;
        foreach ($this->activeCatalogRules as $rule) {
            switch ($rule['action_type']) {
                case 'by_percent':
                    $price = $price - ($price * $rule['discount_amount'] / 100);
                    break;
                case 'by_fixed':
                    $price = $price - $rule['discount_amount'];
                    break;
                case 'to_percent':
                    $price = $price * $rule['discount_amount'] / 100;
                    break;
                case 'to_fixed':
                    $price = $rule['discount_amount'];
                    break;
            }
            $price = max(0, $price);
            if ($rule['end_other_rules']) {
                break;
            }
        }

        return round($price, 2);
    }
}
