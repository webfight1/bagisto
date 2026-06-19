<x-admin::layouts>
    <x-slot:title>
        Hinnagrupid
    </x-slot>

    <form method="POST" action="{{ route('admin.catalog.seed_price_groups.update') }}">
        @csrf
        @method('PUT')

        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <div>
                <p class="text-xl font-bold text-gray-800 dark:text-white">
                    Hinnagrupid
                </p>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Siin määratud hind sünkroniseeritakse kõigile sellesse gruppi kuuluvatele toodetele.
                </p>
            </div>

            @if (bouncer()->hasPermission('catalog.seed_price_groups.edit'))
                <button class="primary-button">
                    Salvesta hinnagrupid
                </button>
            @endif
        </div>

        <div class="box-shadow mt-7 overflow-x-auto rounded bg-white dark:bg-gray-900">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-gray-50 text-gray-600 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">Grupp</th>
                        <th class="px-4 py-3">Nimetus</th>
                        <th class="px-4 py-3">Hind (€)</th>
                        <th class="px-4 py-3">Meriti kood</th>
                        <th class="px-4 py-3">Tooteid</th>
                        <th class="px-4 py-3">Aktiivne</th>
                    </tr>
                </thead>

                <tbody class="divide-y dark:divide-gray-800">
                    @foreach ($groups as $group)
                        <tr class="text-gray-700 dark:text-gray-200">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold">
                                {{ $group->code }}
                            </td>

                            <td class="min-w-52 px-4 py-3">
                                <input
                                    type="text"
                                    name="groups[{{ $group->id }}][name]"
                                    value="{{ old("groups.{$group->id}.name", $group->name) }}"
                                    class="w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-800 dark:bg-gray-900"
                                    required
                                    @disabled(! bouncer()->hasPermission('catalog.seed_price_groups.edit'))
                                >
                                @error("groups.{$group->id}.name")
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </td>

                            <td class="min-w-40 px-4 py-3">
                                <input
                                    type="number"
                                    name="groups[{{ $group->id }}][price]"
                                    value="{{ old("groups.{$group->id}.price", $group->price) }}"
                                    min="0"
                                    step="0.01"
                                    placeholder="Määramata"
                                    class="w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-800 dark:bg-gray-900"
                                    @disabled(! bouncer()->hasPermission('catalog.seed_price_groups.edit'))
                                >
                                @error("groups.{$group->id}.price")
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </td>

                            <td class="min-w-44 px-4 py-3">
                                <input
                                    type="text"
                                    name="groups[{{ $group->id }}][merit_code]"
                                    value="{{ old("groups.{$group->id}.merit_code", $group->merit_code) }}"
                                    maxlength="20"
                                    class="w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm uppercase dark:border-gray-800 dark:bg-gray-900"
                                    required
                                    @disabled(! bouncer()->hasPermission('catalog.seed_price_groups.edit'))
                                >
                                @error("groups.{$group->id}.merit_code")
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </td>

                            <td class="whitespace-nowrap px-4 py-3">
                                {{ $group->products_count }}
                            </td>

                            <td class="px-4 py-3">
                                <input type="hidden" name="groups[{{ $group->id }}][is_active]" value="0">
                                <input
                                    type="checkbox"
                                    name="groups[{{ $group->id }}][is_active]"
                                    value="1"
                                    class="h-4 w-4"
                                    @checked((bool) old("groups.{$group->id}.is_active", $group->is_active))
                                    @disabled(! bouncer()->hasPermission('catalog.seed_price_groups.edit'))
                                >
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </form>
</x-admin::layouts>
