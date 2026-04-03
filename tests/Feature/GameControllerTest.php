<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\League;
use App\Models\Game;
use App\Models\Penality;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class GameControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected League $league;
    protected Game $game;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!Schema::hasTable('games') || !Schema::hasTable('penalities')) {
            $this->markTestSkipped('Backend schema issue: required tables for game testing not found');
        }

        $this->user = User::factory()->create([
            'role' => 'head_coach',
            'status' => 'approved'
        ]);

        $sportId = DB::table('sports')->insertGetId(['title' => 'Test Sport']);
        
        $this->league = new League();
        $this->league->user_id = $this->user->id;
        $this->league->sport_id = $sportId;
        $this->league->league_rule_id = \Illuminate\Support\Facades\DB::table('league_rules')->value('id') ?? 1;
        $this->league->title = 'Test League';
        $this->league->number_of_team = 2;
        $this->league->save();

        $this->game = new Game();
        $this->game->league_id = $this->league->id;
        $this->game->creator_id = $this->user->id;
        $this->game->my_team_id = 1;
        $this->game->oponent_team_id = 2;
        $this->game->date = now()->toDateString();
        $this->game->location_type = 'home';
        $this->game->save();
    }

    protected function auth()
    {
        Sanctum::actingAs($this->user);
        $this->actingAs($this->user, 'api');
    }

    public function test_can_create_game()
    {
        $this->auth();

        $response = $this->postJson('/api/games', [
            'league_id' => $this->league->id,
            'my_team_id' => 1,
            'oponent_team_id' => 2,
            'date' => now()->toDateString(),
            'location' => 'Home Stadium',
            'location_type' => 'home'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('games', [
            'league_id' => $this->league->id,
            'location_type' => 'home',
            'location' => 'Home Stadium'
        ]);
    }

    public function test_can_get_game_index()
    {
        $this->auth();

        $response = $this->getJson('/api/games/id');

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'message', 'data']);
    }

    public function test_can_show_game()
    {
        $this->auth();

        $response = $this->getJson('/api/game/' . $this->game->id);

        $response->assertStatus(200);
    }

    public function test_can_get_opponent_my_team_players()
    {
        $this->auth();

        $response = $this->getJson('/api/game/' . $this->game->id . '/opponents_my');

        $response->assertStatus(200);
    }

    public function test_can_get_games_by_league()
    {
        $this->auth();

        $response = $this->getJson('/api/games/league/' . $this->league->id);

        $response->assertStatus(200);
    }

    public function test_can_add_penalty()
    {
        $this->auth();

        $response = $this->postJson('/api/penalities', [
            'league_id' => $this->league->id,
            'game_id' => $this->game->id,
            'penalty_type_id' => 1,
            'category' => 'Offense',
            'severity' => 'Major',
            'yardage_penalty' => 10
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('penalities', [
            'game_id' => $this->game->id,
            'category' => 'Offense',
            'yardage_penalty' => 10
        ]);
    }

    public function test_can_get_penalty_list()
    {
        $this->auth();

        $penalty = new Penality();
        $penalty->league_id = $this->league->id;
        $penalty->game_id = $this->game->id;
        $penalty->penalty_type_id = 1;
        $penalty->yardage_penalty = 5;
        $penalty->save();

        $response = $this->getJson('/api/penalty-list?league_id=' . $this->league->id . '&game_id=' . $this->game->id);

        $response->assertStatus(200);
    }

    public function test_can_clear_ground_players_at_end_game()
    {
        $this->auth();

        $response = $this->getJson('/api/end-game-clearplayers/' . $this->game->id);

        $response->assertStatus(200);
    }

    public function test_can_delete_game()
    {
        $this->auth();

        $response = $this->getJson('/api/delete-game/' . $this->game->id);

        $response->assertStatus(200);
        $this->assertNull(Game::find($this->game->id));
    }
}
