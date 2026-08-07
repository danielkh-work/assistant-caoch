<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\Sport;
use App\Models\League;
use App\Models\LeagueTeam;
use App\Models\Team;
use App\Models\Player;
use App\Models\TeamPlayer;
use Laravel\Sanctum\Sanctum;

class TeamControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected League $league;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'head_coach',
            'status' => 'approved'
        ]);

        $sport = Sport::where('title', 'Football Test')->first();
        if (!$sport) {
            $sport = new Sport();
            $sport->title = 'Football Test';
            $sport->save();
        }
        
        $this->league = new League();
        $this->league->user_id = $this->user->id;
        $this->league->sport_id = $sport->id;
        $this->league->league_rule_id = \Illuminate\Support\Facades\DB::table('league_rules')->value('id') ?? 1;
        $this->league->title = 'Test League';
        $this->league->number_of_team = 2;
        $this->league->save();
    }

    protected function auth()
    {
        Sanctum::actingAs($this->user);
        $this->actingAs($this->user, 'api');
    }

    public function test_can_create_team()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('league_teams')) {
            $this->markTestSkipped('Backend schema issue: league_teams table not found');
        }

        $this->auth();

        \App\Models\Player::firstOrCreate(['id' => 1], ['name' => 'Test', 'number' => 1]);
        \App\Models\Player::firstOrCreate(['id' => 2], ['name' => 'Test', 'number' => 2]);

        $response = $this->postJson('/api/create-team', [
            'team_name' => 'Demo Team',
            'playerid' => [1, 2],
            'playertype' => ['QB', 'RB']
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('league_teams', ['team_name' => 'Demo Team']);
    }

    public function test_can_list_teams()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('league_teams')) {
            $this->markTestSkipped('Backend schema issue: league_teams table not found');
        }

        $this->auth();

        $team = Team::where('name', 'Sample Team')->first();
        if (!$team) {
            $team = new Team();
            $team->name = 'Sample Team';
            $team->save();
        }

        $response = $this->getJson('/api/team-list');

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'message', 'data']);
    }

    public function test_can_view_team()
    {
        $this->auth();

        $leagueTeam = new LeagueTeam();
        $leagueTeam->league_id = $this->league->id;
        $leagueTeam->team_name = 'Alpha';
        $leagueTeam->save();

        $response = $this->getJson('/api/view-team/' . $leagueTeam->id);

        $response->assertStatus(200);
    }

    public function test_can_get_practice_team_list()
    {
        $this->auth();

        $response = $this->getJson('/api/practice-team-list/' . $this->league->id);

        $response->assertStatus(200);
    }

    public function test_can_update_team()
    {
        $this->auth();

        $leagueTeam = new LeagueTeam();
        $leagueTeam->league_id = $this->league->id;
        $leagueTeam->team_name = 'Old Name';
        $leagueTeam->save();

        $playersData = json_encode([
            [
                'player_id' => 1,
                'playertype' => 'QB',
                'name' => 'John Doe',
                'speed' => 90
            ]
        ]);

        $response = $this->postJson('/api/update-team/' . $leagueTeam->id, [
            'team_name' => 'New Awesome Team',
            'league_id' => $this->league->id,
            'players' => $playersData
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('league_teams', ['id' => $leagueTeam->id, 'team_name' => 'New Awesome Team']);
    }

    protected function createLeaguePlayer(string $name = 'Roster Player'): Player
    {
        $player = new Player();
        $player->name = $name;
        $player->user_id = $this->user->id;
        $player->number = rand(10, 99);
        $player->position = 'QB';
        $player->size = 70;
        $player->speed = 80;
        $player->strength = 80;
        $player->weight = 200;
        $player->height = 180;
        $player->save();

        return $player;
    }

    protected function rosterPlayerOnTeam(Player $player, LeagueTeam $team): void
    {
        $teamPlayer = new TeamPlayer();
        $teamPlayer->player_id = $player->id;
        $teamPlayer->team_id = $team->id;
        $teamPlayer->name = $player->name;
        $teamPlayer->number = $player->number;
        $teamPlayer->position = $player->position;
        $teamPlayer->save();
    }

    protected function buildUpdateTeamPayload(LeagueTeam $team, array $players): array
    {
        return [
            'team_name' => $team->team_name,
            'league_id' => $this->league->id,
            'players' => json_encode($players),
        ];
    }

    public function test_update_team_rejects_player_already_on_another_team_in_same_league()
    {
        $this->auth();

        $teamA = new LeagueTeam();
        $teamA->league_id = $this->league->id;
        $teamA->team_name = 'Team Alpha';
        $teamA->save();

        $teamB = new LeagueTeam();
        $teamB->league_id = $this->league->id;
        $teamB->team_name = 'Team Beta';
        $teamB->save();

        $player = $this->createLeaguePlayer('Shared Player');
        $this->rosterPlayerOnTeam($player, $teamA);

        $response = $this->postJson('/api/update-team/' . $teamB->id, $this->buildUpdateTeamPayload($teamB, [
            [
                'player_id' => $player->id,
                'playertype' => 'offence',
                'name' => $player->name,
                'speed' => 80,
            ],
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Player "Shared Player" is already assigned to team "Team Alpha" in this league.');

        $this->assertDatabaseMissing('team_players', [
            'team_id' => $teamB->id,
            'player_id' => $player->id,
        ]);
    }

    public function test_update_team_allows_player_on_teams_in_different_leagues()
    {
        $this->auth();

        $otherLeague = new League();
        $otherLeague->user_id = $this->user->id;
        $otherLeague->sport_id = $this->league->sport_id;
        $otherLeague->league_rule_id = $this->league->league_rule_id;
        $otherLeague->title = 'Other League';
        $otherLeague->number_of_team = 2;
        $otherLeague->save();

        $teamA = new LeagueTeam();
        $teamA->league_id = $this->league->id;
        $teamA->team_name = 'League One Team';
        $teamA->save();

        $teamB = new LeagueTeam();
        $teamB->league_id = $otherLeague->id;
        $teamB->team_name = 'League Two Team';
        $teamB->save();

        $player = $this->createLeaguePlayer('Cross League Player');
        $this->rosterPlayerOnTeam($player, $teamA);

        $response = $this->postJson('/api/update-team/' . $teamB->id, $this->buildUpdateTeamPayload($teamB, [
            [
                'player_id' => $player->id,
                'playertype' => 'offence',
                'name' => $player->name,
                'speed' => 80,
            ],
        ]));

        $response->assertStatus(200);
        $this->assertDatabaseHas('team_players', [
            'team_id' => $teamB->id,
            'player_id' => $player->id,
        ]);
    }

    public function test_update_team_allows_resaving_player_on_same_team()
    {
        $this->auth();

        $team = new LeagueTeam();
        $team->league_id = $this->league->id;
        $team->team_name = 'Same Team';
        $team->save();

        $player = $this->createLeaguePlayer('Same Team Player');
        $this->rosterPlayerOnTeam($player, $team);

        $response = $this->postJson('/api/update-team/' . $team->id, $this->buildUpdateTeamPayload($team, [
            [
                'player_id' => $player->id,
                'playertype' => 'deffence',
                'name' => $player->name,
                'speed' => 85,
            ],
        ]));

        $response->assertStatus(200);
        $this->assertDatabaseHas('team_players', [
            'team_id' => $team->id,
            'player_id' => $player->id,
            'type' => 'deffence',
        ]);
    }

    public function test_can_list_team_by_league()
    {
        $this->auth();

        $response = $this->getJson('/api/team-list-by-league/' . $this->league->id);

        $response->assertStatus(200);
    }

    public function test_can_list_team_for_play_mode()
    {
        $this->auth();

        $response = $this->getJson('/api/team-list-by-play-mode/' . $this->league->id);

        $response->assertStatus(200);
    }
}
