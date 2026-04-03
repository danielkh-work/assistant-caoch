<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\League;
use App\Models\Game;
use App\Models\Play;
use App\Models\BenchPlayer;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class SuggestionControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected League $league;
    protected Game $game;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!Schema::hasTable('games') || !Schema::hasTable('plays') || !Schema::hasTable('bench_players')) {
            $this->markTestSkipped('Backend schema issue: required tables for suggestions testing not found');
        }

        $this->user = User::factory()->create([
            'role' => 'head_coach',
            'status' => 'approved'
        ]);

        $sportId = DB::table('sports')->insertGetId(['title' => 'Test Sport']);
        
        $this->league = new League();
        $this->league->user_id = $this->user->id;
        $this->league->sport_id = $sportId;
        $this->league->league_rule_id = DB::table('league_rules')->value('id') ?? 1;
        $this->league->title = 'Test League';
        $this->league->number_of_team = 2;
        $this->league->save();

        $this->game = new Game();
        $this->game->league_id = $this->league->id;
        $this->game->user_id = $this->user->id;
        $this->game->my_team_id = 1;
        $this->game->oponent_team_id = 2;
        $this->game->my_team_name = 'My Team';
        $this->game->opponent_team_name = 'Opponent';
        $this->game->season = '2026';
        $this->game->week = 1;
        if (Schema::hasColumn('games', 'event_name')) {
            $this->game->event_name = 'Test Event';
        }
        $this->game->save();
    }

    protected function auth()
    {
        Sanctum::actingAs($this->user);
        $this->actingAs($this->user, 'api');
    }

    public function test_can_get_offensive_suggested_plays()
    {
        $this->auth();

        $play = new Play();
        $play->league_id = $this->league->id;
        $play->play_name = 'Offensive Play';
        $play->possession = 'offensive';
        $play->play_type = 1;
        $play->zone_selection = 1;
        $play->min_expected_yard = '0';
        $play->max_expected_yard = '10';
        $play->pre_snap_motion = 1;
        $play->play_action_fake = 1;
        $play->save();

        $response = $this->getJson('/api/leagues/' . $this->league->id . '/get-suggested-plays?possession=offensive&match_id=' . $this->game->id . '&is_practice=false');

        $response->assertStatus(200);
    }

    public function test_can_get_defensive_suggested_plays()
    {
        $this->auth();

        $response = $this->getJson('/api/leagues/' . $this->league->id . '/get-suggested-plays?possession=defensive&match_id=' . $this->game->id . '&is_practice=false');

        $response->assertStatus(200);
    }
}
