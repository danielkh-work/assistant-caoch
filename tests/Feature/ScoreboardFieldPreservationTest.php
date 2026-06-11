<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\LeagueTeam;
use App\Models\PlayGameMode;
use App\Models\Sport;
use App\Models\User;
use App\Models\WebsocketPracticeScoreboard;
use App\Models\WebsocketScoreboard;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ScoreboardFieldPreservationTest extends TestCase
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
        $league->title = 'Scoreboard Field League';
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

    public function test_partial_broadcast_preserves_game_state_and_session_id(): void
    {
        $user = $this->createHeadCoachUser();
        Sanctum::actingAs($user);
        $this->actingAs($user, 'api');

        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);

        $session = new PlayGameMode();
        $session->sport_id = $user->sport_id;
        $session->league_id = $league->id;
        $session->my_team_id = $team1->id;
        $session->oponent_team_id = $team2->id;
        $session->user_id = $user->id;
        $session->game_mode = 'play';
        $session->status = 2;
        $session->save();

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
        $user = $this->createHeadCoachUser();
        Sanctum::actingAs($user);
        $this->actingAs($user, 'api');

        [$league] = $this->createLeagueWithTeams($user);
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
            'league_id' => $league->id,
        ])->assertNoContent();

        $this->assertDatabaseHas('websocket_scoreboards', [
            'game_id' => $gameId,
            'sync_time' => 450,
            'timer_remaining' => 450,
        ]);
    }

    public function test_info_broadcast_preserves_practice_scores_quarter_and_timer(): void
    {
        $user = $this->createHeadCoachUser();
        Sanctum::actingAs($user);
        $this->actingAs($user, 'api');

        [$league] = $this->createLeagueWithTeams($user);
        $gameId = 36;

        WebsocketPracticeScoreboard::create([
            'user_id' => $user->id,
            'game_id' => $gameId,
            'league_id' => $league->id,
            'left_score' => 7,
            'right_score' => 7,
            'is_start' => true,
            'action' => 'RED',
            'quarter' => 2,
            'down' => 1,
            'strategies' => 'regular',
            'position_number' => 12,
            'timer_remaining' => 833,
        ]);

        $this->postJson('/api/practice/scoreboard/broadcast', [
            'game_id' => $gameId,
            'team' => 'both',
            'teamLeftScore' => 0,
            'teamRightScore' => 0,
            'points' => 0,
            'action' => 'INFO',
            'isStartTime' => true,
            'time' => 0,
            'quarter' => 1,
            'strategies' => 'red zone',
            'positionNumber' => 14,
            'league_id' => $league->id,
        ])->assertNoContent();

        $this->assertDatabaseHas('websocket_practice_scoreboards', [
            'user_id' => $user->id,
            'game_id' => $gameId,
            'left_score' => 7,
            'right_score' => 7,
            'quarter' => 2,
            'timer_remaining' => 833,
            'strategies' => 'red zone',
            'position_number' => 14,
        ]);

        $this->getJson('/api/practice-scoreboard?game_id=' . $gameId)
            ->assertStatus(200)
            ->assertJsonPath('data.left_score', 7)
            ->assertJsonPath('data.right_score', 7)
            ->assertJsonPath('data.quarter', '2')
            ->assertJsonPath('data.timer_remaining', 833);
    }

    public function test_scoring_broadcast_still_updates_practice_scores(): void
    {
        $user = $this->createHeadCoachUser();
        Sanctum::actingAs($user);
        $this->actingAs($user, 'api');

        [$league] = $this->createLeagueWithTeams($user);
        $gameId = 37;

        WebsocketPracticeScoreboard::create([
            'user_id' => $user->id,
            'game_id' => $gameId,
            'league_id' => $league->id,
            'left_score' => 6,
            'right_score' => 0,
            'is_start' => true,
            'action' => 'TD',
            'quarter' => 1,
        ]);

        $this->postJson('/api/practice/scoreboard/broadcast', [
            'game_id' => $gameId,
            'team' => 'right',
            'teamLeftScore' => 6,
            'teamRightScore' => 7,
            'points' => 7,
            'action' => 'RED',
            'isStartTime' => true,
            'time' => 600,
            'quarter' => 1,
            'league_id' => $league->id,
        ])->assertNoContent();

        $this->assertDatabaseHas('websocket_practice_scoreboards', [
            'game_id' => $gameId,
            'left_score' => 6,
            'right_score' => 7,
            'action' => 'RED',
        ]);
    }
}
