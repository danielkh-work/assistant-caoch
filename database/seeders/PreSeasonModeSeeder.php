<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Play;
use App\Models\Sport;
use App\Models\League;
use App\Models\LeagueRule;

class PreSeasonModeSeeder extends Seeder
{
    public function run()
    {
        // Ensure minimal required data for pre-season mode.
        $sport = Sport::firstOrCreate(['title' => 'Football']);
        $rule = LeagueRule::firstOrCreate(['title' => 'Standard']);

        $league = League::firstOrCreate([
            'title' => 'Preseason Seeded League',
            'sport_id' => $sport->id,
            'league_rule_id' => $rule->id,
        ], [
            'number_of_team' => 2,
        ]);

        Play::firstOrCreate([
            'play_name' => 'Seeded Offensive Play',
            'offensive_play_type' => 'Offense',
            'league_id' => $league->id,
        ], [
            'play_type' => 1,
            'zone_selection' => 2,
            'min_expected_yard' => 5,
            'max_expected_yard' => 15,
            'target_offensive' => 1,
            'opposing_defensive' => 1,
            'pre_snap_motion' => 0,
            'play_action_fake' => 1,
            'possession' => 'offensive',
        ]);
    }
}
