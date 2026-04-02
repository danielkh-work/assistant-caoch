<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\Player;
use App\Models\Team;
use App\Models\League;
use App\Models\BenchPlayer;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class BenchPlayerControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected League $league;
    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!Schema::hasTable('teams') || !Schema::hasTable('players') || !Schema::hasTable('bench_players')) {
            $this->markTestSkipped('Backend schema issue: required tables for bench testing not found');
        }

        $this->user = User::factory()->create([
            'role' => 'head_coach',
            'status' => 'approved'
        ]);

        $sportId = DB::table('sports')->insertGetId(['title' => 'Test Sport']);
        
        $this->league = new League();
        $this->league->user_id = $this->user->id;
        $this->league->sport_id = $sportId;
        $this->league->title = 'Test League';
        $this->league->number_of_team = 2;
        $this->league->save();

        $this->team = new Team();
        $this->team->name = 'Test Team';
        $this->team->save();
    }

    protected function auth()
    {
        Sanctum::actingAs($this->user);
        $this->actingAs($this->user, 'api');
    }

    protected function createPlayer($name)
    {
        $player = new Player();
        $player->name = $name;
        $player->user_id = $this->user->id;
        $player->number = 10;
        $player->position = 'QB';
        $player->size = 70;
        $player->speed = 80;
        $player->strength = 80;
        $player->weight = 200;
        $player->height = 180;
        $player->save();
        return $player;
    }

    public function test_can_store_bench_players()
    {
        $this->auth();

        $player = $this->createPlayer('Bench Dude');

        $response = $this->postJson('/api/bench-players', [
            'benchPlayers' => [
                [
                    'id' => $player->id,
                    'positionSelect' => 'QB',
                    'rpp' => 85
                ]
            ],
            'teamId' => $this->team->id,
            'playerType' => 'offense',
            'leagueId' => $this->league->id,
            'gameId' => 1,
            'isPractice' => false
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('bench_players', [
            'player_id' => $player->id,
            'team_id' => $this->team->id,
            'game_id' => 1
        ]);
    }

    public function test_can_update_rpp()
    {
        $this->auth();

        $benchPlayer = new BenchPlayer();
        $benchPlayer->game_id = 1;
        $benchPlayer->team_id = $this->team->id;
        $benchPlayer->player_id = 99;
        $benchPlayer->rpp = 50;
        $benchPlayer->save();

        $response = $this->putJson('/api/bench/' . $benchPlayer->id . '/update', [
            'rpp' => 88
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('bench_players', [
            'id' => $benchPlayer->id,
            'rpp' => 88
        ]);
    }

    public function test_can_get_bench_count()
    {
        $this->auth();

        $benchPlayer = new BenchPlayer();
        $benchPlayer->game_id = 2;
        $benchPlayer->team_id = $this->team->id;
        $benchPlayer->player_id = 99;
        $benchPlayer->rpp = 50;
        $benchPlayer->save();

        $response = $this->getJson('/api/bench-players_count/2');

        $response->assertStatus(200)
                 ->assertJsonFragment(['count' => 1]);
    }
}
