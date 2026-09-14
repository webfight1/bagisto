<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmartpostLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

class SmartpostController extends Controller
{
    #[OA\Get(
        path: '/api/v1/smartpost/locations',
        operationId: 'getSmartpostLockers',
        summary: 'Itella / Smartpost parcel locker locations (EE)',
        description: 'Synced from Itella plugin API (delivery.plugins.itella.com). Includes both legacy blue SMARTPOST and newer white LOCKER types. Use `location_id` when sending to /checkout/shipping-method.',
        tags: ['Aiamaailm Shipping'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'locations', type: 'array', items: new OA\Items(properties: [
                        new OA\Property(property: 'location_id', type: 'string', example: '3696605'),
                        new OA\Property(property: 'postal_code', type: 'string'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'municipality', type: 'string'),
                        new OA\Property(property: 'city', type: 'string'),
                        new OA\Property(property: 'street', type: 'string'),
                        new OA\Property(property: 'house', type: 'string'),
                        new OA\Property(property: 'lng', type: 'string'),
                        new OA\Property(property: 'lat', type: 'string'),
                    ])),
                ]),
            ])),
        ]
    )]
    public function locations(Request $request): JsonResource
    {
        $locations = SmartpostLocation::query()
            ->where('country', 'EE')
            ->where('type', 'SMARTPOST')
            ->orderBy('name')
            ->get([
                'location_id',
                'postal_code',
                'name',
                'municipality',
                'city',
                'street',
                'house',
                'lng',
                'lat',
                'source_modified_at',
            ]);

        return new JsonResource([
            'data' => [
                'locations' => $locations,
            ],
        ]);
    }
}
