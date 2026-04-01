<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\League;
use App\Models\LeagueTeam;
use App\Models\Game;
use App\Models\PlayGameMode;
use App\Models\PlayGameLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

class GameModeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clean database tables
        $tables = [
            'play_game_modes',
            'play_game_logs',
            'games',
            'league_teams',
            'leagues',
            'users',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }

    protected function createHeadCoachUser(): User
    {
        return User::create([
            'name' => 'Coach GameMode',
            'email' => 'coach_gamemode@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'head_coach',
            'status' => 'approved',
            'sport_id' => 1, // MUST set sport_id to avoid SQL errors
        ]);
    }

    protected function createLeagueWithTeams(User $user): array
    {
        $league = League::forceCreate([
            'user_id' => $user->id,
            'sport_id' => $user->sport_id,
            'league_rule_id' => 1,
            'title' => 'Game Mode League',
            'practice_number_players' => 7,
            'number_of_team' => 2,
        ]);

        $team1 = LeagueTeam::forceCreate(['league_id' => $league->id, 'team_name' => 'Alpha']);
        $team2 = LeagueTeam::forceCreate(['league_id' => $league->id, 'team_name' => 'Beta']);

        return [$league, $team1, $team2];
    }

    protected function authAsCoach(): User
    {
        $user = $this->createHeadCoachUser();
        // Authenticate with Sanctum for API
        Sanctum::actingAs($user, ['*']);
        $this->actingAs($user, 'api');
        return $user;
    }

    public function test_can_start_game_mode()
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);

        $response = $this->postJson('/api/start-game-mode', [
            'league_id' => $league->id,
            'my_team_id' => $team1->id,
            'oponent_team_id' => $team2->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('play_game_modes', [
            'league_id' => $league->id,
            'my_team_id' => $team1->id,
            'oponent_team_id' => $team2->id,
            'sport_id' => $user->sport_id,
        ]);
    }

    public function test_can_add_play_game_log()
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);

        $mode = PlayGameMode::create([
            'sport_id' => $user->sport_id,
            'league_id' => $league->id,
            'my_team_id' => $team1->id,
            'oponent_team_id' => $team2->id,
            'user_id' => $user->id,
            'status' => 0,
        ]);

        $response = $this->postJson('/api/add-play-game-log', [
            'game_id' => $mode->id,
            'league_id' => $league->id,
            'my_team_id' => $team1->id,
            'oponent_team_id' => $team2->id,
            'is_practice' => false,
            'players' => [['player_id' => 1]],
            'is_confirmed' => true,
            'quater' => 1,
            'play_id' => 1,
            'downs' => 1,
            'weather_status' => 'sunny',
            'current_position' => 'QB',
            'target' => 'Goal',
            'my_points' => 7,
            'oponent_points' => 0,
            'time' => '00:10',
            'type_of_log' => 'play',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('play_game_logs', ['game_id' => $mode->id]);
    }

    public function test_can_add_points_update_state()
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);

        $mode = PlayGameMode::create([
            'sport_id' => $user->sport_id,
            'league_id' => $league->id,
            'my_team_id' => $team1->id,
            'oponent_team_id' => $team2->id,
            'user_id' => $user->id,
            'status' => 0,
        ]);

        $payload = [[
            'game_id' => $mode->id,
            'league_id' => $league->id,
            'player_id' => 1,
            'my_team_id' => $team1->id,
            'oponent_team_id' => $team2->id,
            'quater' => 1,
            'downs' => 1,
            'weather_status' => 'rain',
            'current_position' => 'RB',
            'target' => 'First Down',
            'my_points' => 3,
            'oponent_points' => 0,
            'time' => '00:15',
            'reasons' => 'Great play',
            'type_of_log' => 'play',
        ]];

        $response = $this->postJson('/api/add-points-update-state', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('play_game_logs', ['game_id' => $mode->id]);
    }

    public function test_can_create_penalty_and_get_penalty_list()
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);

        $game = Game::create([
            'league_id' => $league->id,
            'my_team_id' => $team1->id,
            'oponent_team_id' => $team2->id,
            'date' => now()->toDateString(),
            'location_type' => 'home',
            'creator_id' => $user->id,
        ]);

        $response = $this->postJson('/api/penalities', [
            'league_id' => $league->id,
            'game_id' => $game->id,
            'penalty_type_id' => 1,
            'category' => 'personal',
            'severity' => 1,
            'yardage_penalty' => 10,
        ]);

        $response->assertStatus(200);

        $list = $this->getJson('/api/penalty-list?league_id=' . $league->id . '&game_id=' . $game->id);
        $list->assertStatus(200);
    }

    public function test_can_broadcast_practice_scoreboard_and_get_practice_scoreboard()
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);

        $mode = PlayGameMode::create([
            'league_id' => $league->id,
            'my_team_id' => $team1->id,
            'oponent_team_id' => $team2->id,
            'user_id' => $user->id,
            'sport_id' => $user->sport_id,
            'status' => 0,
        ]);

        $response = $this->postJson('/api/practice/scoreboard/broadcast', [
            'team' => 'left',
            'points' => 7,
            'action' => 'touchdown',
            'game_id' => $mode->id,
            'teamLeftScore' => 0,
            'teamRightScore' => 0,
            'quarter' => 1,
            'isStartTime' => true,
            'down' => 1,
            'teamPosition' => 'left',
            'expectedyardgain' => 10,
            'positionNumber' => 20,
            'pkg' => 'A',
            'strategies' => 'run',
            'possession' => 'left',
            'time' => '00:12',
        ]);

        $response->assertStatus(200);

        $view = $this->getJson('/api/practice-scoreboard');
        $view->assertStatus(200);
    }

    public function test_can_broadcast_scoreboard_and_get_scoreboard()
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);

        $mode = PlayGameMode::create([
            'league_id' => $league->id,
            'my_team_id' => $team1->id,
            'oponent_team_id' => $team2->id,
            'user_id' => $user->id,
            'sport_id' => $user->sport_id,
            'status' => 0,
        ]);

        $response = $this->postJson('/api/scoreboard/broadcast', [
            'team' => 'left',
            'points' => 3,
            'action' => 'field_goal',
            'game_id' => $mode->id,
            'teamLeftScore' => 0,
            'teamRightScore' => 0,
            'quarter' => 1,
            'isStartTime' => true,
            'down' => 1,
            'teamPosition' => 'left',
            'expectedyardgain' => 10,
            'positionNumber' => 20,
            'pkg' => 'A',
            'strategies' => 'run',
            'possession' => 'left',
            'time' => '00:12',
        ]);

        $response->assertStatus(200);

        $view = $this->getJson('/api/scoreboard');
        $view->assertStatus(200);
    }
}
