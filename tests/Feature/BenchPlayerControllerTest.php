<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\Player;
use App\Models\TeamPlayer;
use App\Models\LeagueTeam;
use App\Models\League;
use App\Models\Game;
use App\Models\BenchPlayer;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class BenchPlayerControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected League $league;
    protected LeagueTeam $team;
    protected Game $game;

    protected function setUp(): void
    {
        parent::setUp();
        
        $missingTables = [];
        if (!Schema::hasTable('league_teams')) $missingTables[] = 'league_teams';
        if (!Schema::hasTable('players')) $missingTables[] = 'players';
        if (!Schema::hasTable('offense_defense_players')) $missingTables[] = 'offense_defense_players';
        
        if (!empty($missingTables)) {
            $this->markTestSkipped('Backend schema issue: required tables not found: ' . implode(', ', $missingTables));
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

        $this->team = new LeagueTeam();
        $this->team->team_name = 'Test Team';
        $this->team->league_id = $this->league->id;
        $this->team->save();

        $this->game = new Game();
        $this->game->league_id = $this->league->id;
        $this->game->creator_id = $this->user->id;
        $this->game->my_team_id = $this->team->id;
        $this->game->oponent_team_id = $this->team->id;
        $this->game->date = now()->toDateString();
        $this->game->location_type = 'home';
        $this->game->save();
    }

    protected function auth()
    {
        Sanctum::actingAs($this->user);
        $this->actingAs($this->user, 'api');
    }

    protected function createPlayer($name)
    {
        $basePlayer = new Player();
        $basePlayer->name = $name;
        $basePlayer->user_id = $this->user->id;
        $basePlayer->number = 10;
        $basePlayer->position = 'QB';
        $basePlayer->size = 70;
        $basePlayer->speed = 80;
        $basePlayer->strength = 80;
        $basePlayer->weight = 200;
        $basePlayer->height = 180;
        $basePlayer->save();

        $player = new TeamPlayer();
        $player->player_id = $basePlayer->id;
        $player->team_id = $this->team->id;
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
            'gameId' => $this->game->id,
            'isPractice' => false
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('offense_defense_players', [
            'player_id' => $player->id,
            'team_id' => $this->team->id,
            'game_id' => $this->game->id
        ]);
    }

    public function test_can_update_rpp()
    {
        $this->auth();

        $player = $this->createPlayer('Player 99');

        $benchPlayer = new BenchPlayer();
        $benchPlayer->league_id = $this->league->id;
        $benchPlayer->game_id = $this->game->id;
        $benchPlayer->team_id = $this->team->id;
        $benchPlayer->player_id = $player->id;
        $benchPlayer->rpp = 50;
        $benchPlayer->save();

        $response = $this->putJson('/api/bench/' . $benchPlayer->id . '/update', [
            'rpp' => 88
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('offense_defense_players', [
            'id' => $benchPlayer->id,
            'rpp' => 88
        ]);
    }

    public function test_can_get_bench_count()
    {
        $this->auth();

        $player = $this->createPlayer('Player 99');

        $benchPlayer = new BenchPlayer();
        $benchPlayer->league_id = $this->league->id;
        $benchPlayer->game_id = $this->game->id;
        $benchPlayer->team_id = $this->team->id;
        $benchPlayer->player_id = $player->id;
        $benchPlayer->rpp = 50;
        $benchPlayer->save();

        $response = $this->getJson('/api/bench-players_count/' . $this->game->id);

        $response->assertStatus(200)
                 ->assertJsonFragment(['count' => 1]);
    }
}
