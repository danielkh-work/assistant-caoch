<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            ['title' => 'Classic', 'currency' => 'USD', 'amount' => '10', 'month' => '1', 'description' => 'Starter Plan', 'commission' => '5'],
            ['title' => 'HD HUMAN DASHBOARD',     'currency' => 'USD', 'amount' => '30', 'month' => '1', 'description' => 'Pro Plan',     'commission' => '10'],
            ['title' => 'Pro','currency' => 'USD', 'amount' => '100', 'month' => '1', 'description' => 'Enterprise Plan', 'commission' => '15'],
        ];

        foreach ($plans as $plan) {
            foreach (['basic', 'advance'] as $type) {
                DB::table('subscription_plans')->insert([
                    'title' => $plan['title'],
                    'type' => $type,
                    'currency' => $plan['currency'],
                    'amount' => $type === 'basic' ? $plan['amount'] : $plan['amount'] * 1.5, // 50% more for advance
                    'month' => $plan['month'],
                    'description' => $plan['description'] . ' - ' . ucfirst($type),
                    'commission' => $type === 'basic' ? $plan['commission'] : $plan['commission'] + 5,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
