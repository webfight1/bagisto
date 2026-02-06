<?php

namespace App\Console\Commands;

use App\Models\SmartpostLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SyncSmartpostLocations extends Command
{
    protected $signature = 'smartpost:sync-locations {--force : Run even if already ran recently}';

    protected $description = 'Sync Smartpost parcel locker locations from Posti API into database';

    public function handle()
    {
        ini_set('memory_limit', '1G');
        set_time_limit(0);

        $url = 'https://locationservice.posti.com/api/2/location/?countryCode=EE';

        $this->info('Fetching Smartpost locations...');

        $response = Http::timeout(60)->retry(3, 1000)->get($url);

        if (! $response->successful()) {
            $this->error('Failed to fetch Smartpost locations. HTTP '.$response->status());

            return 1;
        }

        $payloadJson = $response->json();
        $locations = is_array($payloadJson) ? ($payloadJson['locations'] ?? []) : [];

        if (! is_array($locations)) {
            $this->error('Invalid Smartpost locations response (expected JSON array).');

            return 1;
        }

        $filtered = array_values(array_filter($locations, function ($row) {
            $country = $row['countryCode'] ?? null;
            $type = $row['type'] ?? null;

            return $country === 'EE' && $type === 'SMARTPOST';
        }));

        $this->info('Filtered EE Smartpost parcel lockers: '.count($filtered));

        $now = now();
        $payload = [];

        foreach ($filtered as $row) {
            $locationId = (string) ($row['id'] ?? '');
            $postalCode = (string) ($row['postalCode'] ?? '');
            $name = (string) ($row['publicName']['fi'] ?? $row['publicName']['en'] ?? '');
            $address = $row['address']['fi'] ?? [];

            if ($locationId === '' || $postalCode === '' || $name === '') {
                continue;
            }

            $payload[] = [
                'location_id' => $locationId,
                'postal_code' => $postalCode,
                'name' => $name,
                'country' => (string) ($row['countryCode'] ?? ''),
                'type' => (string) ($row['type'] ?? ''),
                'municipality' => $address['municipality'] ?? null,
                'city' => $address['postalCodeName'] ?? null,
                'street' => $address['streetName'] ?? null,
                'house' => $address['streetNumber'] ?? null,
                'lng' => isset($row['location']['lon']) ? (float) $row['location']['lon'] : null,
                'lat' => isset($row['location']['lat']) ? (float) $row['location']['lat'] : null,
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
            SmartpostLocation::query()->upsert(
                $payload,
                ['location_id'],
                [
                    'postal_code',
                    'name',
                    'country',
                    'type',
                    'municipality',
                    'city',
                    'street',
                    'house',
                    'lng',
                    'lat',
                    'source_modified_at',
                    'raw',
                    'updated_at',
                ]
            );
        });

        $this->info('Smartpost locations sync complete.');

        return 0;
    }
}
