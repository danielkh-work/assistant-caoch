<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\League;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition()
    {
        return [
            'league_id' => League::factory(), // creates a league automatically
            'team_name' => $this->faker->company,
            'image' => 'test-image-path.jpg',
            'sport_id' => Sport::factory(),   // optional
            'type' => 0,
            'is_practice' => 0,
            'won' => 0,
            'drawn' => 0,
            'lost' => 0,
            'points' => 0,
        ];
    }
}