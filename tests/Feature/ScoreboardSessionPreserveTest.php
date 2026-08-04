<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\LeagueTeam;
use App\Models\PlayGameMode;
use App\Models\Sport;
use App\Models\User;
use App\Models\Device;
use App\Models\WebsocketPracticeScoreboard;
use App\Models\WebsocketScoreboard;
use App\Support\ActiveGameModeGuard;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ScoreboardSessionPreserveTest extends TestCase
{
    use DatabaseTransactions;

    protected Sport $sport;

    protected function setUp(): void
    {
        parent::setUp();

        $sport = Sport::first();

        if (! $sport) {
            $sport = new Sport();
            $sport->title = 'Football';
            $sport->save();
        }

        $this->sport = $sport;
    }

    protected function createHeadCoachUser(): User
    {
        $user = new User();
        $user->name = 'Coach Scoreboard';
        $user->email = 'coach_sb_' . uniqid() . '@test.com';
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
        $league->title = 'Scoreboard Session League';
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

    protected function authAsCoach(): User
    {
        $user = $this->createHeadCoachUser();

        Sanctum::actingAs($user);
        $this->actingAs($user, 'api');

        return $user;
    }

    protected function createRegisteredDeviceForLeague(League $league, User $user): Device
    {
        $device = new Device();
        $device->device_name = 'Scoreboard Device';
        $device->pairing_code = Device::generateUniquePairingCode();
        $device->qr_token = Device::generateUniqueQrToken();
        $device->status = 'registered';
        $device->user_id = $user->id;
        $device->paired_at = now();
        $device->save();

        $league->devices()->attach($device->id);

        return $device;
    }

    protected function createActiveSession(User $user, League $league, LeagueTeam $team1, LeagueTeam $team2, string $gameMode = 'play'): PlayGameMode
    {
        $mode = new PlayGameMode();
        $mode->sport_id = $user->sport_id;
        $mode->league_id = $league->id;
        $mode->my_team_id = $team1->id;
        $mode->oponent_team_id = $team2->id;
        $mode->user_id = $user->id;
        $mode->game_mode = $gameMode;
        $mode->status = ActiveGameModeGuard::STATUS_ACTIVE;
        $mode->save();

        return $mode;
    }

    protected function scoreboardBroadcastPayload(int $gameId, int $leagueId, array $overrides = []): array
    {
        return array_merge([
            'game_id' => $gameId,
            'team' => 'both',
            'teamLeftScore' => 0,
            'teamRightScore' => 0,
            'points' => 0,
            'action' => 'INFO',
            'sync_time' => 123456,
            'isStartTime' => true,
            'time' => 600,
            'quarter' => 1,
            'down' => 1,
            'teamPosition' => 'home',
            'expectedyardgain' => 10,
            'positionNumber' => 1,
            'pkg' => 'test',
            'strategies' => 'regular',
            'possession' => 'home',
            'league_id' => $leagueId,
        ], $overrides);
    }

    public function test_info_broadcast_preserves_session_id_and_is_start_when_omitted(): void
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);
        $session = $this->createActiveSession($user, $league, $team1, $team2);
        $gameId = 999;

        WebsocketScoreboard::create([
            'user_id' => $user->id,
            'game_id' => $gameId,
            'session_id' => $session->id,
            'league_id' => $league->id,
            'left_score' => 0,
            'right_score' => 0,
            'is_start' => true,
            'action' => 'Start',
        ]);

        $this->postJson('/api/scoreboard/broadcast', $this->scoreboardBroadcastPayload($gameId, $league->id, [
            'action' => 'INFO',
            'isStartTime' => false,
            'down' => 2,
        ]))->assertNoContent();

        $this->assertDatabaseHas('websocket_scoreboards', [
            'user_id' => $user->id,
            'game_id' => $gameId,
            'session_id' => $session->id,
            'is_start' => true,
            'action' => 'INFO',
        ]);
    }

    public function test_get_scoreboard_reconcile_keeps_live_row_when_league_session_active(): void
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);
        $this->createActiveSession($user, $league, $team1, $team2);
        $gameId = 999;

        $row = WebsocketScoreboard::create([
            'user_id' => $user->id,
            'game_id' => $gameId,
            'session_id' => null,
            'league_id' => $league->id,
            'left_score' => 0,
            'right_score' => 0,
            'is_start' => true,
            'action' => 'INFO',
            'updated_at' => now()->subMinutes(2),
        ]);

        $this->getJson('/api/scoreboard?game_id=' . $gameId)
            ->assertStatus(200);

        $this->assertDatabaseHas('websocket_scoreboards', [
            'id' => $row->id,
            'is_start' => true,
            'action' => 'INFO',
        ]);
    }

    public function test_end_match_broadcast_completes_active_session(): void
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);
        $session = $this->createActiveSession($user, $league, $team1, $team2);
        $gameId = 999;

        WebsocketScoreboard::create([
            'user_id' => $user->id,
            'game_id' => $gameId,
            'session_id' => $session->id,
            'league_id' => $league->id,
            'left_score' => 0,
            'right_score' => 0,
            'is_start' => true,
            'action' => 'Start',
        ]);

        $this->postJson('/api/scoreboard/broadcast', $this->scoreboardBroadcastPayload($gameId, $league->id, [
            'action' => 'EndMatch',
            'session_id' => $session->id,
            'isStartTime' => false,
        ]))->assertNoContent();

        $this->assertDatabaseHas('play_game_modes', [
            'id' => $session->id,
            'status' => ActiveGameModeGuard::STATUS_COMPLETED,
        ]);

        $this->assertDatabaseHas('websocket_scoreboards', [
            'game_id' => $gameId,
            'is_start' => false,
            'action' => 'EndMatch',
        ]);
    }

    public function test_partial_broadcast_preserves_game_state_and_session_id(): void
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);
        $session = $this->createActiveSession($user, $league, $team1, $team2);
        $gameId = 999;

        WebsocketScoreboard::create([
            'user_id' => $user->id,
            'game_id' => $gameId,
            'session_id' => $session->id,
            'league_id' => $league->id,
            'left_score' => 0,
            'right_score' => 0,
            'is_start' => true,
            'action' => 'Start',
            'down' => 2,
            'strategies' => 'aggressive',
            'position_number' => 35,
        ]);

        $this->postJson('/api/scoreboard/broadcast', [
            'game_id' => $gameId,
            'team' => 'both',
            'teamLeftScore' => 0,
            'teamRightScore' => 0,
            'points' => 0,
            'action' => 'INFO',
            'isStartTime' => true,
            'time' => 450,
            'quarter' => 1,
            'teamPosition' => 2,
        ])->assertNoContent();

        $this->assertDatabaseHas('websocket_scoreboards', [
            'user_id' => $user->id,
            'game_id' => $gameId,
            'session_id' => $session->id,
            'league_id' => $league->id,
            'down' => 2,
            'strategies' => 'aggressive',
            'position_number' => 35,
            'team_position' => 2,
            'is_start' => true,
            'sync_time' => 450,
            'timer_remaining' => 450,
        ]);

        $this->getJson('/api/scoreboard?game_id=' . $gameId)
            ->assertStatus(200)
            ->assertJsonPath('data.down', 2)
            ->assertJsonPath('data.strategies', 'aggressive')
            ->assertJsonPath('data.position_number', 35)
            ->assertJsonPath('data.session_id', $session->id)
            ->assertJsonPath('data.sync_time', 450);
    }

    public function test_info_broadcast_persists_sync_time_from_time_field(): void
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);
        $this->createActiveSession($user, $league, $team1, $team2);
        $gameId = 1001;

        WebsocketScoreboard::create([
            'user_id' => $user->id,
            'game_id' => $gameId,
            'league_id' => $league->id,
            'left_score' => 0,
            'right_score' => 0,
            'is_start' => true,
            'action' => 'Start',
        ]);

        $this->postJson('/api/scoreboard/broadcast', $this->scoreboardBroadcastPayload($gameId, $league->id, [
            'action' => 'INFO',
            'time' => 450,
        ]))->assertNoContent();

        $this->assertDatabaseHas('websocket_scoreboards', [
            'game_id' => $gameId,
            'sync_time' => 450,
            'timer_remaining' => 450,
        ]);
    }

    public function test_stale_reconcile_cleanup_does_not_use_end_match_action(): void
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);
        $gameId = 999;

        $row = WebsocketScoreboard::create([
            'user_id' => $user->id,
            'game_id' => $gameId,
            'session_id' => null,
            'league_id' => $league->id,
            'left_score' => 0,
            'right_score' => 0,
            'is_start' => true,
            'action' => 'INFO',
            'updated_at' => now()->subMinutes(2),
        ]);

        $this->getJson('/api/scoreboard?game_id=' . $gameId)
            ->assertNoContent();

        $this->assertDatabaseHas('websocket_scoreboards', [
            'id' => $row->id,
            'is_start' => false,
            'action' => 'INFO',
        ]);
    }

    public function test_stale_play_session_does_not_block_new_session_start(): void
    {
        config(['game_modes.live_session_timeout_minutes' => 1]);

        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);
        $this->createRegisteredDeviceForLeague($league, $user);
        $staleSession = $this->createActiveSession($user, $league, $team1, $team2);

        $endedAt = now()->subMinutes(2);
        $row = WebsocketScoreboard::create([
            'user_id' => $user->id,
            'game_id' => 999,
            'session_id' => $staleSession->id,
            'league_id' => $league->id,
            'left_score' => 0,
            'right_score' => 0,
            'is_start' => true,
            'action' => 'INFO',
            'quarter' => 4,
            'timer_remaining' => 0,
            'regulation_ended_at' => $endedAt,
            'last_meaningful_activity_at' => $endedAt,
            'updated_at' => now(),
        ]);

        $this->postJson('/api/start-game-mode', [
            'league_id' => $league->id,
            'my_team_id' => $team1->id,
            'oponent_team_id' => $team2->id,
            'is_practice' => false,
        ])->assertStatus(200);

        $this->assertDatabaseHas('play_game_modes', [
            'id' => $staleSession->id,
            'status' => ActiveGameModeGuard::STATUS_COMPLETED,
        ]);

        $this->assertDatabaseHas('websocket_scoreboards', [
            'id' => $row->id,
            'is_start' => false,
            'action' => 'INFO',
        ]);

        $this->assertSame(1, PlayGameMode::where('user_id', $user->id)
            ->where('game_mode', 'play')
            ->where('status', ActiveGameModeGuard::STATUS_ACTIVE)
            ->count());
    }

    public function test_fresh_play_session_still_blocks_new_session_start(): void
    {
        config(['game_modes.live_session_timeout_minutes' => 1]);

        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);
        $this->createRegisteredDeviceForLeague($league, $user);
        $session = $this->createActiveSession($user, $league, $team1, $team2);

        // Still in regulation — must not auto-expire even if updated_at is old.
        WebsocketScoreboard::create([
            'user_id' => $user->id,
            'game_id' => 999,
            'session_id' => $session->id,
            'league_id' => $league->id,
            'left_score' => 0,
            'right_score' => 0,
            'is_start' => true,
            'action' => 'INFO',
            'quarter' => 2,
            'timer_remaining' => 400,
            'sys_time' => now()->toDateTimeString(),
            'last_meaningful_activity_at' => now()->subMinutes(30),
            'updated_at' => now()->subMinutes(30),
        ]);

        $this->postJson('/api/start-game-mode', [
            'league_id' => $league->id,
            'my_team_id' => $team1->id,
            'oponent_team_id' => $team2->id,
            'is_practice' => false,
        ])->assertStatus(STATUS_CODE_UNPROCESSABLE);

        $this->assertDatabaseHas('play_game_modes', [
            'id' => $session->id,
            'status' => ActiveGameModeGuard::STATUS_ACTIVE,
        ]);
    }

    public function test_stale_practice_session_is_expired_by_reconcile(): void
    {
        config(['game_modes.live_session_timeout_minutes' => 1]);

        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);
        $session = $this->createActiveSession($user, $league, $team1, $team2, 'practice');

        $endedAt = now()->subMinutes(2);
        $row = WebsocketPracticeScoreboard::create([
            'user_id' => $user->id,
            'game_id' => 1000,
            'session_id' => $session->id,
            'league_id' => $league->id,
            'left_score' => 0,
            'right_score' => 0,
            'is_start' => true,
            'action' => 'INFO',
            'quarter' => 4,
            'timer_remaining' => 0,
            'regulation_ended_at' => $endedAt,
            'last_meaningful_activity_at' => $endedAt,
            'updated_at' => now(),
        ]);

        $this->getJson('/api/practice-scoreboard?game_id=1000')
            ->assertNoContent();

        $this->assertDatabaseHas('play_game_modes', [
            'id' => $session->id,
            'status' => ActiveGameModeGuard::STATUS_COMPLETED,
        ]);

        $this->assertDatabaseHas('websocket_practice_scoreboards', [
            'id' => $row->id,
            'is_start' => false,
            'action' => 'INFO',
        ]);
    }

    public function test_scoreboard_poll_does_not_prevent_post_regulation_auto_end(): void
    {
        config(['game_modes.live_session_timeout_minutes' => 1]);

        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);
        $session = $this->createActiveSession($user, $league, $team1, $team2);

        $endedAt = now()->subMinutes(2);
        $row = WebsocketScoreboard::create([
            'user_id' => $user->id,
            'game_id' => 999,
            'session_id' => $session->id,
            'league_id' => $league->id,
            'left_score' => 0,
            'right_score' => 0,
            'is_start' => true,
            'action' => 'INFO',
            'quarter' => 4,
            'timer_remaining' => 0,
            'sys_time' => $endedAt->toDateTimeString(),
            'regulation_ended_at' => $endedAt,
            'last_meaningful_activity_at' => $endedAt,
            'updated_at' => now()->subSeconds(5),
        ]);

        // Poll would previously refresh updated_at and block auto-end forever.
        $this->getJson('/api/scoreboard?game_id=999')->assertNoContent();

        $this->assertDatabaseHas('play_game_modes', [
            'id' => $session->id,
            'status' => ActiveGameModeGuard::STATUS_COMPLETED,
        ]);

        $this->assertDatabaseHas('websocket_scoreboards', [
            'id' => $row->id,
            'is_start' => false,
        ]);
    }

    public function test_meaningful_activity_after_regulation_delays_auto_end(): void
    {
        config(['game_modes.live_session_timeout_minutes' => 1]);

        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);
        $session = $this->createActiveSession($user, $league, $team1, $team2);

        WebsocketScoreboard::create([
            'user_id' => $user->id,
            'game_id' => 999,
            'session_id' => $session->id,
            'league_id' => $league->id,
            'left_score' => 0,
            'right_score' => 0,
            'is_start' => true,
            'action' => 'INFO',
            'quarter' => 4,
            'timer_remaining' => 0,
            'regulation_ended_at' => now()->subMinutes(10),
            'last_meaningful_activity_at' => now()->subSeconds(20),
            'updated_at' => now(),
        ]);

        $this->assertSame(0, ActiveGameModeGuard::expireStaleSessions($user->id, 'play', $league->id));

        $this->assertDatabaseHas('play_game_modes', [
            'id' => $session->id,
            'status' => ActiveGameModeGuard::STATUS_ACTIVE,
        ]);
    }
}
