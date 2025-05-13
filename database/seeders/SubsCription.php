<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubsCription extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('subscription_plans')->insert([
            // CFL Entry
            [
               'title'=>'Beginner'
            ],
            [
                'title'=>'Expert'
            ],
            [
                'title'=>'Pro'
            ]
        ]);
    }
}
