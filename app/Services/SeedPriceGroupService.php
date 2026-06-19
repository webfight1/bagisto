<?php

namespace App\Services;

use App\Models\SeedPriceGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Webkul\Attribute\Models\Attribute;
use Webkul\Product\Models\Product;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;

class SeedPriceGroupService
{
    public function synchronizeProducts(SeedPriceGroup $group): void
    {
        if ($group->price === null) {
            return;
        }

        $priceAttributeId = Attribute::query()
            ->where('code', 'price')
            ->value('id');

        if (! $priceAttributeId) {
            return;
        }

        Product::query()
            ->where('seed_price_group_id', $group->id)
            ->select('id')
            ->chunkById(100, function ($products) use ($group, $priceAttributeId) {
                $productIds = $products->pluck('id');

                DB::table('product_attribute_values')
                    ->where('attribute_id', $priceAttributeId)
                    ->whereIn('product_id', $productIds)
                    ->update(['float_value' => $group->price]);

                foreach ($products as $product) {
                    Event::dispatch('catalog.product.update.after', $product->fresh());
                }
            });
    }

    public function snapshotOrderItem(OrderItem $orderItem): void
    {
        $group = $this->resolveGroupForOrderItem($orderItem);

        if (! $group) {
            return;
        }

        $orderItem->forceFill([
            'seed_price_group_id'         => $group->id,
            'seed_price_group_code'       => $group->code,
            'seed_price_group_name'       => $group->name,
            'seed_price_group_merit_code' => $group->merit_code,
            'seed_price_group_price'      => $group->price,
        ])->saveQuietly();
    }

    public function buildMeritRows(Order $order, mixed $taxId): array
    {
        $groups = [];
        $unassignedItemIds = [];

        foreach ($order->items->whereNull('parent_id') as $item) {
            $group = $this->groupDataForOrderItem($item);

            if (! $group) {
                $unassignedItemIds[] = $item->id;
                $group = [
                    'code'       => 'MAARAMATA',
                    'name'       => 'Hinnagrupp määramata',
                    'merit_code' => 'MAARAMATA',
                ];
            }

            $key = $group['code'];
            $quantity = (float) $item->qty_ordered;
            $lineTotal = (float) $item->total - (float) ($item->discount_amount ?? 0);

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    ...$group,
                    'quantity' => 0.0,
                    'total'    => 0.0,
                ];
            }

            $groups[$key]['quantity'] += $quantity;
            $groups[$key]['total'] += $lineTotal;
        }

        if ($unassignedItemIds) {
            Log::warning('Merit invoice contains products without a seed price group', [
                'order_id'       => $order->id,
                'order_item_ids' => $unassignedItemIds,
            ]);
        }

        return array_values(array_map(static function (array $group) use ($taxId) {
            $unitPrice = $group['quantity'] > 0
                ? round($group['total'] / $group['quantity'], 4)
                : 0;

            return [
                'Item' => [
                    'Code'        => substr($group['merit_code'] ?: $group['code'], 0, 20),
                    'Description' => $group['name'],
                    'Type'        => 3,
                    'UOMName'     => 'tk',
                ],
                'Quantity'       => $group['quantity'],
                'Price'          => $unitPrice,
                'DiscountPct'    => 0,
                'DiscountAmount' => 0.00,
                'TaxId'          => $taxId,
            ];
        }, $groups));
    }

    private function groupDataForOrderItem(OrderItem $item): ?array
    {
        if ($item->seed_price_group_code) {
            return [
                'code'       => $item->seed_price_group_code,
                'name'       => $item->seed_price_group_name ?: 'Hinnagrupp '.$item->seed_price_group_code,
                'merit_code' => $item->seed_price_group_merit_code ?: $item->seed_price_group_code,
            ];
        }

        $group = $this->resolveGroupForOrderItem($item);

        if (! $group) {
            return null;
        }

        return [
            'code'       => $group->code,
            'name'       => $group->name,
            'merit_code' => $group->merit_code,
        ];
    }

    private function resolveGroupForOrderItem(OrderItem $item): ?SeedPriceGroup
    {
        $productIds = collect([$item->product_id]);

        if ($item->relationLoaded('children')) {
            $productIds = $productIds->merge($item->children->pluck('product_id'));
        } else {
            $productIds = $productIds->merge($item->children()->pluck('product_id'));
        }

        $groupId = Product::query()
            ->whereIn('id', $productIds->filter()->unique())
            ->whereNotNull('seed_price_group_id')
            ->value('seed_price_group_id');

        return $groupId ? SeedPriceGroup::find($groupId) : null;
    }
}
