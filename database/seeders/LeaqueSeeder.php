<?php

namespace Database\Seeders;

use App\Models\Leaque;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaqueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('league_rules')->insert([
            // CFL Entry
            // [
            //     'sport_id'=>1,
            //     'title' => 'Canadian Football League (CFL)',
            //     'number_of_downs' => 3,
            //     'length_of_field' => '110 yards',
            //     'number_of_timeouts' => 1,
            //     'clock_time' => 'CFL',
            //     'number_of_quarters' => 4,
            //     'length_of_quarters' => 15,
            //     'stop_time_reason' => 1,
            //     'overtime_rules' => 1,
            //     'number_of_players' => 12,
            //     'flag_tbd' => 'No',
            //     'created_at' => now(),
            //     'updated_at' => now()
            // ],
            // NFL Entry
            [
                'sport_id'=>1,
                'title' => 'American Football (NFL)',
                'number_of_downs' => 4,
                'length_of_field' => '100 yards',
                'number_of_timeouts' => 3,
                'clock_time' => 'NFL',
                'number_of_quarters' => 4,
                'length_of_quarters' => 15,
                'stop_time_reason' => 2,
                'overtime_rules' => 2,
                'number_of_players' => 11,
                'flag_tbd' => 'No',
                'created_at' => now(),
                'updated_at' => now()
            ],
            // Hybrid (SICA) Entry
            // [
            //     'sport_id'=>1,
            //     'title' => 'Hybrid Canadian/American (SICA)',
            //     'number_of_downs' => 4,
            //     'length_of_field' => '105 yards',
            //     'number_of_timeouts' => 2,
            //     'clock_time' => 'SICA',
            //     'number_of_quarters' => 4,
            //     'length_of_quarters' => 15,
            //     'stop_time_reason' => 3,
            //     'overtime_rules' => 3,
            //     'number_of_players' => 11,
            //     'flag_tbd' => 'NO',
            //     'created_at' => now(),
            //     'updated_at' => now()
            // ],
            [
                'sport_id'=>1,
                'title' => 'Special',
                'number_of_downs' => 0,
                'length_of_field' => '',
                'number_of_timeouts' => 0,
                'clock_time' => '',
                'number_of_quarters' => 0,
                'length_of_quarters' => 0,
                'stop_time_reason' =>0,
                'overtime_rules' => 0,
                'number_of_players' => 0,
                'flag_tbd' => '',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
}
