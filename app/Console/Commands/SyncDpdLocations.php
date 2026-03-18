<?php

namespace App\Console\Commands;

use App\Models\DpdLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SyncDpdLocations extends Command
{
    protected $signature = 'dpd:sync-locations {--force : Run even if already ran recently}';

    protected $description = 'Sync DPD parcel shop locations from DPD API into database';

    public function handle()
    {
        ini_set('memory_limit', '1G');
        set_time_limit(0);

        $url = 'https://dpdbaltics.com/PickupParcelShopData.json';

        $this->info('Fetching DPD locations...');

        $response = Http::timeout(60)->retry(3, 1000)->get($url);

        if (! $response->successful()) {
            $this->error('Failed to fetch DPD locations. HTTP '.$response->status());

            return 1;
        }

        $locations = $response->json();

        if (! is_array($locations)) {
            $this->error('Invalid DPD locations response (expected JSON array).');

            return 1;
        }

        $filtered = array_values(array_filter($locations, function ($row) {
            $countryCode = $row['countryCode'] ?? null;

            return $countryCode === 'EE';
        }));

        $this->info('Filtered EE parcel shops: '.count($filtered));

        $now = now();

        $payload = [];

        foreach ($filtered as $row) {
            $parcelShopId = (string) ($row['parcelShopId'] ?? '');
            $companyName = (string) ($row['companyName'] ?? '');

            if ($parcelShopId === '' || $companyName === '') {
                continue;
            }

            $payload[] = [
                'parcel_shop_id' => $parcelShopId,
                'legacy_shop_id' => $row['legacyShopId'] ?? null,
                'parcel_shop_type' => $row['parcelShopType'] ?? null,
                'company_name' => $companyName,
                'company_short_name' => $row['companyShortName'] ?? null,
                'street' => $row['street'] ?? null,
                'house_no' => $row['houseNo'] ?? null,
                'address_line2' => $row['addressLine2'] ?? null,
                'country_code' => $row['countryCode'] ?? null,
                'zip_code' => $row['zipCode'] ?? null,
                'city' => $row['city'] ?? null,
                'longitude' => isset($row['longitude']) ? (float) $row['longitude'] : null,
                'latitude' => isset($row['latitude']) ? (float) $row['latitude'] : null,
                'opening_hours' => isset($row['openingHours']) ? json_encode($row['openingHours'], JSON_UNESCAPED_UNICODE) : null,
                'source_modified_at' => null,
                'raw' => json_encode($row, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($payload)) {
            $this->warn('No valid locations to upsert.');

            return 0;
        }

        $this->info('Upserting into DB...');

        DB::transaction(function () use ($payload) {
            DpdLocation::query()->upsert(
                $payload,
                ['parcel_shop_id'],
                [
                    'legacy_shop_id',
                    'parcel_shop_type',
                    'company_name',
                    'company_short_name',
                    'street',
                    'house_no',
                    'address_line2',
                    'country_code',
                    'zip_code',
                    'city',
                    'longitude',
                    'latitude',
                    'opening_hours',
                    'source_modified_at',
                    'raw',
                    'updated_at',
                ]
            );
        });

        $this->info('DPD locations sync complete.');

        return 0;
    }
}
