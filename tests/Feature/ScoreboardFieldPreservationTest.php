<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\LeagueTeam;
use App\Models\PlayGameMode;
use App\Models\Sport;
use App\Models\User;
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
            'time' => 600,
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
        ]);

        $this->getJson('/api/scoreboard?game_id=' . $gameId)
            ->assertStatus(200)
            ->assertJsonPath('data.down', 2)
            ->assertJsonPath('data.strategies', 'aggressive')
            ->assertJsonPath('data.position_number', 35)
            ->assertJsonPath('data.session_id', $session->id);
    }
}
