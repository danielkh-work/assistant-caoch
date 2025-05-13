<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sports = [
            'American Football ',
            // 'Baseball',
            // 'Basketball',
            // 'Soccer',
            // 'Volleyball',
            // 'Tennis',
            // 'Cornhole',
            // 'Spikeball',
            // 'Badminton',
            // 'Ultimate Frisbee'
        ];

        foreach ($sports as $sport) {
            Sport::firstOrCreate(['title' => $sport]);
        }
    }
}
