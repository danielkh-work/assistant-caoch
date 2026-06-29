<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sport;
use App\Models\League;
use App\Models\LeagueTeam;
use App\Models\Game;
use App\Models\PlayGameMode;
use App\Models\Device;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GameModeTest extends TestCase
{
    use DatabaseTransactions;

    protected Sport $sport;

    protected function setUp(): void
    {
        parent::setUp();

        $sport = Sport::first();

        if (!$sport) {
            $sport = new Sport();
            $sport->title = 'Football';
            $sport->save();
        }

        $this->sport = $sport;
    }

    protected function createHeadCoachUser(): User
    {
        $user = new User();
        $user->name = 'Coach GameMode';
        $user->email = 'coach_' . uniqid() . '@test.com';
        $user->password = Hash::make('12345678');
        $user->role = 'head_coach';
        $user->status = 'approved';
        $user->sport_id = $this->sport->id;
        $user->save();

        return $user;
    }

    protected function createLeagueWithTeams(User $user): array
    {
        $league = new League();
        $league->user_id = $user->id;
        $league->sport_id = $user->sport_id;
        $league->league_rule_id = DB::table('league_rules')->value('id') ?? 1;
        $league->title = 'Game Mode League';
        $league->practice_number_players = 7;
        $league->number_of_team = 2;
        $league->save();

        $team1 = new LeagueTeam();
        $team1->league_id = $league->id;
        $team1->team_name = 'Alpha';
        $team1->save();

        $team2 = new LeagueTeam();
        $team2->league_id = $league->id;
        $team2->team_name = 'Beta';
        $team2->save();

        return [$league, $team1, $team2];
    }

    protected function createRegisteredDeviceForLeague(League $league, User $user): Device
    {
        $device = new Device();
        $device->device_name = 'Test Device';
        $device->pairing_code = Device::generateUniquePairingCode();
        $device->qr_token = Device::generateUniqueQrToken();
        $device->status = 'registered';
        $device->user_id = $user->id;
        $device->paired_at = now();
        $device->save();

        $league->devices()->attach($device->id);

        return $device;
    }

    protected function authAsCoach(): User
    {
        $user = $this->createHeadCoachUser();

        Sanctum::actingAs($user);
        $this->actingAs($user, 'api');

        return $user;
    }

    public function test_can_start_game_mode()
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);
        $this->createRegisteredDeviceForLeague($league, $user);

        $response = $this->postJson('/api/start-game-mode', [
            'league_id' => $league->id,
            'my_team_id' => $team1->id,
            'oponent_team_id' => $team2->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('play_game_modes', [
            'league_id' => $league->id,
        ]);
    }

    public function test_can_add_play_game_log()
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);

        $mode = new PlayGameMode();
        $mode->sport_id = $user->sport_id;
        $mode->league_id = $league->id;
        $mode->my_team_id = $team1->id;
        $mode->oponent_team_id = $team2->id;
        $mode->save();

        $playerId = DB::table('players')->value('id');

        $response = $this->postJson('/api/add-play-game-log', [
            'game_id' => $mode->id,
            'league_id' => $league->id,
            'my_team_id' => $team1->id,
            'oponent_team_id' => $team2->id,
            'is_practice' => false,
            'players' => [
                ['player_id' => $playerId]
            ],
            'quater' => 1,
            'downs' => 1,
            'play_id' => 1,
            'weather_status' => 'sunny',
            'current_position' => '50',
            'target' => '10',
            'my_points' => 0,
            'oponent_points' => 0,
            'time' => '10:00',
            'type_of_log' => 'play',
        ]);

        $response->dump()->assertStatus(200);
    }

    public function test_can_add_points_update_state()
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);

        $mode = new PlayGameMode();
        $mode->sport_id = $user->sport_id;
        $mode->league_id = $league->id;
        $mode->my_team_id = $team1->id;
        $mode->oponent_team_id = $team2->id;
        $mode->save();

        $playerId = DB::table('players')->value('id');

        $response = $this->postJson('/api/add-points-update-state', [[
            'game_id' => $mode->id,
            'league_id' => $league->id,
            'player_id' => $playerId,
            'my_team_id' => $team1->id,
            'oponent_team_id' => $team2->id,
            'quater' => 1,
            'downs' => 1,
            'weather_status' => 'sunny',
            'current_position' => '50',
            'target' => '10',
            'my_points' => 0,
            'oponent_points' => 0,
            'time' => '10:00',
            'play_id' => 1,
            'type_of_log' => 'play',
        ]]);

        $response->dump()->assertStatus(200);
    }

    public function test_can_create_penalty_and_get_penalty_list()
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);

        $game = new Game();
        $game->league_id = $league->id;
        $game->my_team_id = $team1->id;
        $game->oponent_team_id = $team2->id;
        $game->date = now();
        $game->creator_id = $user->id;
        $game->save();

        $penaltyTypeId = 1;

        $response = $this->postJson('/api/penalities', [
            'league_id' => $league->id,
            'game_id' => $game->id,
            'penalty_type_id' => $penaltyTypeId,
            'category' => 'personal',
            'severity' => 1,
            'yardage_penalty' => 10,
        ]);

        $response->assertStatus(200);

        $this->getJson('/api/penalty-list?league_id='.$league->id.'&game_id='.$game->id)
            ->assertStatus(200);
    }

    public function test_can_broadcast_practice_scoreboard()
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);

        $mode = new PlayGameMode();
        $mode->sport_id = $user->sport_id;
        $mode->league_id = $league->id;
        $mode->my_team_id = $team1->id;
        $mode->oponent_team_id = $team2->id;
        $mode->save();

        $response = $this->postJson('/api/practice/scoreboard/broadcast', [
            'game_id' => $mode->id,
            'team' => 'left',
             'teamLeftScore' => 0,
             'teamRightScore' => 0,
             'operation' => 'add',
            'points' => 7,
            'action' => 'add',
            'isStartTime' => true,
            'time' => '10:00',
            'quarter' => 1,
            'down' => 1,
            'teamPosition' => 'home',
            'expectedyardgain' => 10,
            'positionNumber' => 1,
            'pkg' => 'test',
            'strategies' => 'test',
            'possession' => 'home',
        ]);

        $response->dump()->assertStatus(200);
    }

    public function test_can_broadcast_scoreboard()
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);

        $mode = new PlayGameMode();
        $mode->sport_id = $user->sport_id;
        $mode->league_id = $league->id;
        $mode->my_team_id = $team1->id;
        $mode->oponent_team_id = $team2->id;
        $mode->save();

        $response = $this->postJson('/api/scoreboard/broadcast', [
            'game_id' => $mode->id,
            'team' => 'left',
             'teamLeftScore' => 0,
             'teamRightScore' => 0,
             'operation' => 'add',
            'points' => 3,
            'action' => 'add',
            'sync_time' => 123456,
            'isStartTime' => true,
            'time' => '10:00',
            'quarter' => 1,
            'down' => 1,
            'teamPosition' => 'home',
            'expectedyardgain' => 10,
            'positionNumber' => 1,
            'pkg' => 'test',
            'strategies' => 'test',
            'possession' => 'home',
        ]);

        $response->dump()->assertStatus(200);
    }
}