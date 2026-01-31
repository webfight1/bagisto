<?php

namespace App\Console\Commands;

use App\Models\OmnivaLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SyncOmnivaLocations extends Command
{
    protected $signature = 'omniva:sync-locations {--force : Run even if already ran recently}';

    protected $description = 'Sync Omniva parcel locker locations from locations.json into database';

    public function handle()
    {
        ini_set('memory_limit', '1G');
        set_time_limit(0);

        $url = 'https://www.omniva.ee/locations.json';

        $this->info('Fetching Omniva locations...');

        $response = Http::timeout(60)->retry(3, 1000)->get($url);

        if (! $response->successful()) {
            $this->error('Failed to fetch Omniva locations. HTTP '.$response->status());

            return 1;
        }

        $locations = $response->json();

        if (! is_array($locations)) {
            $this->error('Invalid Omniva locations response (expected JSON array).');

            return 1;
        }

        $filtered = array_values(array_filter($locations, function ($row) {
            $country = $row['A0_NAME'] ?? null;
            $type = $row['TYPE'] ?? null;

            return $country === 'EE' && $type === '0';
        }));

        $this->info('Filtered EE parcel lockers: '.count($filtered));

        $now = now();

        $payload = [];

        foreach ($filtered as $row) {
            $zip = (string) ($row['ZIP'] ?? '');
            $name = (string) ($row['NAME'] ?? '');

            if ($zip === '' || $name === '') {
                continue;
            }

            $payload[] = [
                'zip' => $zip,
                'name' => $name,
                'country' => (string) ($row['A0_NAME'] ?? ''),
                'type' => (string) ($row['TYPE'] ?? ''),
                'county' => $row['A1_NAME'] ?? null,
                'municipality' => $row['A2_NAME'] ?? null,
                'city' => $row['A3_NAME'] ?? null,
                'street' => $row['A5_NAME'] ?? null,
                'house' => $row['A7_NAME'] ?? null,
                'lng' => isset($row['X_COORDINATE']) ? (float) $row['X_COORDINATE'] : null,
                'lat' => isset($row['Y_COORDINATE']) ? (float) $row['Y_COORDINATE'] : null,
                'source_modified_at' => isset($row['MODIFIED']) ? $row['MODIFIED'] : null,
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
            OmnivaLocation::query()->upsert(
                $payload,
                ['zip'],
                [
                    'name',
                    'country',
                    'type',
                    'county',
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

        $this->info('Omniva locations sync complete.');

        return 0;
    }
}
