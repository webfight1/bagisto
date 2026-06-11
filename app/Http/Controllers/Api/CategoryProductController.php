<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class CategoryProductController extends Controller
{
    /** Cached active catalog rules for the current customer group. */
    private ?array $activeCatalogRules = null;

    public function index(Request $request, $slug)
    {
        $customerGroupId = $this->resolveCustomerGroupId($request);
        $this->loadActiveCatalogRules($customerGroupId);

        $category = DB::table('category_translations')
            ->where('slug', $slug)
            ->first();

        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }

        $width = $request->query('width', 260);
        $height = $request->query('height', 260);
        $format = $request->query('format', 'webp');

        $products = DB::table('product_flat')
            ->join('product_categories', 'product_flat.product_id', '=', 'product_categories.product_id')
            ->join('products', 'products.id', '=', 'product_flat.product_id')
            ->leftJoin('product_images', function($join) {
                $join->on('product_flat.product_id', '=', 'product_images.product_id')
                    ->where('product_images.position', '=', 1);
            })
            ->where('product_categories.category_id', $category->category_id)
            ->where('product_flat.status', 1)
            ->where('product_flat.visible_individually', 1)
            ->whereIn('product_flat.locale', ['et', 'en'])
            ->whereNull('products.parent_id')
            ->select(
                'product_flat.product_id as id',
                'product_flat.name',
                'product_flat.sku',
                'product_flat.price',
                'product_flat.special_price',
                'product_flat.type',
                'product_flat.url_key',
                'product_images.path as original_image',
                DB::raw("FIELD(product_flat.locale, 'et', 'en') as locale_priority")
            )
            ->orderBy('locale_priority')
            ->get()
            ->unique('id');

        $products = $products->map(function($product) use ($width, $height, $format) {
            $product->is_configurable = $product->type === 'configurable';

            // Handle configurable products - get lowest price among all variants
            if ($product->is_configurable) {
                $lowestPrice = DB::table('product_flat')
                    ->join('products', 'product_flat.product_id', '=', 'products.id')
                    ->where('products.parent_id', $product->id)
                    ->where('product_flat.status', 1)
                    ->selectRaw('MIN(COALESCE(product_flat.special_price, product_flat.price)) as min_price')
                    ->value('min_price');

                if ($lowestPrice !== null) {
                    $product->price = $lowestPrice;
                    $product->special_price = null;
                }
            }

            // Apply customer-group catalog rule discount (e.g. -25% for gold customers)
            $regularPrice = $product->price !== null ? (float) $product->price : null;
            $regularSpecial = $product->special_price !== null ? (float) $product->special_price : null;
            $product->regular_price = $regularPrice;
            $product->price = $regularPrice !== null ? $this->applyCatalogRules($regularPrice) : null;
            $product->special_price = $regularSpecial !== null
                ? $this->applyCatalogRules($regularSpecial)
                : null;

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

    /**
     * Resolve current customer group id from the request.
     */
    private function resolveCustomerGroupId(Request $request): int
    {
        try {
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
     * Only rules with no per-product conditions are supported.
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
