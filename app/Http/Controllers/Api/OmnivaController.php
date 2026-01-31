<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OmnivaLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OmnivaController extends Controller
{
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
