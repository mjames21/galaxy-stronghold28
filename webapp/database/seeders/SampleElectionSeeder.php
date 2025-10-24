<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Election;
use App\Models\District;
use App\Models\PollingStation;
use App\Models\Party;
use App\Models\Result;

class SampleElectionSeeder extends Seeder
{
    // Toggle this to false to import from CSV
    private const MOCK_MODE = true;

    // CSV path (when MOCK_MODE = false)
    private const CSV_PATH = 'data/sl_results.csv';

    public function run(): void
    {
        // Create one example election shell (no team_id)
        $election = Election::create([
            'name'          => 'Sierra Leone General (Sample)',
            'slug'          => 'sl-general-sample',
            'election_date' => '2023-06-24',
            'description'   => 'Seeded dataset for Stronghold 28 demos',
        ]);

        if (self::MOCK_MODE) {
            $this->seedMockResults($election->id);
        } else {
            $this->importFromCsv($election->id);
        }
    }

    private function seedMockResults(int $electionId): void
    {
        $partyIds  = Party::pluck('id','short_code'); // ['SLPP'=>1, ...]
        $districts = District::with('pollingStations')->get();

        foreach ($districts as $d) {
            foreach ($d->pollingStations as $ps) {
                // Simple mock: SLPP & APC lead; NGC/C4C smaller
                $slpp = rand(300, 800);
                $apc  = rand(250, 750);
                $ngc  = rand( 40, 200);
                $c4c  = rand( 20, 150);

                $turnout    = $slpp + $apc + $ngc + $c4c;
                $registered = $ps->registered_voters;

                $rows = [
                    ['code'=>'SLPP','votes'=>$slpp],
                    ['code'=>'APC', 'votes'=>$apc ],
                    ['code'=>'NGC', 'votes'=>$ngc ],
                    ['code'=>'C4C', 'votes'=>$c4c ],
                ];

                foreach ($rows as $r) {
                    Result::create([
                        'election_id'        => $electionId,
                        'district_id'        => $d->id,
                        'polling_station_id' => $ps->id,
                        'party_id'           => $partyIds[$r['code']] ?? $partyIds->first(),
                        'votes'              => $r['votes'],
                        'turnout'            => $turnout,
                        'registered'         => $registered,
                    ]);
                }
            }
        }
    }

    private function importFromCsv(int $electionId): void
    {
        $partyIds    = Party::pluck('id','short_code'); // code => id
        $districtIds = District::pluck('id','name');    // name => id
        $psByCode    = PollingStation::pluck('id','code'); // code => id

        $path = storage_path('app/' . self::CSV_PATH);
        if (!is_file($path)) {
            throw new \RuntimeException("CSV not found at $path. Create it or set MOCK_MODE=true.");
        }
        if (($fh = fopen($path, 'r')) === false) {
            throw new \RuntimeException("Unable to open CSV at $path");
        }

        $header = fgetcsv($fh);
        $idx = array_flip($header ?: []);
        $required = ['district','polling_station_code','party_code','votes','registered'];
        foreach ($required as $col) {
            if (!isset($idx[$col])) {
                throw new \RuntimeException("CSV missing required column: $col");
            }
        }

        $aggTurnout = []; // "$dId|$psId" => sumVotes
        $aggReg     = [];

        while (($row = fgetcsv($fh)) !== false) {
            $districtName = trim($row[$idx['district']]);
            $psCode       = trim($row[$idx['polling_station_code']]);
            $partyCode    = strtoupper(trim($row[$idx['party_code']]));
            $votes        = (int) $row[$idx['votes']];
            $registered   = (int) $row[$idx['registered']];

            $districtId = $districtIds[$districtName] ?? null;
            if (!$districtId) {
                $districtId = District::where('code', strtoupper(preg_replace('/[^A-Z0-9]+/i','_', $districtName)))->value('id');
            }
            if (!$districtId) continue;

            $psId = $psByCode[$psCode] ?? null;
            if (!$psId) {
                $psId = PollingStation::create([
                    'district_id'        => $districtId,
                    'name'               => $psCode,
                    'code'               => $psCode,
                    'registered_voters'  => $registered,
                ])->id;
                $psByCode[$psCode] = $psId;
            }

            $partyId = $partyIds[$partyCode] ?? null;
            if (!$partyId) continue;

            Result::create([
                'election_id'        => $electionId,
                'district_id'        => $districtId,
                'polling_station_id' => $psId,
                'party_id'           => $partyId,
                'votes'              => $votes,
                'turnout'            => null,     // set later
                'registered'         => $registered,
            ]);

            $k = $districtId.'|'.$psId;
            $aggTurnout[$k] = ($aggTurnout[$k] ?? 0) + $votes;
            $aggReg[$k]     = $registered;
        }
        fclose($fh);

        foreach ($aggTurnout as $key => $turnout) {
            [$dId,$psId] = explode('|', $key);
            \DB::table('results')
                ->where('election_id', $electionId)
                ->where('district_id', (int)$dId)
                ->where('polling_station_id', (int)$psId)
                ->update([
                    'turnout'    => $turnout,
                    'registered' => $aggReg[$key] ?? null,
                ]);
        }
    }
}
