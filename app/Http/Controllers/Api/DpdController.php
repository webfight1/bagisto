<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DpdLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

class DpdController extends Controller
{
    #[OA\Get(
        path: '/api/v1/dpd/locations',
        operationId: 'getDpdLockers',
        summary: 'DPD parcel shop / locker locations (EE)',
        description: 'Synced from DPD API. Use `parcel_shop_id` as locker_id when sending to /checkout/shipping-method.',
        tags: ['Aiamaailm Shipping'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'locations', type: 'array', items: new OA\Items(properties: [
                        new OA\Property(property: 'parcel_shop_id', type: 'string', example: 'EE90273'),
                        new OA\Property(property: 'parcel_shop_type', type: 'string', enum: ['PickupStation', 'ParcelLocker']),
                        new OA\Property(property: 'company_name', type: 'string'),
                        new OA\Property(property: 'street', type: 'string'),
                        new OA\Property(property: 'house_no', type: 'string'),
                        new OA\Property(property: 'city', type: 'string'),
                        new OA\Property(property: 'zip_code', type: 'string'),
                        new OA\Property(property: 'country_code', type: 'string'),
                    ])),
                ]),
            ])),
        ]
    )]
    public function locations(Request $request): JsonResource
    {
        $locations = DpdLocation::query()
            ->where('country_code', 'EE')
            ->orderBy('city')
            ->orderBy('company_name')
            ->get([
                'parcel_shop_id',
                'legacy_shop_id',
                'parcel_shop_type',
                'company_name',
                'company_short_name',
                'street',
                'house_no',
                'country_code',
                'zip_code',
                'city',
                'longitude',
                'latitude',
                'opening_hours',
            ]);

        return new JsonResource([
            'data' => [
                'locations' => $locations,
            ],
        ]);
    }
}
