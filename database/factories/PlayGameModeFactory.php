<?php

namespace Database\Factories;

use App\Models\PlayGameMode;
use App\Models\Sport;
use App\Models\League;
use App\Models\LeagueTeam;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlayGameModeFactory extends Factory
{
    protected $model = PlayGameMode::class;

    public function definition()
    {
        // Create related models and use their IDs
        $sport = Sport::factory()->create();
        $league = League::factory()->create();
        $myTeam = LeagueTeam::factory()->create(['league_id' => $league->id]);
        $opponentTeam = LeagueTeam::factory()->create(['league_id' => $league->id]);

        return [
            'sport_id' => $sport->id,
            'league_id' => $league->id,
            'my_team_id' => $myTeam->id,
            'oponent_team_id' => $opponentTeam->id,
            'my_team_score' => 0,
            'oponent_team_score' => 0,
            'quater' => 'Q1',
            'downs' => '1st',
            'status' => 0,
        ];
    }
}