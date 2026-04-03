<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\Sport;
use App\Models\League;
use App\Models\LeagueTeam;
use App\Models\Team;
use Laravel\Sanctum\Sanctum;

class TeamControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected League $league;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'head_coach',
            'status' => 'approved'
        ]);

        $sport = Sport::where('title', 'Football Test')->first();
        if (!$sport) {
            $sport = new Sport();
            $sport->title = 'Football Test';
            $sport->save();
        }
        
        $this->league = new League();
        $this->league->user_id = $this->user->id;
        $this->league->sport_id = $sport->id;
        $this->league->league_rule_id = \Illuminate\Support\Facades\DB::table('league_rules')->value('id') ?? 1;
        $this->league->title = 'Test League';
        $this->league->number_of_team = 2;
        $this->league->save();
    }

    protected function auth()
    {
        Sanctum::actingAs($this->user);
        $this->actingAs($this->user, 'api');
    }

    public function test_can_create_team()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('league_teams')) {
            $this->markTestSkipped('Backend schema issue: league_teams table not found');
        }

        $this->auth();

        \App\Models\Player::firstOrCreate(['id' => 1], ['name' => 'Test', 'number' => 1]);
        \App\Models\Player::firstOrCreate(['id' => 2], ['name' => 'Test', 'number' => 2]);

        $response = $this->postJson('/api/create-team', [
            'team_name' => 'Demo Team',
            'playerid' => [1, 2],
            'playertype' => ['QB', 'RB']
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('league_teams', ['team_name' => 'Demo Team']);
    }

    public function test_can_list_teams()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('league_teams')) {
            $this->markTestSkipped('Backend schema issue: league_teams table not found');
        }

        $this->auth();

        $team = Team::where('name', 'Sample Team')->first();
        if (!$team) {
            $team = new Team();
            $team->name = 'Sample Team';
            $team->save();
        }

        $response = $this->getJson('/api/team-list');

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'message', 'data']);
    }

    public function test_can_view_team()
    {
        $this->auth();

        $leagueTeam = new LeagueTeam();
        $leagueTeam->league_id = $this->league->id;
        $leagueTeam->team_name = 'Alpha';
        $leagueTeam->save();

        $response = $this->getJson('/api/view-team/' . $leagueTeam->id);

        $response->assertStatus(200);
    }

    public function test_can_get_practice_team_list()
    {
        $this->auth();

        $response = $this->getJson('/api/practice-team-list/' . $this->league->id);

        $response->assertStatus(200);
    }

    public function test_can_update_team()
    {
        $this->auth();

        $leagueTeam = new LeagueTeam();
        $leagueTeam->league_id = $this->league->id;
        $leagueTeam->team_name = 'Old Name';
        $leagueTeam->save();

        $playersData = json_encode([
            [
                'player_id' => 1,
                'playertype' => 'QB',
                'name' => 'John Doe',
                'speed' => 90
            ]
        ]);

        $response = $this->postJson('/api/update-team/' . $leagueTeam->id, [
            'team_name' => 'New Awesome Team',
            'league_id' => $this->league->id,
            'players' => $playersData
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('league_teams', ['id' => $leagueTeam->id, 'team_name' => 'New Awesome Team']);
    }

    public function test_can_list_team_by_league()
    {
        $this->auth();

        $response = $this->getJson('/api/team-list-by-league/' . $this->league->id);

        $response->assertStatus(200);
    }

    public function test_can_list_team_for_play_mode()
    {
        $this->auth();

        $response = $this->getJson('/api/team-list-by-play-mode/' . $this->league->id);

        $response->assertStatus(200);
    }
}
