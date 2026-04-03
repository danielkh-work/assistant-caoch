<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\League;
use App\Models\ConfiguredPlayingTeamPlayer;
use App\Models\ConfigureFormation;
use App\Models\ConfigurePlay;
use App\Models\ConfigureDefensivePlay;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ConfigureControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected League $league;

    protected function setUp(): void
    {
        parent::setUp();
        
        $requiredTables = [
            'configured_playing_team_players', 
            'configure_formations', 
            'configure_plays', 
            'configure_defensive_plays'
        ];

        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("Backend schema issue: required table {$table} not found");
            }
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
    }

    protected function auth()
    {
        Sanctum::actingAs($this->user);
        $this->actingAs($this->user, 'api');
    }

    public function test_can_configure_player()
    {
        $this->auth();

        $response = $this->postJson('/api/configure-player', [
            'team_id' => 1,
            'match_id' => 1,
            'game_type' => 1,
            'player_id' => [101, 102],
            'type' => ['offense', 'defense']
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('configured_playing_team_players', [
            'team_id' => 1,
            'match_id' => 1,
            'team_type' => 1
        ]);
    }

    public function test_can_configure_visiting_player()
    {
        $this->auth();

        $response = $this->postJson('/api/configure-player-visiting', [
            'team_id' => 2,
            'match_id' => 1,
            'game_type' => 1,
            'player_id' => [201],
            'type' => ['offense']
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('configured_playing_team_players', [
            'team_id' => 2,
            'match_id' => 1,
            'team_type' => 2
        ]);
    }

    public function test_can_view_configured_players()
    {
        $this->auth();

        $player = new ConfiguredPlayingTeamPlayer();
        $player->team_id = 1;
        $player->match_id = 1;
        $player->type = 'offense';
        $player->team_type = 1;
        $player->game_type = 1;
        $player->player_id = 999;
        $player->save();

        $response = $this->getJson('/api/configure-player-view?team_id=1&game_id=1');

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'message', 'data']);
    }

    public function test_can_configure_formation()
    {
        $this->auth();

        $response = $this->postJson('/api/configure-formation', [
            'formation_id' => 10,
            'league_id' => $this->league->id
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('configure_formations', [
            'user_id' => $this->user->id,
            'league_id' => $this->league->id,
            'formation_id' => 10
        ]);
    }

    public function test_can_configure_formation_view()
    {
        $this->auth();

        $configure = new ConfigureFormation();
        $configure->user_id = $this->user->id;
        $configure->league_id = $this->league->id;
        $configure->formation_id = 10;
        $configure->save();

        $response = $this->getJson('/api/configure-formation-view?league_id=' . $this->league->id);

        $response->assertStatus(200);
    }

    public function test_can_configure_play()
    {
        $this->auth();

        $response = $this->postJson('/api/configure-play', [
            'matchId' => 2,
            'league_id' => $this->league->id,
            'play_id' => [50, 51]
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('configure_plays', [
            'match_id' => 2,
            'play_id' => 50
        ]);
    }

    public function test_can_configure_defensive_play()
    {
        $this->auth();

        $response = $this->postJson('/api/configure-defensive-play', [
            'matchId' => 2,
            'league_id' => $this->league->id,
            'play_id' => [60]
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('configure_defensive_plays', [
            'game_id' => 2,
            'play_id' => 60
        ]);
    }

    public function test_can_view_configure_plays()
    {
        $this->auth();

        $play = new ConfigurePlay();
        $play->user_id = $this->user->id;
        $play->league_id = $this->league->id;
        $play->match_id = 99;
        $play->play_id = 1;
        $play->save();

        $response = $this->getJson('/api/configure-play-view?league_id=' . $this->league->id . '&matchId=99');

        $response->assertStatus(200);
    }

    public function test_can_view_configure_defensive_plays()
    {
        $this->auth();

        $play = new ConfigureDefensivePlay();
        $play->user_id = $this->user->id;
        $play->league_id = $this->league->id;
        $play->game_id = 99;
        $play->play_id = 2;
        $play->save();

        $response = $this->getJson('/api/configure-defensive-play-view?league_id=' . $this->league->id . '&matchId=99');

        $response->assertStatus(200);
    }
}
