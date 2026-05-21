<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\League;
use App\Models\LeagueTeam;
use App\Models\PlayGameLog;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class LogControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected League $league;
    protected LeagueTeam $myTeam;
    protected LeagueTeam $opponentTeam;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('play_game_logs') || !Schema::hasTable('leagues')) {
            $this->markTestSkipped('Backend schema issue: required tables for log testing not found');
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

        $this->myTeam = new LeagueTeam();
        $this->myTeam->league_id = $this->league->id;
        $this->myTeam->team_name = 'Alpha';
        $this->myTeam->save();

        $this->opponentTeam = new LeagueTeam();
        $this->opponentTeam->league_id = $this->league->id;
        $this->opponentTeam->team_name = 'Beta';
        $this->opponentTeam->save();
    }

    protected function auth()
    {
        Sanctum::actingAs($this->user);
        $this->actingAs($this->user, 'api');
    }

    public function test_can_get_logs_index()
    {
        $this->auth();

        $log = new PlayGameLog();
        $log->league_id = $this->league->id;
        $log->game_id = 1;
        $log->my_team_id = $this->myTeam->id;
        $log->oponent_team_id = $this->opponentTeam->id;
        $log->target = $this->myTeam->id;
        $log->save();

        $response = $this->getJson('/api/leagues/' . $this->league->id . '/matches/1/logs');

        $response->assertStatus(200);
    }

    public function test_targetdata_fallback_for_down_with_empty_target()
    {
        $this->auth();

        $log = new PlayGameLog();
        $log->league_id = $this->league->id;
        $log->game_id = 1;
        $log->my_team_id = $this->myTeam->id;
        $log->oponent_team_id = $this->opponentTeam->id;
        $log->target = '';
        $log->type_of_log = 'down';
        $log->downs = 2;
        $log->save();

        $response = $this->getJson('/api/leagues/' . $this->league->id . '/matches/1/logs');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.targetdata.team_name', 'Alpha');
    }

    public function test_targetdata_fallback_for_short_with_mismatched_target()
    {
        $this->auth();

        $log = new PlayGameLog();
        $log->league_id = $this->league->id;
        $log->game_id = 1;
        $log->my_team_id = $this->myTeam->id;
        $log->oponent_team_id = $this->opponentTeam->id;
        $log->target = 'invalid-target';
        $log->type_of_log = 'short';
        $log->save();

        $response = $this->getJson('/api/leagues/' . $this->league->id . '/matches/1/logs');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.targetdata.team_name', 'Alpha');
    }

    public function test_targetdata_remains_null_for_unmatched_non_fallback_types()
    {
        $this->auth();

        $log = new PlayGameLog();
        $log->league_id = $this->league->id;
        $log->game_id = 1;
        $log->my_team_id = $this->myTeam->id;
        $log->oponent_team_id = $this->opponentTeam->id;
        $log->target = 'invalid-target';
        $log->type_of_log = 'play';
        $log->save();

        $response = $this->getJson('/api/leagues/' . $this->league->id . '/matches/1/logs');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.targetdata', null);
    }
}
