<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmartpostLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmartpostController extends Controller
{
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
