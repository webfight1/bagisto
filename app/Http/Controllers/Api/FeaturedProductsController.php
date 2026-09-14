<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use OpenApi\Attributes as OA;

class FeaturedProductsController extends Controller
{
    #[OA\Get(
        path: '/api/v1/featured-products',
        operationId: 'aiamaailmFeaturedProducts',
        summary: 'Esiletoodud tooted (Bagisto admin Featured = on)',
        description: 'Returns products with the Bagisto admin `featured=1` flag set. Excludes variant children and only includes individually visible, active products. Image is rendered/cached at requested size/format.',
        tags: ['Aiamaailm Catalog'],
        parameters: [
            new OA\Parameter(name: 'limit',  in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 12, maximum: 50)),
            new OA\Parameter(name: 'width',  in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 260)),
            new OA\Parameter(name: 'height', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 260)),
            new OA\Parameter(name: 'format', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['webp', 'jpg', 'png'], default: 'webp')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'id',                type: 'integer'),
                    new OA\Property(property: 'name',              type: 'string'),
                    new OA\Property(property: 'sku',               type: 'string'),
                    new OA\Property(property: 'price',             type: 'string'),
                    new OA\Property(property: 'special_price',     type: 'string', nullable: true),
                    new OA\Property(property: 'url_key',           type: 'string'),
                    new OA\Property(property: 'short_description', type: 'string', nullable: true),
                    new OA\Property(property: 'featured',          type: 'boolean'),
                    new OA\Property(property: 'new',               type: 'boolean'),
                    new OA\Property(property: 'image',             type: 'string', nullable: true),
                ])),
            ])),
        ]
    )]
    public function index(Request $request)
    {
        $limit  = min(50, max(1, (int) $request->query('limit', 12)));
        $width  = (int) $request->query('width', 260);
        $height = (int) $request->query('height', 260);
        $format = $request->query('format', 'webp');

        $rows = DB::table('product_flat')
            ->join('products', 'products.id', '=', 'product_flat.product_id')
            ->leftJoin('product_images', function ($join) {
                $join->on('product_flat.product_id', '=', 'product_images.product_id')
                    ->where('product_images.position', '=', 1);
            })
            ->where('product_flat.featured', 1)
            ->where('product_flat.status', 1)
            ->where('product_flat.visible_individually', 1)
            ->whereNull('products.parent_id')
            ->whereIn('product_flat.locale', ['et', 'en'])
            ->select(
                'product_flat.product_id as id',
                'product_flat.name',
                'product_flat.sku',
                'product_flat.price',
                'product_flat.special_price',
                'product_flat.url_key',
                'product_flat.short_description',
                'product_flat.featured',
                'product_flat.new',
                'product_images.path as original_image',
                DB::raw("FIELD(product_flat.locale, 'et', 'en') as locale_priority")
            )
            ->orderBy('locale_priority')
            ->orderBy('product_flat.updated_at', 'desc')
            ->limit($limit * 3)
            ->get()
            ->unique('id')
            ->take($limit)
            ->values();

        $data = $rows->map(function ($p) use ($width, $height, $format) {
            $p->featured = (bool) $p->featured;
            $p->new      = (bool) $p->new;
            $p->image    = $p->original_image
                ? $this->renderImage($p->original_image, $width, $height, $format)
                : null;
            unset($p->original_image, $p->locale_priority);
            return $p;
        });

        return response()->json(['data' => $data]);
    }

    protected function renderImage(string $path, int $w, int $h, string $format): ?string
    {
        $sourcePath = storage_path('app/public/' . $path);
        if (! file_exists($sourcePath)) return null;

        $info = pathinfo($path);
        $cacheRel = 'cache/' . $info['dirname'] . '/' . $info['filename'] . "_{$w}x{$h}.{$format}";
        $cacheAbs = storage_path('app/public/' . $cacheRel);

        if (! file_exists($cacheAbs)) {
            if (! is_dir(dirname($cacheAbs))) {
                mkdir(dirname($cacheAbs), 0755, true);
            }

            $img = Image::make($sourcePath);
            $img->resize($w, null, function ($c) {
                $c->aspectRatio();
                $c->upsize();
            });
            if ($img->height() > $h) {
                $img->crop($w, $h, 0, 0);
            }
            $img->encode($format, 85)->save($cacheAbs);
        }

        return '/storage/' . $cacheRel;
    }
}
