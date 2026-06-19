<?php

namespace App\Listeners;

use App\Models\SeedPriceGroup;
use App\Services\SeedPriceGroupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Models\Attribute;
use Webkul\Sales\Models\OrderItem;

class SeedPriceGroupListener
{
    public function __construct(
        private readonly SeedPriceGroupService $service
    ) {}

    public function prepareProductPrice(): void
    {
        if (! request()->exists('seed_price_group_id')) {
            return;
        }

        $groupId = request()->input('seed_price_group_id');

        if ($groupId === null || $groupId === '') {
            return;
        }

        $group = SeedPriceGroup::query()
            ->whereKey($groupId)
            ->where('is_active', true)
            ->first();

        if (! $group) {
            throw ValidationException::withMessages([
                'seed_price_group_id' => 'Valitud hinnagruppi ei leitud või see ei ole aktiivne.',
            ]);
        }

        if ($group->price === null) {
            throw ValidationException::withMessages([
                'seed_price_group_id' => 'Valitud hinnagrupil puudub hind. Määra hind esmalt hinnagruppide tabelis.',
            ]);
        }

        $specialPrice = request()->input('special_price');

        if (
            $specialPrice !== null
            && $specialPrice !== ''
            && (float) $specialPrice >= (float) $group->price
        ) {
            throw ValidationException::withMessages([
                'special_price' => 'Soodushind peab olema hinnagrupi hinnast väiksem.',
            ]);
        }

        request()->merge(['price' => $group->price]);
    }

    public function saveProductGroup($product): void
    {
        if (! request()->exists('seed_price_group_id')) {
            return;
        }

        $groupId = request()->input('seed_price_group_id') ?: null;

        DB::table('products')
            ->where('id', $product->id)
            ->update(['seed_price_group_id' => $groupId]);

        if (! $groupId) {
            return;
        }

        $group = SeedPriceGroup::find($groupId);
        $priceAttributeId = Attribute::query()
            ->where('code', 'price')
            ->value('id');

        if (! $group || $group->price === null || ! $priceAttributeId) {
            return;
        }

        DB::table('product_attribute_values')
            ->where('product_id', $product->id)
            ->where('attribute_id', $priceAttributeId)
            ->update(['float_value' => $group->price]);

        $product->refresh();
    }

    public function snapshotOrderItem(OrderItem $orderItem): void
    {
        $this->service->snapshotOrderItem($orderItem);
    }
}
