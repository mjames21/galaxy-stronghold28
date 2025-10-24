<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Region;
use App\Models\District;
use App\Models\PollingStation;

class GeoSeeder extends Seeder
{
    public function run(): void
    {
        // Optional: wipe existing
        \DB::table('polling_stations')->truncate();
        \DB::table('districts')->truncate();
        \DB::table('regions')->truncate();

        // Regions
        $regions = [
            'Eastern'      => ['Kailahun','Kenema','Kono'],
            'Northern'     => ['Bombali','Tonkolili','Koinadugu','Falaba'],
            'North West'   => ['Kambia','Port Loko','Karene'],
            'Southern'     => ['Bo','Bonthe','Moyamba','Pujehun'],
            'Western Area' => ['Western Area Urban','Western Area Rural'],
        ];

        foreach ($regions as $regionName => $districtNames) {
            $region = Region::create([
                'name' => $regionName,
                'code' => strtoupper(str_replace(' ', '_', $regionName)),
                'geojson' => null, // add GeoJSON later if you have boundary shapes
            ]);

            foreach ($districtNames as $i => $dName) {
                $district = District::create([
                    'region_id' => $region->id,
                    'name' => $dName,
                    'code' => strtoupper(preg_replace('/[^A-Z0-9]+/i','_', $dName)),
                    'geojson' => null,
                    'seats' => 1, // placeholder; update with official constituency counts if needed
                ]);

                // Seed a few example polling stations per district so PVT/turnout demos work
                for ($n = 1; $n <= 3; $n++) {
                    PollingStation::create([
                        'district_id' => $district->id,
                        'name' => "{$dName} PS{$n}",
                        'code' => substr($district->code,0,3) . "-PS{$n}",
                        'registered_voters' => rand(900, 1800),
                    ]);
                }
            }
        }
    }
}
