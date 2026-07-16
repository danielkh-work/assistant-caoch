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

    public function test_get_games_by_league_returns_all_statuses_without_status_filter()
    {
        if (! Schema::hasColumn('games', 'status')) {
            $this->markTestSkipped('Games status column is not available.');
        }

        $this->auth();

        $endedGame = $this->createGameForLeague(['status' => 'ended']);
        $startedGame = $this->createGameForLeague(['status' => 'started']);

        $response = $this->getJson('/api/games/league/' . $this->league->id);

        $response->assertStatus(200);

        $gameIds = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($endedGame->id, $gameIds);
        $this->assertContains($startedGame->id, $gameIds);
    }

    public function test_get_games_by_league_can_filter_not_ended_games()
    {
        if (! Schema::hasColumn('games', 'status')) {
            $this->markTestSkipped('Games status column is not available.');
        }

        $this->auth();

        $endedGame = $this->createGameForLeague(['status' => 'ended']);
        $startedGame = $this->createGameForLeague(['status' => 'started']);
        $scheduledGame = $this->createGameForLeague(['status' => null]);

        $response = $this->getJson('/api/games/league/' . $this->league->id . '?status=not-ended');

        $response->assertStatus(200);

        $gameIds = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($endedGame->id, $gameIds);
        $this->assertContains($startedGame->id, $gameIds);
        $this->assertContains($scheduledGame->id, $gameIds);
    }

    public function test_can_get_searchable_non_practice_opponent_teams()
    {
        if (! Schema::hasTable('league_teams')) {
            $this->markTestSkipped('League teams table is not available.');
        }

        $this->auth();

        $normalTeam = [
            'league_id' => $this->league->id,
            'team_name' => 'CNDF Notre Dame',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $practiceTeam = [
            'league_id' => $this->league->id,
            'team_name' => 'Practice offence',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $otherTeam = [
            'league_id' => $this->league->id,
            'team_name' => 'Cougars Lennoxville',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $myTeam = [
            'league_id' => $this->league->id,
            'team_name' => 'Giants St-Jean-sur-Le-Richelieu',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('league_teams', 'is_practice')) {
            $normalTeam['is_practice'] = 0;
            $practiceTeam['is_practice'] = 1;
            $otherTeam['is_practice'] = 0;
            $myTeam['is_practice'] = 0;
        }

        if (Schema::hasColumn('league_teams', 'type')) {
            $normalTeam['type'] = null;
            $practiceTeam['type'] = 1;
            $otherTeam['type'] = null;
            $myTeam['type'] = 1;
        }

        $normalTeamId = DB::table('league_teams')->insertGetId($normalTeam);
        DB::table('league_teams')->insert($practiceTeam);
        DB::table('league_teams')->insert($otherTeam);
        $myTeamId = DB::table('league_teams')->insertGetId($myTeam);

        $response = $this->getJson('/api/leagues/' . $this->league->id . '/opponent-teams?search=CNDF');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Opponent teams list')
            ->assertJsonFragment([
                'id' => $normalTeamId,
                'team_name' => 'CNDF Notre Dame',
            ])
            ->assertJsonMissing([
                'team_name' => 'Practice offence',
            ])
            ->assertJsonMissing([
                'team_name' => 'Cougars Lennoxville',
            ]);

        if (Schema::hasColumn('league_teams', 'is_practice') && Schema::hasColumn('league_teams', 'type')) {
            $allResponse = $this->getJson('/api/leagues/' . $this->league->id . '/opponent-teams');

            $allResponse->assertStatus(200)
                ->assertJsonMissing([
                    'team_name' => 'Practice offence',
                ])
                ->assertJsonMissing([
                    'team_name' => 'Giants St-Jean-sur-Le-Richelieu',
                ]);
        }
    }

    public function test_can_duplicate_game_with_match_setup_rows()
    {
        if (! Schema::hasTable('configured_playing_team_players') || ! Schema::hasTable('personal_groupings')) {
            $this->markTestSkipped('Duplicate game setup tables are not available.');
        }

        $this->auth();

        if (Schema::hasColumn('games', 'status')) {
            $this->game->status = 'ended';
        }

        if (Schema::hasColumn('games', 'match_start_date')) {
            $this->game->match_start_date = now()->subHour();
        }

        if (Schema::hasColumn('games', 'match_end_date')) {
            $this->game->match_end_date = now();
        }

        $this->game->save();

        $configuredPlayer = [
            'match_id' => $this->game->id,
            'team_id' => 1,
            'player_id' => 10,
            'type' => 'offensive',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('configured_playing_team_players', 'practice_player_id')) {
            $configuredPlayer['practice_player_id'] = null;
        }

        if (Schema::hasColumn('configured_playing_team_players', 'team_type')) {
            $configuredPlayer['team_type'] = 1;
        }

        if (Schema::hasColumn('configured_playing_team_players', 'game_type')) {
            $configuredPlayer['game_type'] = 1;
        }

        DB::table('configured_playing_team_players')->insert($configuredPlayer);

        $group = [
            'game_id' => $this->game->id,
            'league_id' => $this->league->id,
            'team_id' => 1,
            'group_name' => 'Test Group',
            'type' => 'Offense',
            'players' => json_encode([['id' => 10]]),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('personal_groupings', 'practice_players')) {
            $group['practice_players'] = null;
        }

        if (Schema::hasColumn('personal_groupings', 'group_level')) {
            $group['group_level'] = 1;
        }

        if (Schema::hasColumn('personal_groupings', 'status')) {
            $group['status'] = 'inactive';
        }

        DB::table('personal_groupings')->insert($group);

        if (Schema::hasTable('play_game_logs')) {
            DB::table('play_game_logs')->insert([
                'game_id' => $this->game->id,
                'league_id' => $this->league->id,
                'my_team_id' => 1,
                'oponent_team_id' => 2,
                'time' => '12:00',
                'type_of_log' => 'event',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('play_results')) {
            DB::table('play_results')->insert([
                'game_id' => $this->game->id,
                'play_id' => 1,
                'type' => 'offensive',
                'result' => 'win',
                'suggested_count' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('penalities')) {
            DB::table('penalities')->insert([
                'league_id' => $this->league->id,
                'game_id' => $this->game->id,
                'penalty_type_id' => 1,
                'yardage_penalty' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->postJson('/api/games/' . $this->game->id . '/duplicate');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Game duplicated successfully.');

        $newGameId = $response->json('data.id');
        $this->assertNotEquals($this->game->id, $newGameId);

        $expectedGame = [
            'id' => $newGameId,
            'league_id' => $this->league->id,
        ];

        if (Schema::hasColumn('games', 'status')) {
            $expectedGame['status'] = null;
        }

        $this->assertDatabaseHas('games', $expectedGame);

        $this->assertDatabaseHas('configured_playing_team_players', [
            'match_id' => $newGameId,
            'team_id' => 1,
            'player_id' => 10,
            'type' => 'offensive',
        ]);

        $this->assertDatabaseHas('personal_groupings', [
            'game_id' => $newGameId,
            'league_id' => $this->league->id,
            'team_id' => 1,
            'group_name' => 'Test Group',
        ]);

        if (Schema::hasTable('play_game_logs')) {
            $this->assertSame(0, DB::table('play_game_logs')->where('game_id', $newGameId)->count());
        }

        if (Schema::hasTable('play_results')) {
            $this->assertSame(0, DB::table('play_results')->where('game_id', $newGameId)->count());
        }

        if (Schema::hasTable('penalities')) {
            $this->assertSame(0, DB::table('penalities')->where('game_id', $newGameId)->count());
        }
    }

    public function test_duplicate_game_with_new_opponent_keeps_my_team_setup_only()
    {
        if (
            ! Schema::hasTable('league_teams') ||
            ! Schema::hasTable('configured_playing_team_players') ||
            ! Schema::hasTable('personal_groupings')
        ) {
            $this->markTestSkipped('Duplicate game setup tables are not available.');
        }

        $this->auth();

        $teamRow = fn (string $name) => [
            'league_id' => $this->league->id,
            'team_name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $myTeam = $teamRow('My Team');
        $oldOpponent = $teamRow('Old Opponent');
        $newOpponent = $teamRow('New Opponent');

        if (Schema::hasColumn('league_teams', 'is_practice')) {
            $myTeam['is_practice'] = 0;
            $oldOpponent['is_practice'] = 0;
            $newOpponent['is_practice'] = 0;
        }

        $myTeamId = DB::table('league_teams')->insertGetId($myTeam);
        $oldOpponentId = DB::table('league_teams')->insertGetId($oldOpponent);
        $newOpponentId = DB::table('league_teams')->insertGetId($newOpponent);

        $this->game->my_team_id = $myTeamId;
        $this->game->oponent_team_id = $oldOpponentId;
        $this->game->save();

        $this->insertConfiguredPlayerForDuplicateTest($this->game->id, $myTeamId, 101, 1, 'offensive');
        $this->insertConfiguredPlayerForDuplicateTest($this->game->id, $oldOpponentId, 202, 2, 'defensive');

        DB::table('personal_groupings')->insert($this->personalGroupForDuplicateTest(
            $this->game->id,
            $myTeamId,
            'My Team Group',
            101
        ));

        DB::table('personal_groupings')->insert($this->personalGroupForDuplicateTest(
            $this->game->id,
            $oldOpponentId,
            'Old Opponent Group',
            202
        ));

        $response = $this->postJson('/api/games/' . $this->game->id . '/duplicate', [
            'date' => '2026-08-20 19:30:00',
            'opponent_team_id' => $newOpponentId,
        ]);

        $response->assertStatus(200);

        $newGameId = $response->json('data.id');
        $duplicatedGame = Game::find($newGameId);

        $this->assertSame($newOpponentId, (int) $duplicatedGame->oponent_team_id);
        $this->assertStringStartsWith('2026-08-20', (string) $duplicatedGame->date);

        $this->assertDatabaseHas('configured_playing_team_players', [
            'match_id' => $newGameId,
            'team_id' => $myTeamId,
            'player_id' => 101,
        ]);

        $this->assertDatabaseMissing('configured_playing_team_players', [
            'match_id' => $newGameId,
            'team_id' => $oldOpponentId,
            'player_id' => 202,
        ]);

        $this->assertDatabaseHas('personal_groupings', [
            'game_id' => $newGameId,
            'team_id' => $myTeamId,
            'group_name' => 'My Team Group',
        ]);

        $this->assertDatabaseMissing('personal_groupings', [
            'game_id' => $newGameId,
            'team_id' => $oldOpponentId,
            'group_name' => 'Old Opponent Group',
        ]);
    }

    private function insertConfiguredPlayerForDuplicateTest(
        int $gameId,
        int $teamId,
        int $playerId,
        int $teamType,
        string $type
    ): void {
        $row = [
            'match_id' => $gameId,
            'team_id' => $teamId,
            'player_id' => $playerId,
            'type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('configured_playing_team_players', 'practice_player_id')) {
            $row['practice_player_id'] = null;
        }

        if (Schema::hasColumn('configured_playing_team_players', 'team_type')) {
            $row['team_type'] = $teamType;
        }

        if (Schema::hasColumn('configured_playing_team_players', 'game_type')) {
            $row['game_type'] = 1;
        }

        DB::table('configured_playing_team_players')->insert($row);
    }

    private function personalGroupForDuplicateTest(int $gameId, int $teamId, string $groupName, int $playerId): array
    {
        $row = [
            'game_id' => $gameId,
            'league_id' => $this->league->id,
            'team_id' => $teamId,
            'group_name' => $groupName,
            'type' => 'Offense',
            'players' => json_encode([['id' => $playerId]]),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('personal_groupings', 'practice_players')) {
            $row['practice_players'] = null;
        }

        if (Schema::hasColumn('personal_groupings', 'group_level')) {
            $row['group_level'] = 1;
        }

        if (Schema::hasColumn('personal_groupings', 'status')) {
            $row['status'] = 'inactive';
        }

        return $row;
    }

    private function createGameForLeague(array $attributes = []): Game
    {
        $game = new Game();
        $game->league_id = $this->league->id;
        $game->creator_id = $this->user->id;
        $game->my_team_id = $attributes['my_team_id'] ?? 1;
        $game->oponent_team_id = $attributes['oponent_team_id'] ?? 2;
        $game->date = $attributes['date'] ?? now()->toDateString();
        $game->location_type = $attributes['location_type'] ?? 'home';

        if (array_key_exists('status', $attributes) && Schema::hasColumn('games', 'status')) {
            $game->status = $attributes['status'];
        }

        if (array_key_exists('type', $attributes) && Schema::hasColumn('games', 'type')) {
            $game->type = $attributes['type'];
        }

        $game->save();

        return $game;
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
