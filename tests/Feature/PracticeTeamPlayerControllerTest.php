<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\LeagueTeam;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Schema;

class PracticeTeamPlayerControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!Schema::hasTable('league_teams') || !Schema::hasTable('practice_team_players')) {
            $this->markTestSkipped('Backend schema issue: required practice tables not found');
        }

        $this->user = User::factory()->create([
            'role' => 'head_coach',
            'status' => 'approved'
        ]);
    }

    protected function auth()
    {
        Sanctum::actingAs($this->user);
        $this->actingAs($this->user, 'api');
    }

    public function test_can_update_practice_team_players()
    {
        $this->auth();

        $team = new LeagueTeam();
        $team->league_id = 999;
        $team->team_name = 'Original Practice Team';
        $team->save();

        $playersData = json_encode([
            [
                'player_id' => 101,
                'name' => 'Practice Dude',
                'target' => 'WR',
                'ofp' => 100,
                'number' => 88
            ]
        ]);

        $response = $this->postJson('/api/practice-update-team/' . $team->id, [
            'team_name' => 'Updated Practice Team',
            'players' => $playersData
        ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('league_teams', [
            'id' => $team->id,
            'team_name' => 'Updated Practice Team'
        ]);

        $this->assertDatabaseHas('practice_team_players', [
            'team_id' => $team->id,
            'player_id' => 101,
            'name' => 'Practice Dude'
        ]);
    }
}
