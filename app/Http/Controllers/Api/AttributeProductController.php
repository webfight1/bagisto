<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Product\Models\Product;

class AttributeProductController extends Controller
{
    public function __construct(
        protected AttributeRepository $attributeRepository
    ) {}

    public function byBrand(string $value, Request $request)
    {
        return $this->filterByAttribute('brand', $value, $request);
    }

    public function byAttribute(string $attribute_code, string $value, Request $request)
    {
        return $this->filterByAttribute($attribute_code, $value, $request);
    }

    protected function filterByAttribute(string $code, string $rawValue, Request $request)
    {
        $attribute = $this->attributeRepository->findOneByField('code', $code);

        if (! $attribute) {
            return response()->json([
                'success' => false,
                'message' => "Attribute '{$code}' not found",
            ], 404);
        }

        $values = collect(explode(',', $rawValue))
            ->map(fn ($v) => trim($v))
            ->filter()
            ->unique()
            ->values();

        if ($values->isEmpty()) {
            return response()->json([
                'success'   => true,
                'attribute' => $code,
                'value'     => $rawValue,
                'products'  => [],
            ]);
        }

        $optionIds = DB::table('attribute_options')
            ->where('attribute_id', $attribute->id)
            ->whereIn('admin_name', $values)
            ->pluck('id')
            ->unique()
            ->values();

        if ($optionIds->isEmpty()) {
            return response()->json([
                'success'   => true,
                'attribute' => $code,
                'value'     => $rawValue,
                'products'  => [],
            ]);
        }

        $productIds = DB::table('product_attribute_values')
            ->where('attribute_id', $attribute->id)
            ->whereIn('integer_value', $optionIds)
            ->pluck('product_id')
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return response()->json([
                'success'   => true,
                'attribute' => $code,
                'value'     => $rawValue,
                'products'  => [],
            ]);
        }

        $products = DB::table('product_flat')
            ->leftJoin('product_images', 'product_images.product_id', '=', 'product_flat.product_id')
            ->whereIn('product_flat.product_id', $productIds)
            ->groupBy(
                'product_flat.product_id',
                'product_flat.sku',
                'product_flat.type',
                'product_flat.name',
                'product_flat.price',
                'product_flat.url_key',
                'product_flat.created_at'
            )
            ->select([
                'product_flat.product_id as id',
                'product_flat.sku',
                'product_flat.type',
                'product_flat.name',
                'product_flat.price',
                'product_flat.url_key',
                'product_flat.created_at',
                DB::raw('MIN(product_images.path) as image_path'),
            ])
            ->get()
            ->map(function ($row) {
                return [
                    'id'         => $row->id,
                    'sku'        => $row->sku,
                    'type'       => $row->type,
                    'name'       => $row->name,
                    'price'      => $row->price,
                    'url_key'    => $row->url_key,
                    'created_at' => $row->created_at,
                    'image_url'  => $row->image_path
                        ? url('storage/' . ltrim($row->image_path, '/'))
                        : null,
                ];
            });

        return response()->json([
            'success'   => true,
            'attribute' => $code,
            'value'     => $rawValue,
            'count'     => $products->count(),
            'products'  => $products,
        ]);
    }
}
