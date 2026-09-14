<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\RestApi\Http\Controllers\V1\Shop\Catalog\ProductController as VendorProductController;

/**
 * Wraps Bagisto vendor's GET /api/v1/products (and /api/v1/products/{id}) so
 * customer-group catalog rule discounts are applied transparently. Vendor's
 * response shape is preserved exactly — only `price` and `formatted_price`
 * are reduced, and a `regular_price` field is added so the SPA can show a
 * strikethrough original.
 *
 * The Bearer-token → customer_group resolution mirrors what
 * CategoryProductController / SingleProductController already do.
 */
class ProductsListGroupPriceController extends Controller
{
    public function index(Request $request, VendorProductController $vendor): JsonResponse
    {
        // 1. Let vendor compute the full response (handles search/filters/pagination).
        $resourceCollection = $vendor->allResources($request);

        // 2. Resolve to an array we can mutate. JsonResource subclasses respond via
        //    response()->setData() during rendering — we replay that here.
        $rawResponse = $resourceCollection->toResponse($request)->getData(true);

        $items = $rawResponse['data'] ?? [];
        if (! is_array($items)) {
            return response()->json($rawResponse);
        }
        // Single-item endpoints sometimes return data as object — normalize.
        $isSingle = (! empty($items)) && ! array_is_list($items);
        if ($isSingle) {
            $items = [$items];
        }

        // 3. Look up customer group + applicable discount once.
        $groupId  = $this->resolveCustomerGroupId($request);
        $discount = $this->getActiveGroupDiscount($groupId);

        if ($discount > 0) {
            foreach ($items as $i => $item) {
                $items[$i] = $this->applyDiscount($item, $discount);
            }
        }

        $rawResponse['data'] = $isSingle ? ($items[0] ?? null) : $items;

        return response()->json($rawResponse);
    }

    private function applyDiscount(array $item, float $percent): array
    {
        $original = (float) ($item['price'] ?? 0);
        if ($original <= 0) return $item;

        $discounted = round($original * (1 - $percent / 100), 2);

        $item['regular_price']           = $original;
        $item['formatted_regular_price'] = core()->currency($original);
        $item['price']                   = $discounted;
        $item['formatted_price']         = core()->currency($discounted);

        return $item;
    }

    private function resolveCustomerGroupId(Request $request): int
    {
        try {
            if ($token = $request->bearerToken()) {
                $at = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                if ($at && $at->tokenable && ! empty($at->tokenable->customer_group_id)) {
                    return (int) $at->tokenable->customer_group_id;
                }
            }
            $user = auth('customer')->user();
            if ($user && ! empty($user->customer_group_id)) {
                return (int) $user->customer_group_id;
            }
        } catch (\Throwable $e) {
            // Fall through to guest.
        }

        return (int) (DB::table('customer_groups')->where('code', 'guest')->value('id') ?? 1);
    }

    /** Returns the largest applicable %-discount for this group, or 0. */
    private function getActiveGroupDiscount(int $groupId): float
    {
        $row = DB::table('catalog_rules')
            ->join('catalog_rule_customer_groups', 'catalog_rules.id', '=', 'catalog_rule_customer_groups.catalog_rule_id')
            ->where('catalog_rule_customer_groups.customer_group_id', $groupId)
            ->where('catalog_rules.status', 1)
            ->where('catalog_rules.action_type', 'by_percent')
            ->where(function ($q) {
                $q->whereNull('catalog_rules.starts_from')->orWhere('catalog_rules.starts_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('catalog_rules.ends_till')->orWhere('catalog_rules.ends_till', '>=', now());
            })
            ->orderByDesc('catalog_rules.discount_amount')
            ->first();

        return $row ? (float) $row->discount_amount : 0.0;
    }
}
