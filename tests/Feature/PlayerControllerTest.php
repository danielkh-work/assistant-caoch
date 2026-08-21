<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\Player;
use App\Models\Team;
use App\Models\TeamPlayer;
use App\Models\Sport;
use App\Models\League;
use App\Models\LeagueTeam;
use App\Services\LeaguePlayerTeamValidator;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Schema;

class PlayerControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!Schema::hasTable('players')) {
            $this->markTestSkipped('Backend schema issue: players table not found');
        }

        $this->user = User::factory()->create([
            'role' => 'head_coach',
            'status' => 'approved'
        ]);
    }

    protected function createPlayer($name)
    {
        $player = new Player();
        $player->name = $name;
        $player->user_id = $this->user->id;
        $player->number = rand(1, 99);
        $player->position = 'QB';
        $player->size = 70;
        $player->speed = 80;
        $player->strength = 80;
        $player->weight = 200;
        $player->height = 180;
        $player->save();
        return $player;
    }

    protected function auth()
    {
        Sanctum::actingAs($this->user);
        $this->actingAs($this->user, 'api');
    }

    public function test_can_add_player()
    {
        $this->auth();

        $response = $this->postJson('/api/add-player', [
            'type' => 'player',
            'name' => 'John Doe',
            'number' => 12,
            'position' => 'QB',
            'size' => 70,
            'weight' => 200,
            'height' => 180,
            'dob' => '1990-01-01',
            'ofp' => 85,
            'strength' => 90
        ]);

        if ($response->status() !== 200) {
            $response->dump();
        }

        $response->assertStatus(200);
        $this->assertDatabaseHas('players', ['name' => 'John Doe', 'number' => 12]);
    }

    public function test_can_add_open_player()
    {
        $this->auth();

        $response = $this->postJson('/api/add-open-player', [
            'type' => 'player',
            'name' => 'Jane Doe',
            'number' => 15,
            'position' => 'WR',
            'size' => 72,
            'weight' => 190,
            'height' => 185,
            'dob' => '1992-02-02',
            'ofp' => 88,
            'strength' => 85
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('players', ['name' => 'Jane Doe', 'number' => 15]);
    }

    public function test_can_list_players()
    {
        $this->auth();

        $this->createPlayer('Test List Player');

        $response = $this->getJson('/api/player-list');

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'message', 'data']);
    }

    public function test_player_list_includes_league_and_team_metadata()
    {
        if (!Schema::hasTable('league_teams')) {
            $this->markTestSkipped('Backend schema issue: league_teams table not found');
        }

        $this->auth();

        [$league, $teamA] = array_slice($this->createLeagueWithTeams(), 0, 2);
        $player = $this->createPlayer('Metadata Player');
        $this->rosterPlayerOnTeam($player, $teamA);

        $response = $this->getJson('/api/player-list?league_id=' . $league->id . '&page=1&per_page=20');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Metadata Player',
            ]);

        $listedPlayer = collect($response->json('data'))->firstWhere('name', 'Metadata Player');
        $this->assertNotNull($listedPlayer);
        $this->assertSame($teamA->id, $listedPlayer['teams'][0]['team_id'] ?? null);
        $this->assertSame('Player Team A', $listedPlayer['teams'][0]['team_name'] ?? null);
        $this->assertSame('Player Test League', $listedPlayer['leagues'][0]['league_name'] ?? null);
    }

    public function test_player_list_without_filter_includes_all_team_and_league_metadata()
    {
        if (!Schema::hasTable('league_teams')) {
            $this->markTestSkipped('Backend schema issue: league_teams table not found');
        }

        $this->auth();

        [$ownedLeague, $ownedTeam] = array_slice($this->createLeagueWithTeams(), 0, 2);

        $otherCoach = User::factory()->create([
            'role' => 'head_coach',
            'status' => 'approved',
        ]);
        [$foreignLeague, $foreignTeam] = $this->createLeagueWithTeamsForUser(
            $otherCoach,
            'Foreign League',
            'Foreign Team',
        );

        $player = $this->createPlayer('Cross Coach Player');
        $this->rosterPlayerOnTeam($player, $ownedTeam);
        $this->rosterPlayerOnTeam($player, $foreignTeam);

        $response = $this->getJson('/api/player-list?page=1&per_page=20');

        $response->assertStatus(200);

        $listedPlayer = collect($response->json('data'))->firstWhere('name', 'Cross Coach Player');
        $this->assertNotNull($listedPlayer);
        $this->assertContains($ownedTeam->id, collect($listedPlayer['teams'])->pluck('team_id')->all());
        $this->assertContains($foreignTeam->id, collect($listedPlayer['teams'])->pluck('team_id')->all());
        $this->assertContains($ownedLeague->id, collect($listedPlayer['leagues'])->pluck('league_id')->all());
        $this->assertContains($foreignLeague->id, collect($listedPlayer['leagues'])->pluck('league_id')->all());
    }

    public function test_player_list_filter_current_league_returns_league_players()
    {
        if (!Schema::hasTable('league_teams')) {
            $this->markTestSkipped('Backend schema issue: league_teams table not found');
        }

        $this->auth();

        [$league, $teamA] = array_slice($this->createLeagueWithTeams(), 0, 2);
        [$otherLeague, $otherTeam] = $this->createLeagueWithTeams('Other League', 'Other Team');

        $inLeaguePool = $this->createPlayer('League Pool Player');
        $inLeaguePool->league_id = $league->id;
        $inLeaguePool->save();

        $rosteredInLeague = $this->createPlayer('Rostered League Player');
        $this->rosterPlayerOnTeam($rosteredInLeague, $teamA);
        $this->rosterPlayerOnTeam($rosteredInLeague, $otherTeam);

        $outsideLeague = $this->createPlayer('Outside League Player');

        $response = $this->getJson('/api/player-list?filter=current_league&league_id=' . $league->id . '&page=1&per_page=20');

        $response->assertStatus(200);

        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertContains('League Pool Player', $names);
        $this->assertContains('Rostered League Player', $names);
        $this->assertNotContains('Outside League Player', $names);

        $listedPlayer = collect($response->json('data'))->firstWhere('name', 'Rostered League Player');
        $this->assertNotNull($listedPlayer);
        $this->assertSame(
            [$teamA->id],
            collect($listedPlayer['teams'])->pluck('team_id')->all()
        );
        $this->assertSame(
            [$league->id],
            collect($listedPlayer['leagues'])->pluck('league_id')->all()
        );
    }

    public function test_player_list_filter_current_team_returns_rostered_players()
    {
        if (!Schema::hasTable('league_teams')) {
            $this->markTestSkipped('Backend schema issue: league_teams table not found');
        }

        $this->auth();

        [, $teamA] = $this->createLeagueWithTeams();

        $onTeam = $this->createPlayer('On Team Player');
        $this->rosterPlayerOnTeam($onTeam, $teamA);

        $notOnTeam = $this->createPlayer('Not On Team Player');

        $response = $this->getJson('/api/player-list?filter=current_team&team_id=' . $teamA->id . '&page=1&per_page=20');

        $response->assertStatus(200);

        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertContains('On Team Player', $names);
        $this->assertNotContains('Not On Team Player', $names);
    }

    public function test_player_list_filter_not_assigned_returns_unrostered_players()
    {
        if (!Schema::hasTable('league_teams')) {
            $this->markTestSkipped('Backend schema issue: league_teams table not found');
        }

        $this->auth();

        [, $teamA] = $this->createLeagueWithTeams();

        $assigned = $this->createPlayer('Assigned Player');
        $this->rosterPlayerOnTeam($assigned, $teamA);

        $unassigned = $this->createPlayer('Unassigned Player');

        $response = $this->getJson('/api/player-list?filter=not_assigned&page=1&per_page=20');

        $response->assertStatus(200);

        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertContains('Unassigned Player', $names);
        $this->assertNotContains('Assigned Player', $names);
    }

    public function test_player_list_filter_created_by_me_returns_only_auth_user_players()
    {
        $this->auth();

        $mine = $this->createPlayer('My Created Player');

        $otherCoach = User::factory()->create([
            'role' => 'head_coach',
            'status' => 'approved',
        ]);
        $theirs = new Player();
        $theirs->name = 'Other Coach Player';
        $theirs->user_id = $otherCoach->id;
        $theirs->number = 88;
        $theirs->position = 'QB';
        $theirs->size = 70;
        $theirs->speed = 80;
        $theirs->strength = 80;
        $theirs->save();

        $response = $this->getJson('/api/player-list?filter=created_by_me&page=1&per_page=20');

        $response->assertStatus(200);

        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertContains('My Created Player', $names);
        $this->assertNotContains('Other Coach Player', $names);
        $this->assertSame($this->user->id, collect($response->json('data'))->firstWhere('name', 'My Created Player')['user_id'] ?? null);
    }

    public function test_player_list_filter_current_league_requires_league_id()
    {
        $this->auth();

        $response = $this->getJson('/api/player-list?filter=current_league&page=1&per_page=20');

        $response->assertStatus(422)
            ->assertJsonPath('message', 'league_id is required when filter is current_league.');
    }

    public function test_player_list_filter_my_leagues_returns_players_from_owned_leagues()
    {
        if (!Schema::hasTable('league_teams')) {
            $this->markTestSkipped('Backend schema issue: league_teams table not found');
        }

        $this->auth();

        [$ownedLeague, $ownedTeam] = array_slice($this->createLeagueWithTeams('Owned League', 'Owned Team A'), 0, 2);
        [$secondOwnedLeague] = $this->createLeagueWithTeams('Second Owned League', 'Owned Team B');

        $otherCoach = User::factory()->create([
            'role' => 'head_coach',
            'status' => 'approved',
        ]);
        [$foreignLeague, $foreignTeam] = $this->createLeagueWithTeamsForUser(
            $otherCoach,
            'Foreign League',
            'Foreign Team',
        );

        $poolPlayer = $this->createPlayer('Owned Pool Player');
        $poolPlayer->league_id = $ownedLeague->id;
        $poolPlayer->save();

        $rosteredPlayer = $this->createPlayer('Owned Rostered Player');
        $this->rosterPlayerOnTeam($rosteredPlayer, $ownedTeam);

        $secondLeaguePoolPlayer = $this->createPlayer('Second Owned Pool Player');
        $secondLeaguePoolPlayer->league_id = $secondOwnedLeague->id;
        $secondLeaguePoolPlayer->save();

        $foreignOnlyPlayer = $this->createPlayer('Foreign Only Player');
        $foreignOnlyPlayer->league_id = $foreignLeague->id;
        $foreignOnlyPlayer->save();
        $this->rosterPlayerOnTeam($foreignOnlyPlayer, $foreignTeam);

        $response = $this->getJson('/api/player-list?filter=my_leagues&page=1&per_page=20');

        $response->assertStatus(200);

        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertContains('Owned Pool Player', $names);
        $this->assertContains('Owned Rostered Player', $names);
        $this->assertContains('Second Owned Pool Player', $names);
        $this->assertNotContains('Foreign Only Player', $names);
    }

    public function test_player_list_filter_positions_returns_matching_players()
    {
        if (!Schema::hasTable('player_positions')) {
            $this->markTestSkipped('Backend schema issue: player_positions table not found');
        }

        $this->auth();

        $quarterback = $this->createPlayer('Quarterback Player');
        $this->addPlayerPosition($quarterback, 'Quarterback');

        $linebacker = $this->createPlayer('Linebacker Player');
        $this->addPlayerPosition($linebacker, 'Middle Linebacker (Mike)');

        $unmatched = $this->createPlayer('Unmatched Position Player');
        $this->addPlayerPosition($unmatched, 'Wide receiver X');

        $response = $this->getJson('/api/player-list?positions[]=Quarterback&positions[]=Middle%20Linebacker%20(Mike)&page=1&per_page=20');

        $response->assertStatus(200);

        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertContains('Quarterback Player', $names);
        $this->assertContains('Linebacker Player', $names);
        $this->assertNotContains('Unmatched Position Player', $names);
    }

    public function test_player_list_filter_positions_combines_with_current_league()
    {
        if (!Schema::hasTable('league_teams') || !Schema::hasTable('player_positions')) {
            $this->markTestSkipped('Backend schema issue: league_teams or player_positions table not found');
        }

        $this->auth();

        [$league, $teamA] = array_slice($this->createLeagueWithTeams(), 0, 2);
        [$otherLeague, $otherTeam] = $this->createLeagueWithTeams('Other League', 'Other Team');

        $matching = $this->createPlayer('Matching League QB');
        $matching->league_id = $league->id;
        $matching->save();
        $this->addPlayerPosition($matching, 'Quarterback');

        $wrongPosition = $this->createPlayer('Matching League WR');
        $wrongPosition->league_id = $league->id;
        $wrongPosition->save();
        $this->addPlayerPosition($wrongPosition, 'Wide receiver X');

        $otherLeagueQuarterback = $this->createPlayer('Other League QB');
        $this->rosterPlayerOnTeam($otherLeagueQuarterback, $otherTeam);
        $this->addPlayerPosition($otherLeagueQuarterback, 'Quarterback');

        $response = $this->getJson(
            '/api/player-list?filter=current_league&league_id=' . $league->id . '&positions[]=Quarterback&page=1&per_page=20'
        );

        $response->assertStatus(200);

        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertContains('Matching League QB', $names);
        $this->assertNotContains('Matching League WR', $names);
        $this->assertNotContains('Other League QB', $names);
    }

    public function test_player_list_empty_positions_filter_does_not_error()
    {
        $this->auth();

        $this->createPlayer('Any Player');

        $response = $this->getJson('/api/player-list?positions[]=&page=1&per_page=20');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Any Player']);
    }

    public function test_can_update_player_profile()
    {
        $this->auth();

        $player = $this->createPlayer('Old Profile Name');

        $response = $this->postJson('/api/update-player/' . $player->id, [
            'type' => 'basic', // Not team_player
            'name' => 'Updated Profile Name',
            'number' => 99,
            'position' => 'TE',
            'size' => 75,
            'speed' => 85,
            'strength' => 80,
            'weight' => 220,
            'height' => 195,
            'dob' => '1988-08-08',
            'ofp' => 90
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('players', ['id' => $player->id, 'name' => 'Updated Profile Name']);
    }

    public function test_can_update_team_player_profile()
    {
        if (!Schema::hasTable('league_teams')) {
            $this->markTestSkipped('Backend schema issue: league_teams table not found');
        }

        $this->auth();

        $team = new Team();
        $team->name = 'Test Update Team';
        $team->save();

        $player = $this->createPlayer('Update Team Player');

        $teamPlayer = new TeamPlayer();
        $teamPlayer->team_id = $team->id;
        $teamPlayer->player_id = $player->id;
        $teamPlayer->name = 'Update Team Player';
        $teamPlayer->save();

        $response = $this->postJson('/api/update-player/' . $team->id, [
            'type' => 'team_player',
            'player_id' => $player->id,
            'name' => 'Updated Team Player',
            'number' => 10,
            'position' => 'LB',
            'size' => 80,
            'speed' => 75,
            'strength' => 85,
            'weight' => 240,
            'height' => 180,
            'dob' => '1991-01-01',
            'ofp' => 70
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('team_players', ['player_id' => $player->id, 'name' => 'Updated Team Player']);
    }

    protected function createLeagueWithTeams(
        string $leagueTitle = 'Player Test League',
        string $teamAName = 'Player Team A',
        string $teamBName = 'Player Team B',
    ): array {
        $sport = Sport::where('title', 'Football Test')->first();
        if (!$sport) {
            $sport = new Sport();
            $sport->title = 'Football Test';
            $sport->save();
        }

        $league = new League();
        $league->user_id = $this->user->id;
        $league->sport_id = $sport->id;
        $league->league_rule_id = \Illuminate\Support\Facades\DB::table('league_rules')->value('id') ?? 1;
        $league->title = $leagueTitle;
        $league->number_of_team = 2;
        $league->save();

        $teamA = new LeagueTeam();
        $teamA->league_id = $league->id;
        $teamA->team_name = $teamAName;
        $teamA->save();

        $teamB = new LeagueTeam();
        $teamB->league_id = $league->id;
        $teamB->team_name = $teamBName;
        $teamB->save();

        return [$league, $teamA, $teamB];
    }

    protected function createLeagueWithTeamsForUser(
        User $user,
        string $leagueTitle = 'Player Test League',
        string $teamAName = 'Player Team A',
        string $teamBName = 'Player Team B',
    ): array {
        $sport = Sport::where('title', 'Football Test')->first();
        if (!$sport) {
            $sport = new Sport();
            $sport->title = 'Football Test';
            $sport->save();
        }

        $league = new League();
        $league->user_id = $user->id;
        $league->sport_id = $sport->id;
        $league->league_rule_id = \Illuminate\Support\Facades\DB::table('league_rules')->value('id') ?? 1;
        $league->title = $leagueTitle;
        $league->number_of_team = 2;
        $league->save();

        $teamA = new LeagueTeam();
        $teamA->league_id = $league->id;
        $teamA->team_name = $teamAName;
        $teamA->save();

        $teamB = new LeagueTeam();
        $teamB->league_id = $league->id;
        $teamB->team_name = $teamBName;
        $teamB->save();

        return [$league, $teamA, $teamB];
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

    protected function addPlayerPosition(Player $player, string $positionName): void
    {
        \Illuminate\Support\Facades\DB::table('player_positions')->insert([
            'player_id' => $player->id,
            'position_name' => $positionName,
            'sort' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_add_player_rejects_when_player_already_on_another_team_in_league()
    {
        if (!Schema::hasTable('league_teams')) {
            $this->markTestSkipped('Backend schema issue: league_teams table not found');
        }

        $this->auth();

        [$league, $teamA, $teamB] = $this->createLeagueWithTeams();
        $player = $this->createPlayer('Existing Roster Player');
        $this->rosterPlayerOnTeam($player, $teamA);

        $validator = app(LeaguePlayerTeamValidator::class);
        $message = $validator->firstConflictMessage($league->id, $teamB->id, [$player->id]);

        $this->assertSame(
            'Player "Existing Roster Player" is already assigned to team "Player Team A" in this league.',
            $message
        );

        $response = $this->postJson('/api/add-player', [
            'type' => 'team',
            'team_id' => $teamB->id,
            'name' => 'Existing Roster Player',
            'number' => $player->number,
            'position' => 'QB',
            'size' => 70,
            'weight' => 200,
            'height' => 180,
            'dob' => '1990-01-01',
            'ofp' => 85,
            'strength' => 90,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'A player with this name and number already exists.');

        $this->assertDatabaseMissing('team_players', [
            'team_id' => $teamB->id,
            'player_id' => $player->id,
        ]);

        $assignResponse = $this->postJson('/api/update-team/' . $teamB->id, [
            'team_name' => $teamB->team_name,
            'league_id' => $league->id,
            'players' => json_encode([
                [
                    'player_id' => $player->id,
                    'playertype' => 'offence',
                    'name' => $player->name,
                    'speed' => 80,
                ],
            ]),
        ]);

        $assignResponse->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Player "Existing Roster Player" is already assigned to team "Player Team A" in this league.'
            );
    }

    public function test_can_update_ofp()
    {
        if (!Schema::hasTable('league_teams')) {
            $this->markTestSkipped('Backend schema issue: league_teams table not found');
        }

        $this->auth();

        $team = new Team();
        $team->name = 'Test OFP Team';
        $team->save();

        $player = $this->createPlayer('OFP Player');

        $teamPlayer = new TeamPlayer();
        $teamPlayer->team_id = $team->id;
        $teamPlayer->player_id = $player->id;
        $teamPlayer->rpp = 50;
        $teamPlayer->save();

        $response = $this->putJson('/api/team-players/' . $teamPlayer->id . '/ofp', [
            'rpp' => 80
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('team_players', ['id' => $teamPlayer->id, 'rpp' => 80]);
    }

    public function test_can_view_player()
    {
        $this->auth();

        $player = $this->createPlayer('View Setup Player');

        $response = $this->getJson('/api/view-player/' . $player->id);

        $response->assertStatus(200);
    }
}
