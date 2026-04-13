<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SitemapController
{
    public function index(): JsonResponse
    {
        $products = DB::table('product_flat')
            ->select('url_key', 'updated_at')
            ->where('locale', 'et')
            ->where('status', 1)
            ->whereNotNull('url_key')
            ->whereNull('parent_id')
            ->orderBy('updated_at', 'desc')
            ->get();

        $categories = DB::table('categories')
            ->join('category_translations', 'categories.id', '=', 'category_translations.category_id')
            ->select('category_translations.slug', 'categories.updated_at')
            ->where('category_translations.locale', 'et')
            ->where('categories.status', 1)
            ->whereNotNull('category_translations.slug')
            ->where('category_translations.slug', '!=', 'root')
            ->get();

        return response()->json([
            'products'   => $products,
            'categories' => $categories,
        ]);
    }
}
