<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OmnivaLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

class OmnivaController extends Controller
{
    #[OA\Get(
        path: '/api/v1/omniva/locations',
        operationId: 'getOmnivaLockers',
        summary: 'Omniva parcel locker locations (EE)',
        description: 'Synced daily from Omniva official locations.json. Returns all Estonian parcel lockers ordered by name. Use the `zip` field as locker_id when sending to /checkout/shipping-method.',
        tags: ['Aiamaailm Shipping'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'locations', type: 'array', items: new OA\Items(properties: [
                        new OA\Property(property: 'zip', type: 'string', example: '96331'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'county', type: 'string'),
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
        $locations = OmnivaLocation::query()
            ->where('country', 'EE')
            ->where('type', '0')
            ->orderBy('name')
            ->get([
                'zip',
                'name',
                'county',
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
