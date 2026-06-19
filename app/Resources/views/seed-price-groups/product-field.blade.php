@php
    $priceGroups = \App\Models\SeedPriceGroup::query()
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->get();

    $selectedGroupId = old('seed_price_group_id', $product->seed_price_group_id);
@endphp

<div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
        Seemnete hinnagrupp
    </p>

    <x-admin::form.control-group class="last:!mb-0">
        <x-admin::form.control-group.label>
            Hinnagrupp
        </x-admin::form.control-group.label>

        <select
            id="seed_price_group_id"
            name="seed_price_group_id"
            class="w-full rounded-md border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
        >
            <option value="">Hinnagrupp puudub</option>

            @foreach ($priceGroups as $group)
                <option
                    value="{{ $group->id }}"
                    data-price="{{ $group->price !== null ? number_format((float) $group->price, 4, '.', '') : '' }}"
                    @selected((string) $selectedGroupId === (string) $group->id)
                >
                    {{ $group->code }} — {{ $group->name }}
                    ({{ $group->price !== null ? number_format((float) $group->price, 2, ',', ' ') . ' €' : 'hind määramata' }})
                </option>
            @endforeach
        </select>

        <p class="mt-2 text-xs text-gray-500">
            Valimisel asendab hinnagrupi hind toote tavahinna. Grupi hinda saab muuta menüüs Kataloog → Hinnagrupid.
        </p>

        <x-admin::form.control-group.error control-name="seed_price_group_id" />
    </x-admin::form.control-group>
</div>

@pushOnce('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const priceGroupSelect = document.getElementById('seed_price_group_id');

            if (! priceGroupSelect) {
                return;
            }

            priceGroupSelect.addEventListener('change', () => {
                const selectedOption = priceGroupSelect.options[priceGroupSelect.selectedIndex];
                const price = selectedOption?.dataset.price;
                const priceInput = document.getElementById('price')
                    ?? document.querySelector('input[name="price"]');

                if (! priceInput || price === undefined || price === '') {
                    return;
                }

                priceInput.value = Number(price).toFixed(2);
                priceInput.dispatchEvent(new Event('input', { bubbles: true }));
                priceInput.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    </script>
@endPushOnce
