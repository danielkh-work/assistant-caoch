<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\League>
 */
class LeagueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sport_id' => 1,
            'league_rule_id' => 3,
            'title' => $this->faker->name,
            'number_of_team' => 2,
            'number_of_downs' => 4,
            'length_of_field' => 100,
            'number_of_timeouts' => 3,
            'clock_time' => "CFL",
            'number_of_quarters' => 4,
            'length_of_quarters' => 15,
            'stop_time_reason' => 1,
            'overtime_rules' => 2,
            'number_of_players' => 11,
            'flag_tbd' => 0,
           
        ];
    }
}
