<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\League;
use App\Models\PlayGameMode;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MatchControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected League $league;
    protected PlayGameMode $match;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!Schema::hasTable('play_game_modes') || !Schema::hasTable('leagues')) {
            $this->markTestSkipped('Backend schema issue: required tables for match testing not found');
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

        $this->match = new PlayGameMode();
        $this->match->league_id = $this->league->id;
        $this->match->sport_id = $sportId;
        $this->match->my_team_id = 1;
        $this->match->oponent_team_id = 2;
        if (Schema::hasColumn('play_game_modes', 'user_id')) {
            $this->match->user_id = $this->user->id;
        }
        $this->match->my_team_score = 10;
        $this->match->oponent_team_score = 5;
        $this->match->save();
    }

    protected function auth()
    {
        Sanctum::actingAs($this->user);
        $this->actingAs($this->user, 'api');
    }

    public function test_can_get_matches_index()
    {
        $this->auth();

        $response = $this->getJson('/api/leagues/' . $this->league->id . '/matches');

        $response->assertStatus(200);
    }

    public function test_can_update_match_score()
    {
        $this->auth();

        $response = $this->putJson('/api/leagues/' . $this->league->id . '/matches/' . $this->match->id, [
            'my_team_score' => 20,
            'oponent_team_score' => 15
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('play_game_modes', [
            'id' => $this->match->id,
            'my_team_score' => 20,
            'oponent_team_score' => 15
        ]);
    }
}
