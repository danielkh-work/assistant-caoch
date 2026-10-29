<?php

namespace Tests\Feature;

use App\Models\ConfiguredPlayingTeamPlayer;
use App\Models\Game;
use App\Models\League;
use App\Models\LeagueTeam;
use App\Models\Player;
use App\Models\TeamPlayer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Deleting a player from a team's roster left `configured_playing_team_players`
 * rows (the per-match "Configure Players" roster) dangling - the row's player_id
 * kept pointing at a TeamPlayer that no longer resolves. Every screen that reads
 * that per-match roster (most notably the opposition/visiting team's "Personal
 * Grouping" player pool, which eager-loads `player.player` and drops any row
 * where that chain breaks) would silently lose that row. If an opposition
 * team's whole roster had been replaced this way at some point, its entire
 * player pool could vanish from the grouping screen with zero explanation.
 */
class PlayerDeletionConfiguredRosterCleanupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->beforeApplicationDestroyed(fn () => DB::rollBack());
        DB::beginTransaction();
    }

    private function actingUser(): User
    {
        $user = User::create([
            'name' => 'Roster Cleanup Coach',
            'email' => 'roster-cleanup-'.uniqid().'@test.com',
            'password' => Hash::make('secret'),
            'role' => 'head_coach',
            'status' => 'approved',
            'sport_id' => 1,
        ]);
        Sanctum::actingAs($user);
        $this->actingAs($user, 'api');

        return $user;
    }

    /** @test */
    public function deleting_a_player_from_the_opposition_team_also_clears_its_configured_match_roster_rows(): void
    {
        $user = $this->actingUser();

        $league = League::forceCreate([
            'user_id' => $user->id,
            'sport_id' => 1,
            'league_rule_id' => DB::table('league_rules')->value('id') ?? 1,
            'title' => 'Roster Cleanup League',
            'number_of_players' => 12,
            'practice_number_players' => 7,
        ]);

        $myTeam = LeagueTeam::forceCreate([
            'league_id' => $league->id,
            'team_name' => 'My Team',
            'type' => 1,
            'is_practice' => 0,
        ]);
        $oppTeam = LeagueTeam::forceCreate([
            'league_id' => $league->id,
            'team_name' => 'Opp Team',
            'type' => 1,
            'is_practice' => 0,
        ]);

        $game = Game::create([
            'league_id' => $league->id,
            'my_team_id' => $myTeam->id,
            'oponent_team_id' => $oppTeam->id,
            'date' => now()->format('Y-m-d'),
            'location_type' => 'home',
            'creator_id' => $user->id,
            'type' => 1,
        ]);

        $player = Player::forceCreate(['name' => 'Opposition Player', 'speed' => 5]);
        $teamPlayer = TeamPlayer::forceCreate([
            'team_id' => $oppTeam->id,
            'player_id' => $player->id,
            'name' => 'Opposition Player',
            'type' => 'offence',
            'position' => 'offence',
            'speed' => 5,
        ]);

        $configuredRow = ConfiguredPlayingTeamPlayer::forceCreate([
            'team_id' => $oppTeam->id,
            'match_id' => $game->id,
            'type' => 'offensive',
            'team_type' => 2,
            'game_type' => 1,
            'player_id' => $teamPlayer->id,
        ]);

        $view = $this->getJson(
            "/api/configure-player-view?team_id={$oppTeam->id}&game_id={$game->id}&league_id={$league->id}"
        );
        $view->assertOk();
        $this->assertCount(1, $view->json('data'));
        $this->assertNotNull($view->json('data.0.player.player.name'), 'Sanity check: row resolves before deletion.');

        $response = $this->getJson("/api/delete-player/{$player->id}/{$oppTeam->id}");
        $response->assertOk();

        $this->assertDatabaseMissing('team_players', ['id' => $teamPlayer->id, 'deleted_at' => null]);

        $this->assertSoftDeleted('configured_playing_team_players', ['id' => $configuredRow->id]);

        $afterView = $this->getJson(
            "/api/configure-player-view?team_id={$oppTeam->id}&game_id={$game->id}&league_id={$league->id}"
        );
        $afterView->assertOk();
        $this->assertCount(
            0,
            $afterView->json('data'),
            'The deleted player\'s configure row should be gone, not left behind with a null player relation.'
        );
    }
}
