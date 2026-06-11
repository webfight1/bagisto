<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\ProductFlat;

class ProductPopularityController extends Controller
{
    /** Cached active catalog rules for the current customer group. */
    private ?array $activeCatalogRules = null;

    public function index(Request $request, $limit = 10)
    {
        $limit = max(1, min((int) $limit, 100));

        $customerGroupId = $this->resolveCustomerGroupId($request);
        $this->loadActiveCatalogRules($customerGroupId);

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
                $regularPrice = (float) $product->price;
                return [
                    'id'                => $product->id,
                    'sku'               => $product->sku,
                    'name'              => $product->name,
                    'short_description' => $product->short_description,
                    'price'             => $this->applyCatalogRules($regularPrice),
                    'regular_price'     => $regularPrice,
                    'total_qty'         => $popularProductTotals[$product->product_id] ?? 0,
                    'image_url'         => $product->image_path
                        ? url('storage/' . $product->image_path)
                        : null,
                    'url_key'           => $product->url_key,
                ];
            });

        return response()->json($products);
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
     * Load active catalog rules for the customer group.
     * Only unconditional rules are supported.
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
