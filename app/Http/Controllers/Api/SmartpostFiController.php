<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmartpostLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmartpostFiController extends Controller
{
    public function locations(Request $request): JsonResource
    {
        $locations = SmartpostLocation::query()
            ->where('country', 'FI')
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
