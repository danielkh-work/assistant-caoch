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
          DB::table('offensive_positions')->delete();
          $positions = [
                ['code' => 'QB',     'name' => 'Quarterback'],
                ['code' => 'RB',     'name' => 'Running Back'],
                ['code' => 'FB',     'name' => 'Fullback'],
                ['code' => 'WR',     'name' => 'Wide Receiver'],
                ['code' => 'Slot WR','name' => 'Slot Receiver'],
                ['code' => 'TE',     'name' => 'Tight End'],
                ['code' => 'LT',     'name' => 'Left Tackle'],
                ['code' => 'LG',     'name' => 'Left Guard'],
                ['code' => 'C',      'name' => 'Center'],
                ['code' => 'RG',     'name' => 'Right Guard'],
                ['code' => 'RT',     'name' => 'Right Tackle'],
                ['code' => 'HB',     'name' => 'Halfback'],
        ];

        foreach ($positions as $position) {
            DB::table('offensive_positions')->insert([
                'code' => $position['code'],
                'name' => $position['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
