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
                ['code' => 'EDGE', 'name' => 'Edge Rusher Quick'],
                ['code' => 'EDGE', 'name' => 'Edge Rusher Rush'],
                ['code' => 'MLBM', 'name' => 'Middle linebacker (Mike)'],
                ['code' => 'MLBS',  'name' => 'Strong Side Linebacker (Sam)'],
                ['code' => 'MLBW',  'name' => 'Weak Side Linebacker (Will)'],
                ['code' => 'Add Dime',  'name' => 'Add Dime'],
                ['code' => 'Dollar',  'name' => 'Dollar'],
                ['code' => 'Extras',  'name' => 'Extras'],
                ['code' => '1/2 Back',  'name' => '1/2 Back'],
                ['code' => 'OLB',  'name' => 'Outside Linebacker'],
                ['code' => 'ILB',  'name' => 'Inside Linebacker'],
                ['code' => 'CB',   'name' => 'Corner Weak Side'],
                ['code' => 'CB',   'name' => 'Corner Strong Side'],
                ['code' => 'NB',   'name' => 'Nickelback'],
                ['code' => 'SS',   'name' => 'Strong Safety'],
                ['code' => 'FS',   'name' => 'Free Safety'],
                ['code' => 'DB',   'name' => 'Halfback Weak side'],
                ['code' => 'DB',   'name' => 'Halfback Strong Side'],
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
