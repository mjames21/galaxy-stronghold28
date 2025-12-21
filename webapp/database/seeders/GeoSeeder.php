<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\Region;
use App\Models\District;
use App\Models\PollingStation;

class GeoSeeder extends Seeder
{
    public function run(): void
    {
        // Safely reset tables that depend on each other
        Schema::disableForeignKeyConstraints();

        PollingStation::truncate();
        District::truncate();
        Region::truncate();

        Schema::enableForeignKeyConstraints();

        // Regions → Districts map
        $regions = [
            'Eastern'      => ['Kailahun','Kenema','Kono'],
            'Northern'     => ['Bombali','Tonkolili','Koinadugu','Falaba'],
            'North West'   => ['Kambia','Port Loko','Karene'],
            'Southern'     => ['Bo','Bonthe','Moyamba','Pujehun'],
            'Western Area' => ['Western Area Urban','Western Area Rural'],
        ];

        // Region short codes (fixed): ER, NR, NW, SR, WA
        $regionCodes = [
            'Eastern'      => 'ER',
            'Northern'     => 'NR',
            'North West'   => 'NW',
            'Southern'     => 'SR',
            'Western Area' => 'WA',
        ];

        foreach ($regions as $regionName => $districtNames) {
            $regionCode = $regionCodes[$regionName] ?? strtoupper(str_replace(' ', '_', $regionName));

            // Regions table
            $region = Region::create([
                'name' => $regionName,
                'code' => $regionCode,
            ]);

            foreach ($districtNames as $dName) {
                // Build district code:
                // - one word  => first 3 chars
                // - multiword => first char of each word
                $districtCode = $this->makeDistrictCode($dName);

                $district = District::create([
                    'region' => $region->code,  // ER, NR, NW, SR, WA
                    'name'   => $dName,
                    'code'   => $districtCode,  // e.g. KEN, PL, WAU
                ]);

                // Seed a few demo polling stations per district
                for ($n = 1; $n <= 3; $n++) {
                    PollingStation::create([
                        'district_id'       => $district->id,
                        'name'              => "{$dName} PS{$n}",
                        'code'              => substr($district->code, 0, 3) . "-PS{$n}",
                        'registered_voters' => rand(900, 1800),
                    ]);
                }
            }
        }
    }

    /**
     * Generate district code:
     * - If name has one word  => first 3 chars
     * - If name has many words => first char of each word
     */
    protected function makeDistrictCode(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        // Split on whitespace
        $parts = preg_split('/\s+/', $name);

        // Single word: first 3 letters
        if (count($parts) === 1) {
            $clean = preg_replace('/[^A-Za-z0-9]/', '', $parts[0]);
            return strtoupper(substr($clean, 0, 3));
        }

        // Multi-word: first letter of each word
        $abbr = '';
        foreach ($parts as $word) {
            $word = preg_replace('/[^A-Za-z0-9]/', '', $word);
            if ($word !== '') {
                $abbr .= $word[0];
            }
        }

        return strtoupper($abbr);
    }
}
