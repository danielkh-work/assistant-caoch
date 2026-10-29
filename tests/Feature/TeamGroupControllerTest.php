<?php

namespace Tests\Feature;

use App\Models\LeagueTeam;
use App\Models\PersionalGrouping;
use App\Models\Player;
use App\Models\PracticeTeamPlayer;
use App\Models\TeamGroup;
use App\Models\TeamPlayer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeamGroupControllerTest extends TestCase
{
    protected int $teamId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->beforeApplicationDestroyed(fn () => DB::rollBack());
        DB::beginTransaction();

        if (! Schema::hasTable('team_groups')) {
            $this->markTestSkipped('team_groups table not available.');
        }

        // Authenticate as a head coach so auth:sanctum middleware passes
        $user = User::where('email', 'teamgroup-test@example.com')->first();
        if (! $user) {
            $user = User::create([
                'name'     => 'Team Group Test Coach',
                'email'    => 'teamgroup-test@example.com',
                'password' => Hash::make('secret'),
                'role'     => 'head_coach',
                'status'   => 'approved',
                'sport_id' => 1,
            ]);
        }

        Sanctum::actingAs($user);
        $this->actingAs($user, 'api');

        // Create a real team so FK constraints are satisfied
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $team = LeagueTeam::forceCreate([
            'team_name'   => 'Test Team',
            'league_id'   => 1,
            'is_practice' => 0,
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->teamId = $team->id;
    }

    private function makeTeamGroup(array $overrides = []): TeamGroup
    {
        return TeamGroup::forceCreate(array_merge([
            'team_id'    => $this->teamId,
            'league_id'  => null,
            'group_name' => 'Red Tigers',
            'type'       => 'offense',
            'group_level'=> 11,
            'status'     => 'active',
            'players'    => null,
        ], $overrides));
    }

    /** @test */
    public function index_returns_groups_for_requested_team_only(): void
    {
        $this->makeTeamGroup(['team_id' => $this->teamId, 'group_name' => 'Alpha']);
        $this->makeTeamGroup(['team_id' => $this->teamId, 'group_name' => 'Beta']);

        // Create a second team for isolation test
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $otherTeam = LeagueTeam::forceCreate(['team_name' => 'Other Team', 'league_id' => 1, 'is_practice' => 0]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        TeamGroup::forceCreate([
            'team_id'    => $otherTeam->id,
            'group_name' => 'Other',
            'type'       => 'offense',
            'group_level'=> 11,
            'status'     => 'active',
        ]);

        $response = $this->getJson("/api/teams/{$this->teamId}/groups");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(2, $data);
        $names = array_column($data, 'group_name');
        $this->assertContains('Alpha', $names);
        $this->assertContains('Beta', $names);
        $this->assertNotContains('Other', $names);
    }

    /** @test */
    public function store_creates_group_and_normalises_player_ids(): void
    {
        $response = $this->postJson("/api/teams/{$this->teamId}/groups", [
            'group_name'  => 'Red Tigers',
            'type'        => 'offense',
            'group_level' => 11,
            'player_ids'  => [10, 20, 30],
            'league_id'   => null,
        ]);

        $response->assertOk();
        $group = TeamGroup::where('group_name', 'Red Tigers')->first();
        $this->assertNotNull($group);
        $this->assertIsArray($group->players);
        $this->assertCount(3, $group->players);
        $this->assertEquals(['id' => 10, 'positions' => null], $group->players[0]);
    }

    /** @test */
    public function store_returns_422_when_required_fields_missing(): void
    {
        $this->postJson("/api/teams/{$this->teamId}/groups", [])->assertStatus(422);
    }

    /** @test */
    public function update_changes_name_and_type(): void
    {
        $group = $this->makeTeamGroup(['group_name' => 'Old Name', 'type' => 'offense']);

        $this->putJson("/api/groups/{$group->id}", [
            'group_name'  => 'New Name',
            'type'        => 'defense',
            'group_level' => 7,
            'player_ids'  => [],
        ])->assertOk();

        $this->assertDatabaseHas('team_groups', ['id' => $group->id, 'group_name' => 'New Name']);
    }

    /** @test */
    public function destroy_deletes_team_group_and_nullifies_source_on_game_copies(): void
    {
        $group = $this->makeTeamGroup();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $gameGroup = PersionalGrouping::forceCreate([
            'game_id'              => 42,
            'team_id'              => $this->teamId,
            'league_id'            => 1,
            'group_name'           => 'Red Tigers',
            'type'                 => 'offense',
            'group_level'          => 1,
            'status'               => 'draft',
            'source_team_group_id' => $group->id,
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->deleteJson("/api/groups/{$group->id}")->assertOk();

        $this->assertDatabaseMissing('team_groups', ['id' => $group->id]);
        $this->assertDatabaseHas('personal_groupings', [
            'id'                   => $gameGroup->id,
            'source_team_group_id' => null,
        ]);
    }

    /** @test */
    public function import_creates_independent_game_level_copies(): void
    {
        $tg1 = $this->makeTeamGroup([
            'group_name' => 'Red Tigers',
            'players'    => [['id' => 10, 'positions' => 'QB'], ['id' => 11, 'positions' => null]],
        ]);
        $tg2 = $this->makeTeamGroup(['group_name' => 'Blue Eagles', 'type' => 'defense']);

        $response = $this->postJson('/api/games/42/import-team-groups', [
            'group_ids'   => [$tg1->id, $tg2->id],
            'team_id'     => $this->teamId,
            'league_id'   => 1,
            'is_practice' => false,
        ]);

        $response->assertOk();
        $this->assertCount(2, $response->json('data.created'));

        $copy = PersionalGrouping::where('game_id', 42)
            ->where('source_team_group_id', $tg1->id)
            ->first();
        $this->assertNotNull($copy);
        $this->assertEquals('active', $copy->status);
        $this->assertEquals('QB', $copy->players[0]['positions']);
    }

    /** @test */
    public function import_skips_already_imported_group(): void
    {
        $tg = $this->makeTeamGroup();

        $this->postJson('/api/games/42/import-team-groups', [
            'group_ids' => [$tg->id], 'team_id' => $this->teamId, 'league_id' => 1, 'is_practice' => false,
        ]);

        $response = $this->postJson('/api/games/42/import-team-groups', [
            'group_ids' => [$tg->id], 'team_id' => $this->teamId, 'league_id' => 1, 'is_practice' => false,
        ]);

        $response->assertOk();
        $this->assertEmpty($response->json('data.created'));
        $this->assertContains($tg->id, $response->json('data.skipped'));
        $this->assertEquals(1, PersionalGrouping::where('game_id', 42)->where('source_team_group_id', $tg->id)->count());
    }

    /** @test */
    public function import_for_practice_game_sets_group_level_2(): void
    {
        $tg = $this->makeTeamGroup([
            'practice_players' => [['id' => 5, 'positions' => null]],
        ]);

        $this->postJson('/api/games/99/import-team-groups', [
            'group_ids' => [$tg->id], 'team_id' => $this->teamId, 'league_id' => 1, 'is_practice' => true,
        ])->assertOk();

        $copy = PersionalGrouping::where('game_id', 99)->where('source_team_group_id', $tg->id)->first();
        $this->assertEquals(2, (int) $copy->group_level);
        $this->assertNotNull($copy->practice_players);
        $this->assertNull($copy->players);
    }

    private function makePlayerWithPosition(string $name, string $positionName): Player
    {
        $player = Player::forceCreate([
            'name'     => $name,
            'number'   => '1',
            'position' => 1,
            'size'     => 180,
            'speed'    => 10,
            'strength' => 10,
        ]);

        $player->playerPosition()->create([
            'position_name' => $positionName,
        ]);

        return $player;
    }

    /** @test */
    public function players_endpoint_falls_back_to_the_players_own_position_when_no_team_player_position_pivot_exists(): void
    {
        // A freshly created player: linked to a Player with a playerPosition row, but with
        // NO team_player_position pivot rows yet (those only get created by the separate
        // position-assignment flow) - this is exactly the state of a brand-new player.
        $player = $this->makePlayerWithPosition('Newly Created Player', 'QB');

        $teamPlayer = TeamPlayer::forceCreate([
            'team_id'   => $this->teamId,
            'player_id' => $player->id,
            'name'      => $player->name,
            'number'    => $player->number,
            'speed'     => 10,
        ]);

        $response = $this->getJson("/api/teams/{$this->teamId}/players");

        $response->assertOk();
        $rows = collect($response->json('data'));
        $row = $rows->firstWhere('id', $teamPlayer->id);

        $this->assertNotNull($row, 'Expected the new player to be in the players list');
        $this->assertContains('QB', $row['positions']);
    }

    /** @test */
    public function players_endpoint_falls_back_to_the_players_own_position_for_a_practice_team(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $practiceTeam = LeagueTeam::forceCreate([
            'team_name'   => 'Practice Squad',
            'league_id'   => 1,
            'type'        => 1,
            'is_practice' => 1,
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $player = $this->makePlayerWithPosition('Newly Created Practice Player', 'WR');

        $teamPlayer = TeamPlayer::forceCreate([
            'team_id'   => $practiceTeam->id,
            'player_id' => $player->id,
            'name'      => $player->name,
            'number'    => $player->number,
            'speed'     => 10,
        ]);

        PracticeTeamPlayer::forceCreate([
            'team_id'   => $practiceTeam->id,
            'player_id' => $teamPlayer->id,
            'name'      => $player->name,
            'number'    => $player->number,
            'speed'     => 10,
        ]);

        $response = $this->getJson("/api/teams/{$practiceTeam->id}/players");

        $response->assertOk();
        $rows = collect($response->json('data'));
        $row = $rows->firstWhere('name', $player->name);

        $this->assertNotNull($row, 'Expected the new practice player to be in the players list');
        $this->assertContains('WR', $row['positions']);
    }

    /** @test */
    public function index_embeds_each_members_current_name_even_when_beyond_the_players_pickers_first_page(): void
    {
        // Create more team players than the frontend player-picker's first-page size (20),
        // so a naive "only check what's already loaded in the picker" lookup would miss this one.
        $teamPlayers = [];
        for ($i = 1; $i <= 25; $i++) {
            $player = Player::forceCreate([
                'name'     => "Roster Player {$i}",
                'number'   => (string) $i,
                'position' => 1,
                'size'     => 180,
                'speed'    => 10,
                'strength' => 10,
            ]);
            $teamPlayers[] = TeamPlayer::forceCreate([
                'team_id'   => $this->teamId,
                'player_id' => $player->id,
                'name'      => "Roster Player {$i}",
                'number'    => (string) $i,
                'speed'     => 10,
            ]);
        }

        $lateMember = $teamPlayers[20]; // 21st player - beyond a 20-per-page picker's first page.

        $group = $this->makeTeamGroup([
            'group_name' => 'Late Member Group',
            'players'    => [['id' => $lateMember->id, 'positions' => 'QB']],
        ]);

        $response = $this->getJson("/api/teams/{$this->teamId}/groups");

        $response->assertOk();
        $returnedGroup = collect($response->json('data'))->firstWhere('id', $group->id);
        $this->assertNotNull($returnedGroup);

        $memberEntry = collect($returnedGroup['players'])->firstWhere('id', $lateMember->id);
        $this->assertNotNull($memberEntry, 'Expected the saved member entry to still be present');
        $this->assertEquals('Roster Player 21', $memberEntry['name']);
        $this->assertEquals('21', $memberEntry['number']);
        $this->assertEquals('QB', $memberEntry['position']);
    }

    /** @test */
    public function index_falls_back_to_the_linked_players_name_when_the_team_player_has_none(): void
    {
        $player = Player::forceCreate([
            'name'     => 'Core Player Record',
            'number'   => '9',
            'position' => 1,
            'size'     => 180,
            'speed'    => 10,
            'strength' => 10,
        ]);

        $teamPlayer = TeamPlayer::forceCreate([
            'team_id'   => $this->teamId,
            'player_id' => $player->id,
            'name'      => null,
            'number'    => null,
            'speed'     => 10,
        ]);

        $group = $this->makeTeamGroup([
            'group_name' => 'Fallback Name Group',
            'players'    => [['id' => $teamPlayer->id, 'positions' => null]],
        ]);

        $response = $this->getJson("/api/teams/{$this->teamId}/groups");

        $response->assertOk();
        $returnedGroup = collect($response->json('data'))->firstWhere('id', $group->id);
        $memberEntry = collect($returnedGroup['players'])->firstWhere('id', $teamPlayer->id);

        $this->assertNotNull($memberEntry);
        $this->assertEquals('Core Player Record', $memberEntry['name']);
    }
}
