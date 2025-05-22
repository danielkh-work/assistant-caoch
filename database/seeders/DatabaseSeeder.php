<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Leaque;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SportSeeder::class,
            LeaqueSeeder::class,
            OffensivePositionsTableSeeder::class,
            DefensivePositionsTableSeeder::class,
            SubscriptionPlanSeeder::class,
            PlaysSeeder::class,
            RolesAndPermissionsSeeder::class
        ]);
        
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
