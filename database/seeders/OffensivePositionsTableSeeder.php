<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OffensivePositionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            'QB',  // Quarterback
            'RB',  // Running Back
            'FB',  // Fullback
            'WR',  // Wide Receiver
            'Slot WR', // Slot Receiver
            'TE',  // Tight End
            'LT',  // Left Tackle
            'LG',  // Left Guard
            'C',   // Center
            'RG',  // Right Guard
            'RT',  // Right Tackle
            'HB',  // Halfback
        ];

        foreach ($positions as $position) {
            DB::table('offensive_positions')->insert([
                'name' => $position,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
