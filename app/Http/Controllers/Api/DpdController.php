<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DpdLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DpdController extends Controller
{
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
