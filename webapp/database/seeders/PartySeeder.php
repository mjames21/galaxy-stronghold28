<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Party;

class PartySeeder extends Seeder
{
    public function run(): void
    {
        Party::query()->truncate();

        Party::insert([
            ['name'=>'Sierra Leone People’s Party','short_code'=>'SLPP','color_hex'=>'#1c7c54','is_active'=>true],
            ['name'=>'All People’s Congress','short_code'=>'APC','color_hex'=>'#c8102e','is_active'=>true],
            ['name'=>'National Grand Coalition','short_code'=>'NGC','color_hex'=>'#f59e0b','is_active'=>true],
            ['name'=>'Coalition for Change','short_code'=>'C4C','color_hex'=>'#0ea5e9','is_active'=>true],
        ]);
    }
}
