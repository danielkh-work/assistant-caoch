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
        $positions = [
            'DT',   // Defensive Tackle
            'NT',   // Nose Tackle
            'DE',   // Defensive End
            'EDGE', // Edge Rusher
            'MLB',  // Middle Linebacker
            'OLB',  // Outside Linebacker
            'ILB',  // Inside Linebacker
            'CB',   // Cornerback
            'NB',   // Nickelback
            'SS',   // Strong Safety
            'FS',   // Free Safety
            'DB',   // Defensive Back
        ];

        foreach ($positions as $position) {
            DB::table('defensive_positions')->insert([
                'name' => $position,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
