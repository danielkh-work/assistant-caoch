<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\OffensivePosition;
use App\Models\Play;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlayPlayerCountValidationTest extends TestCase
{
    use DatabaseTransactions;

    private function adminBasePayload(array $overrides = []): array
    {
        return array_merge([
            'play_name' => 'Admin Player Count Test Play',
            'min_expected_yard' => 'medium',
            'possession' => 'offensive',
        ], $overrides);
    }

    private function offensiveStrengths(int $count): array
    {
        $ids = OffensivePosition::pluck('id')->take($count);
        $values = [];
        foreach ($ids as $id) {
            $values[$id] = 20;
        }

        return $values;
    }

    private function makeLeague(User $owner, ?int $numberOfPlayers): League
    {
        $sportId = DB::table('sports')->insertGetId(['title' => 'Test Sport']);
        $league = new League();
        $league->user_id = $owner->id;
        $league->sport_id = $sportId;
        $league->league_rule_id = DB::table('league_rules')->value('id') ?? 1;
        $league->title = 'Player Count Test League';
        $league->number_of_team = 2;
        $league->number_of_players = $numberOfPlayers;
        $league->save();

        return $league;
    }

    private function apiBasePayload(int $leagueId, string $name): array
    {
        return [
            'play_name' => $name,
            'playType' => 'run',
            'league_id' => $leagueId,
            'play_type' => 1,
            'zone_selection' => 1,
            'min_expected_yard' => 'short',
            'max_expected_yard' => 'long',
            'target_offensive' => 1,
            'opposing_defensive' => 1,
            'pre_snap_motion' => 1,
            'play_action_fake' => 1,
            'possession' => 'offensive',
            'hmark_left' => UploadedFile::fake()->image('left.jpg'),
            'hmark_center' => UploadedFile::fake()->image('center.jpg'),
            'hmark_right' => UploadedFile::fake()->image('right.jpg'),
        ];
    }

    /** @test */
    public function admin_panel_rejects_more_positions_than_the_chosen_player_count()
    {
        $admin = User::factory()->create(['status' => 'approved']);

        $this->actingAs($admin)->post(route('play.store'), $this->adminBasePayload([
            'player_count' => '11',
            'offensive' => $this->offensiveStrengths(12),
        ]));

        $this->assertDatabaseMissing('plays', ['play_name' => 'Admin Player Count Test Play']);
    }

    /** @test */
    public function admin_panel_allows_exactly_the_chosen_player_count()
    {
        $admin = User::factory()->create(['status' => 'approved']);

        $this->actingAs($admin)->post(route('play.store'), $this->adminBasePayload([
            'player_count' => '11',
            'offensive' => $this->offensiveStrengths(11),
        ]));

        $play = Play::where('play_name', 'Admin Player Count Test Play')->firstOrFail();
        $this->assertSame(11, $play->player_count);
    }

    /** @test */
    public function admin_panel_leaves_untagged_plays_unrestricted()
    {
        $admin = User::factory()->create(['status' => 'approved']);

        $this->actingAs($admin)->post(route('play.store'), $this->adminBasePayload([
            'player_count' => '',
            'offensive' => $this->offensiveStrengths(15),
        ]));

        $play = Play::where('play_name', 'Admin Player Count Test Play')->firstOrFail();
        $this->assertNull($play->player_count);
    }

    /** @test */
    public function admin_panel_edit_rejects_pushing_an_existing_play_over_its_limit()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $play = Play::create([
            'play_name' => 'Existing Play', 'play_type' => 1, 'zone_selection' => 1,
            'min_expected_yard' => 'short', 'max_expected_yard' => '0',
            'pre_snap_motion' => 1, 'play_action_fake' => 1,
            'possession' => 'offensive', 'video_path' => '',
            'is_global' => true, 'player_count' => 11,
        ]);

        $this->actingAs($admin)->put(route('play.update', $play->id), $this->adminBasePayload([
            'play_name' => 'Existing Play',
            'player_count' => '11',
            'offensive' => $this->offensiveStrengths(12),
        ]));

        $this->assertSame(0, $play->fresh()->targetOffensivePlayers()->count());
    }

    /** @test */
    public function admin_panel_edit_forces_the_leagues_own_player_count_over_a_conflicting_manual_choice()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $league = $this->makeLeague($hc, 11);
        $play = Play::create([
            'play_name' => 'Base Play', 'play_type' => 1, 'zone_selection' => 1,
            'min_expected_yard' => 'short', 'max_expected_yard' => '0',
            'pre_snap_motion' => 1, 'play_action_fake' => 1,
            'possession' => 'offensive', 'video_path' => '', 'is_global' => true,
        ]);

        // Admin picks "12" in the dropdown, but this league already says 11 -
        // the league must win, not the dropdown.
        $this->actingAs($admin)->put(route('play.update', $play->id), $this->adminBasePayload([
            'play_name' => 'League Wins Play',
            'league_id' => $league->id,
            'player_count' => '12',
            'offensive' => $this->offensiveStrengths(10),
        ]));

        $this->assertSame(11, $play->fresh()->player_count);
    }

    /** @test */
    public function admin_panel_edit_rejects_against_the_leagues_forced_player_count_not_the_dropdown_value()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $league = $this->makeLeague($hc, 11);
        $play = Play::create([
            'play_name' => 'Base Play 2', 'play_type' => 1, 'zone_selection' => 1,
            'min_expected_yard' => 'short', 'max_expected_yard' => '0',
            'pre_snap_motion' => 1, 'play_action_fake' => 1,
            'possession' => 'offensive', 'video_path' => '', 'is_global' => true,
        ]);

        // Dropdown says "12" (would allow 12 positions), but the league is
        // forced to 11, so 12 filled positions must still be rejected.
        $this->actingAs($admin)->put(route('play.update', $play->id), $this->adminBasePayload([
            'play_name' => 'League Wins Play 2',
            'league_id' => $league->id,
            'player_count' => '12',
            'offensive' => $this->offensiveStrengths(12),
        ]));

        $this->assertSame(0, $play->fresh()->targetOffensivePlayers()->count());
    }

    /** @test */
    public function admin_panel_edit_respects_manual_choice_when_the_league_has_no_stated_player_count()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $league = $this->makeLeague($hc, null);
        $play = Play::create([
            'play_name' => 'Base Play 3', 'play_type' => 1, 'zone_selection' => 1,
            'min_expected_yard' => 'short', 'max_expected_yard' => '0',
            'pre_snap_motion' => 1, 'play_action_fake' => 1,
            'possession' => 'offensive', 'video_path' => '', 'is_global' => true,
        ]);

        $this->actingAs($admin)->put(route('play.update', $play->id), $this->adminBasePayload([
            'play_name' => 'Manual Choice Play',
            'league_id' => $league->id,
            'player_count' => '11',
            'offensive' => $this->offensiveStrengths(5),
        ]));

        $this->assertSame(11, $play->fresh()->player_count);
    }

    /** @test */
    public function web_app_rejects_more_positions_than_its_leagues_player_count()
    {
        Storage::fake('public');
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $league = $this->makeLeague($hc, 11);

        Sanctum::actingAs($hc);
        $response = $this->postJson('/api/uplaod-play', $this->apiBasePayload($league->id, 'Web App Over Limit') + [
            'offensive' => $this->offensiveStrengths(12),
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('plays', ['play_name' => 'Web App Over Limit']);
    }

    /** @test */
    public function web_app_allows_exactly_its_leagues_player_count_without_being_asked()
    {
        Storage::fake('public');
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $league = $this->makeLeague($hc, 12);

        Sanctum::actingAs($hc);
        $response = $this->postJson('/api/uplaod-play', $this->apiBasePayload($league->id, 'Web App At Limit') + [
            'offensive' => $this->offensiveStrengths(12),
        ]);

        $response->assertStatus(200);
        $play = Play::where('play_name', 'Web App At Limit')->firstOrFail();
        $this->assertSame(12, $play->player_count);
    }
}
