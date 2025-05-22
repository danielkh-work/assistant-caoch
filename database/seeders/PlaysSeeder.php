<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PlaysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('plays')->insert([
            [
                'league_id' => 1,
                'play_name' => 'Ironman',
                'play_type' => 1,
                'zone_selection' => 1,
                'min_expected_yard' => '3',
                'max_expected_yard' => '7',
                'pre_snap_motion' => 0,
                'play_action_fake' => 0,
                'video_path' => '',
                'image' => 'uploads/uploads/ironman.png',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'type' => 'run',
                'preferred_down' => 1,
                'possession' => 'offensive',
            ],
            [
                'league_id' => 1,
                'play_name' => 'Odin',
                'play_type' => 1,
                'zone_selection' => 2,
                'min_expected_yard' => '4',
                'max_expected_yard' => '10',
                'pre_snap_motion' => 1,
                'play_action_fake' => 0,
                'video_path' => '=',
                'image' => 'uploads/uploads/odin.png',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'type' => 'run',
                'preferred_down' => 1,
                'possession' => 'offensive',
            ],
            [
                'league_id' => 1,
                'play_name' => 'San Francisco',
                'play_type' => 2,
                'zone_selection' => 3,
                'min_expected_yard' => '5',
                'max_expected_yard' => '12',
                'pre_snap_motion' => 1,
                'play_action_fake' => 1,
                'video_path' => '',
                'image' => 'uploads/uploads/san-francisco.png',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'type' => 'pass',
                'preferred_down' => 2,
                'possession' => 'offensive',
            ],
            [
                'league_id' => 1,
                'play_name' => 'Saskatchewan',
                'play_type' => 2,
                'zone_selection' => 4,
                'min_expected_yard' => '10',
                'max_expected_yard' => '20',
                'pre_snap_motion' => 1,
                'play_action_fake' => 1,
                'video_path' => '',
                'image' => 'uploads/uploads/saskatchewan.png',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'type' => 'pass',
                'preferred_down' => 3,
                'possession' => 'offensive',
            ]
        ]);
    }
}
