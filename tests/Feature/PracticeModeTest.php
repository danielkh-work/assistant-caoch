<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sport;
use App\Models\League;
use App\Models\LeagueTeam;
use App\Models\Game;
use App\Models\PlayGameMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PracticeModeTest extends TestCase
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
        $user->name = 'Coach PracticeMode';
        $user->email = 'coach_practice_' . uniqid() . '@test.com';
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
        $league->title = 'Practice Mode League';
        $league->practice_number_players = 7;
        $league->number_of_team = 2;
        $league->save();

        $team1 = new LeagueTeam();
        $team1->league_id = $league->id;
        $team1->team_name = 'Alpha Practice';
        $team1->save();

        $team2 = new LeagueTeam();
        $team2->league_id = $league->id;
        $team2->team_name = 'Beta Practice';
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

    public function test_can_start_practice_mode()
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
        ]);
    }

    public function test_can_add_practice_play_log()
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
            'is_practice' => true,
            'players' => [
                ['player_id' => $playerId]
            ],
            'quater' => 1,
            'downs' => 1,
            'play_id' => 1,
            'weather_status' => 'cloudy',
            'current_position' => '50',
            'target' => '10',
            'my_points' => 0,
            'oponent_points' => 0,
            'time' => '12:00',
            'type_of_log' => 'play',
        ]);

        $response->assertStatus(200);
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

        $response->assertStatus(200);
    }

    public function test_can_get_practice_scoreboard()
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);

        $mode = new PlayGameMode();
        $mode->sport_id = $user->sport_id;
        $mode->league_id = $league->id;
        $mode->my_team_id = $team1->id;
        $mode->oponent_team_id = $team2->id;
        $mode->save();

        \App\Models\WebsocketPracticeScoreboard::create([
            'user_id' => $user->id,
            'game_id' => $mode->id,
            'left_score' => 0,
            'right_score' => 0,
            'action' => 'test'
        ]);
        
        $response = $this->getJson('/api/practice-scoreboard?game_id=' . $mode->id);
        
        $response->assertStatus(200);
    }

    public function test_can_delete_practice_scoreboard()
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);

        $mode = new PlayGameMode();
        $mode->sport_id = $user->sport_id;
        $mode->league_id = $league->id;
        $mode->my_team_id = $team1->id;
        $mode->oponent_team_id = $team2->id;
        $mode->save();

        \App\Models\WebsocketPracticeScoreboard::create([
            'user_id' => $user->id,
            'game_id' => $mode->id,
            'left_score' => 0,
            'right_score' => 0,
            'action' => 'test'
        ]);
        
        $response = $this->getJson('/api/practice/delete-scoreboard/' . $mode->id);
        
        $response->assertStatus(204);
    }

    public function test_can_substitute_practice_player_and_rpp()
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);

        // Create Play Game Mode (Match)
        $mode = new PlayGameMode();
        $mode->sport_id = $user->sport_id;
        $mode->league_id = $league->id;
        $mode->my_team_id = $team1->id;
        $mode->oponent_team_id = $team2->id;
        $mode->save();

        // Foreign key bypass for testing
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        // Let's create two practice players directly in DB for testing
        $offensePlayerId = 1111;
        $benchPlayerId = 2222;

        DB::table('practice_team_players')->insert([
            ['id' => $offensePlayerId, 'team_id' => $team1->id, 'name' => 'Offense Player', 'number' => 10, 'rpp' => 80, 'speed' => 90, 'strength' => 90, 'ofp' => 100],
            ['id' => $benchPlayerId, 'team_id' => $team1->id, 'name' => 'Bench Player', 'number' => 20, 'rpp' => 60, 'speed' => 90, 'strength' => 90, 'ofp' => 100],
        ]);

        // Put one on the field (ConfiguredPlayingTeamPlayer)
        DB::table('configured_playing_team_players')->insert([
            'match_id' => $mode->id,
            'team_id' => $team1->id,
            'practice_player_id' => $offensePlayerId,
            'player_id' => 0,
            'team_type' => 1,
        ]);

        // Put one on the bench (BenchPlayer)
        DB::table('offense_defense_players')->insert([
            'game_id' => $mode->id,
            'team_id' => $team1->id,
            'league_id' => $league->id,
            'practice_player_id' => $benchPlayerId,
            'player_id' => 0,
            'type' => 'myteam',
            'position' => 'WR',
            'rpp' => 60, // Original RPP of bench player
        ]);

        // Perform substitution
        $response = $this->postJson('/api/create-my-team-play-mode', [
            'offenseData' => ['id' => $offensePlayerId],
            'benchData' => ['id' => $benchPlayerId],
            'teamId' => $team1->id,
            'leagueId' => $league->id,
            'gameId' => $mode->id,
            'position' => 'QB',
            'is_practice' => true,
        ]);

        $response->assertStatus(200);

        // Verify substitution happened (IDs swapped)
        $this->assertDatabaseHas('configured_playing_team_players', [
            'match_id' => $mode->id,
            'practice_player_id' => $benchPlayerId,
        ]);

        $this->assertDatabaseHas('offense_defense_players', [
            'game_id' => $mode->id,
            'practice_player_id' => $offensePlayerId,
            'position' => 'QB',
            // It substitutes the player, but RPP should ideally be updated to the new bench player's RPP.
            // If the bug exists where RPP is not substituted, this assertion will fail and expose the issue, 
            // per user request "created a test cases for it that it substitute a test cases or not"
            'rpp' => 80, 
        ]);
    }

    public function test_can_shuffle_personal_group_players_in_practice_mode()
    {
        $user = $this->authAsCoach();
        [$league, $team1, $team2] = $this->createLeagueWithTeams($user);

        $mode = new PlayGameMode();
        $mode->sport_id = $user->sport_id;
        $mode->league_id = $league->id;
        $mode->my_team_id = $team1->id;
        $mode->oponent_team_id = $team2->id;
        $mode->save();

        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        $offensePlayerId = 3333;
        $benchPlayerId = 4444;

        DB::table('practice_team_players')->insert([
            ['id' => $offensePlayerId, 'team_id' => $team1->id, 'name' => 'Group Offense Player', 'number' => 11, 'rpp' => 85, 'speed' => 90, 'strength' => 90, 'ofp' => 100],
            ['id' => $benchPlayerId, 'team_id' => $team1->id, 'name' => 'Group Bench Player', 'number' => 22, 'rpp' => 65, 'speed' => 90, 'strength' => 90, 'ofp' => 100],
        ]);

        DB::table('configured_playing_team_players')->insert([
            'match_id' => $mode->id,
            'team_id' => $team1->id,
            'practice_player_id' => $offensePlayerId,
            'player_id' => 0,
            'team_type' => 1,
        ]);

        DB::table('offense_defense_players')->insert([
            'game_id' => $mode->id,
            'team_id' => $team1->id,
            'league_id' => $league->id,
            'practice_player_id' => $benchPlayerId,
            'player_id' => 0,
            'type' => 'myteam',
            'position' => 'RB',
            'rpp' => 65, 
            'player_type' => 'offense',
        ]);

        $response = $this->postJson('/api/shuffle-players', [
            'offensePlayers' => [ // Will be moved to Configured (Field)
                ['id' => $benchPlayerId, 'rpp' => 65]
            ],
            'benchPlayers' => [   // Will be moved to Bench
                ['id' => $offensePlayerId, 'rpp' => 85, 'selected_position' => 'TE']
            ],
            'teamId' => $team1->id,
            'gameId' => $mode->id,
            'playerType' => 'offense',
            'team_type' => 1,
            'leagueId' => $league->id,
            'type' => 'myteam',
            'is_practice' => true,
        ]);

        $response->assertStatus(200);

        // Verify offensePlayerId is now on the bench
        $this->assertDatabaseHas('offense_defense_players', [
            'game_id' => $mode->id,
            'practice_player_id' => $offensePlayerId,
            'rpp' => 85, // Ensuring RPP is properly added/substituted during Group shuffle
            'position' => 'TE'
        ]);

        // Verify benchPlayerId is now on the field
        $this->assertDatabaseHas('configured_playing_team_players', [
            'match_id' => $mode->id,
            'practice_player_id' => $benchPlayerId,
        ]);
    }
}
