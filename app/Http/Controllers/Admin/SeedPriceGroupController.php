<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeedPriceGroup;
use App\Services\SeedPriceGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeedPriceGroupController extends Controller
{
    public function index(): View
    {
        $groups = SeedPriceGroup::query()
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();

        return view('seed-price-groups::index', compact('groups'));
    }

    public function update(
        Request $request,
        SeedPriceGroupService $service
    ): RedirectResponse {
        $validated = $request->validate([
            'groups'              => ['required', 'array'],
            'groups.*.name'       => ['required', 'string', 'max:100'],
            'groups.*.price'      => ['nullable', 'numeric', 'min:0'],
            'groups.*.merit_code' => ['required', 'string', 'max:20'],
            'groups.*.is_active'  => ['required', 'boolean'],
        ]);

        foreach ($validated['groups'] as $id => $data) {
            $group = SeedPriceGroup::findOrFail($id);

            if (
                ($data['price'] === null || $data['price'] === '')
                && $group->products()->exists()
            ) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "groups.$id.price" => "Grupil {$group->code} on tooteid. Selle hinda ei saa tühjaks jätta.",
                    ]);
            }

            $oldPrice = $group->price;

            $group->update([
                'name'       => $data['name'],
                'price'      => $data['price'] === '' ? null : $data['price'],
                'merit_code' => $data['merit_code'],
                'is_active'  => (bool) $data['is_active'],
            ]);

            if ($group->price !== null && (string) $oldPrice !== (string) $group->price) {
                $service->synchronizeProducts($group);
            }
        }

        return back()->with('success', 'Hinnagrupid on salvestatud.');
    }
}
