<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefensivePositionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('defensive_positions')->delete();
        $positions = [
                ['code' => 'DT',   'name' => 'Defensive Tackle'],
                ['code' => 'NT',   'name' => 'Nose Tackle'],
                ['code' => 'DE',   'name' => 'Defensive End'],
                ['code' => 'EDGE', 'name' => 'Edge Rusher'],
                ['code' => 'MLB',  'name' => 'Middle Linebacker'],
                ['code' => 'OLB',  'name' => 'Outside Linebacker'],
                ['code' => 'ILB',  'name' => 'Inside Linebacker'],
                ['code' => 'CB',   'name' => 'Cornerback'],
                ['code' => 'NB',   'name' => 'Nickelback'],
                ['code' => 'SS',   'name' => 'Strong Safety'],
                ['code' => 'FS',   'name' => 'Free Safety'],
                ['code' => 'DB',   'name' => 'Defensive Back'],
            ];

            foreach ($positions as $position) {
                 DB::table('defensive_positions')->insert(
                    [
                        'name'=>$position['name'],
                        'code'       => $position['code'],
                        'updated_at' => now(),
                        'created_at' => now(), // used only if inserting new record
                    ]
                );
        }
    }
}
