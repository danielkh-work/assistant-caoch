<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\League;
use App\Models\LeagueTeam;
use App\Models\PersionalGrouping;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PersonalGroupingPracticeTeamRemovalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->beforeApplicationDestroyed(function () {
            DB::rollBack();
        });

        DB::beginTransaction();

        if (! Schema::hasTable('personal_groupings')) {
            $this->markTestSkipped('personal_groupings table not available.');
        }
    }

    /** @test */
    public function removing_practice_team_player_strips_them_from_groups_and_demotes_invalid_active_groups(): void
    {
        $user = User::create([
            'name' => 'Coach',
            'email' => 'group-removal@test.com',
            'password' => Hash::make('secret'),
            'role' => 'head_coach',
            'status' => 'approved',
            'sport_id' => 1,
        ]);

        $league = League::forceCreate([
            'user_id' => $user->id,
            'sport_id' => 1,
            'name' => 'Test League',
            'number_of_players' => 12,
            'practice_number_players' => 7,
        ]);

        $team = LeagueTeam::forceCreate([
            'league_id' => $league->id,
            'team_name' => 'Practice Team',
            'is_practice' => 1,
        ]);

        $game = Game::create([
            'league_id' => $league->id,
            'my_team_id' => $team->id,
            'oponent_team_id' => 0,
            'date' => now()->format('Y-m-d'),
            'location_type' => 'home',
            'creator_id' => $user->id,
            'type' => 2,
        ]);

        $memberIds = [101, 102, 103, 104, 105, 106, 107];
        $practicePlayers = array_map(
            fn (int $id) => ['id' => $id, 'positions' => 'QB'],
            $memberIds
        );

        $group = PersionalGrouping::create([
            'game_id' => $game->id,
            'league_id' => $league->id,
            'team_id' => $team->id,
            'group_name' => 'Defense Group',
            'type' => 'defense',
            'group_level' => 2,
            'status' => 'active',
            'practice_players' => $practicePlayers,
            'players' => null,
        ]);

        foreach ($memberIds as $practicePlayerId) {
            if (Schema::hasTable('configured_playing_team_players')) {
                DB::table('configured_playing_team_players')->insert([
                    'match_id' => $game->id,
                    'team_id' => $team->id,
                    'practice_player_id' => $practicePlayerId,
                    'team_type' => 1,
                    'game_type' => 2,
                    'type' => 'defensive',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        PersionalGrouping::removeMemberIdsFromAllTeamGroups($team->id, [101], []);

        $group->refresh();

        $remainingIds = collect($group->practice_players)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertNotContains(101, $remainingIds);
        $this->assertCount(6, $remainingIds);
        $this->assertSame('inactive', strtolower((string) $group->status));
    }
}
