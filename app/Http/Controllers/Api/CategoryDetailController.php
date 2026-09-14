<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CategoryDetailController
{
    /**
     * GET /api/v1/category/{slug}/detail
     *
     * Returns SEO-relevant category metadata:
     *   { slug, name, description, meta_title, meta_description, image, breadcrumb }
     *
     * `image` is either category.logo_path (if admin uploaded one) or the
     * first available product image in that category — so Facebook/Twitter
     * shares always have a preview picture.
     */
    public function show(string $slug): JsonResponse
    {
        $cat = DB::table('category_translations as ct')
            ->join('categories as c', 'c.id', '=', 'ct.category_id')
            ->where('ct.slug', $slug)
            ->where('c.status', 1)
            ->select(
                'c.id',
                'c.logo_path',
                'c.parent_id',
                'ct.slug',
                'ct.name',
                'ct.description',
                'ct.meta_title',
                'ct.meta_description',
                'ct.meta_keywords',
            )
            ->first();

        if (! $cat) {
            return response()->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        $image = $this->imageFor($cat);
        $breadcrumb = $this->breadcrumbFor((int) $cat->id);

        return response()->json([
            'slug'             => $cat->slug,
            'name'             => $cat->name,
            'description'      => $cat->description,
            'meta_title'       => $cat->meta_title ?: $cat->name.' — Aiamaailm.ee',
            'meta_description' => $cat->meta_description ?: $this->fallbackDescription($cat->name),
            'meta_keywords'    => $cat->meta_keywords,
            'image'            => $image,
            'breadcrumb'       => $breadcrumb,
        ]);
    }

    private function imageFor(object $cat): ?string
    {
        // 1) admin-uploaded category logo
        if (! empty($cat->logo_path)) {
            return url('storage/'.ltrim($cat->logo_path, '/'));
        }

        // 2) fall back to first product image in this category subtree
        $img = DB::table('product_images as pi')
            ->join('product_categories as pc', 'pc.product_id', '=', 'pi.product_id')
            ->join('product_flat as pf', 'pf.product_id', '=', 'pi.product_id')
            ->where('pc.category_id', $cat->id)
            ->where('pi.position', 1)
            ->where('pf.status', 1)
            ->where('pf.visible_individually', 1)
            ->orderBy('pi.id')
            ->value('pi.path');

        if ($img) {
            return url('storage/'.ltrim($img, '/'));
        }

        return null;
    }

    private function breadcrumbFor(int $categoryId): array
    {
        $crumbs = [];
        $currentId = $categoryId;
        $safety = 10;

        while ($currentId && $safety-- > 0) {
            $row = DB::table('categories as c')
                ->join('category_translations as ct', 'ct.category_id', '=', 'c.id')
                ->where('c.id', $currentId)
                ->select('c.parent_id', 'ct.slug', 'ct.name')
                ->first();

            if (! $row || $row->slug === 'root') break;

            array_unshift($crumbs, ['slug' => $row->slug, 'name' => $row->name]);
            $currentId = (int) $row->parent_id;
        }

        return $crumbs;
    }

    private function fallbackDescription(string $name): string
    {
        return "Kvaliteetsed ".mb_strtolower($name)." Aiamaailma e-poest. Väga hea idanevus, mõistlik hind, kiire saatmine üle Eesti.";
    }
}
