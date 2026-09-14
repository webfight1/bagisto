<?php

namespace App\Http\Controllers\Api;

use App\Models\Page;
use Illuminate\Http\JsonResponse;

class MenuController
{
    /**
     * GET /api/v1/menu
     *
     * Returns the top-navigation menu items, ordered. Each item:
     *   { slug, label, position }
     *
     * Only pages where status=1 AND in_menu=1 are included. `label` falls back
     * to page title when `menu_label` is empty. `position` is used by the
     * frontend to sort within the header.
     */
    public function index(): JsonResponse
    {
        $items = Page::query()
            ->where('status', true)
            ->where('in_menu', true)
            ->orderBy('menu_position')
            ->orderBy('title')
            ->get(['slug', 'title', 'menu_label', 'menu_position'])
            ->map(fn (Page $p) => [
                'slug'     => $p->slug,
                'label'    => $p->menu_label ?: $p->title,
                'position' => (int) $p->menu_position,
            ])
            ->values();

        return response()->json(['data' => $items]);
    }
}
