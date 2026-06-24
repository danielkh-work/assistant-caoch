<?php

namespace Tests\Feature;

use App\Models\LeagueTeam;
use App\Models\PersionalGrouping;
use App\Models\TeamGroup;
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
}
