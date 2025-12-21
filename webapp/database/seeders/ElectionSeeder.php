<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Election;

class ElectionSeeder extends Seeder
{
    public function run(): void
    {
        // We collapse multiple PDFs for the same election into one logical "election"
        // and keep the file names inside the description for traceability.

        $elections = [
            [
                'name'         => '2007 Presidential – First Round',
                'slug'         => 'sl_2007_presidential_r1',
                'election_date'=> null, // fill actual date later if you want
                'type'         => 'presidential',
                'round'        => 1,
                'description'  => 'Seeded from NEC files: 07_FinalResults_2007.pdf',
            ],
            [
                'name'         => '2007 Presidential – Run-Off',
                'slug'         => 'sl_2007_presidential_r2',
                'election_date'=> null,
                'type'         => 'presidential',
                'round'        => 2,
                'description'  => 'Seeded from NEC files: 07_RunOffResults_2007.pdf',
            ],
            [
                'name'         => '2012 Presidential – National Results',
                'slug'         => 'sl_2012_presidential_r1',
                'election_date'=> null,
                'type'         => 'presidential',
                'round'        => 1,
                'description'  => 'Seeded from NEC files: 2012 Presidential ResultsPer District.pdf; Presidential Election Result 2012.pdf',
            ],
            [
                'name'         => '2018 Presidential – First Round',
                'slug'         => 'sl_2018_presidential_r1',
                'election_date'=> null,
                'type'         => 'presidential',
                'round'        => 1,
                'description'  => 'Seeded from NEC file: Presidential Election result first round 2018.pdf',
            ],
            [
                'name'         => '2018 Presidential – Run-Off',
                'slug'         => 'sl_2018_presidential_r2',
                'election_date'=> null,
                'type'         => 'presidential',
                'round'        => 2,
                'description'  => 'Seeded from NEC files: Run-Off Presidential Result 2018.pdf; Progressive Presidential Result 2018 .pdf',
            ],
            [
                'name'         => '2023 Presidential – Final Results',
                'slug'         => 'sl_2023_presidential_r1',
                'election_date'=> null,
                'type'         => 'presidential',
                'round'        => 1,
                'description'  => 'Seeded from NEC file: Announcement of 2023 Presidential election result.pdf',
            ],
        ];

        foreach ($elections as $data) {
            Election::updateOrCreate(
                ['slug' => $data['slug']],   // unique key
                [
                    'name'          => $data['name'],
                    'election_date' => $data['election_date'],
                    'type'          => $data['type'],
                    'round'         => $data['round'],
                    'description'   => $data['description'],
                ]
            );
        }
    }
}
