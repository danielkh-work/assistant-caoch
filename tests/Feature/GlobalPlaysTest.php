<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\Play;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GlobalPlaysTest extends TestCase
{
    use DatabaseTransactions;

    protected function givePlayPermissions(User $user): void
    {
        $user->syncPermissions(['play.view', 'play.create', 'play.edit']);
        if ($user->role === 'head_coach') {
            $user->givePermissionTo('play.delete');
        }
    }

    protected function makeLeague(User $owner): League
    {
        $sportId = DB::table('sports')->insertGetId(['title' => 'Test Sport']);
        $league = new League();
        $league->user_id = $owner->id;
        $league->sport_id = $sportId;
        $league->league_rule_id = DB::table('league_rules')->value('id') ?? 1;
        $league->title = 'Global Plays Test League';
        $league->number_of_team = 2;
        $league->save();
        return $league;
    }

    protected function basePlayPayload(int $leagueId, string $name): array
    {
        return [
            'play_name' => $name,
            'playType' => 'Pass',
            'league_id' => $leagueId,
            'play_type' => 1,
            'zone_selection' => 1,
            'min_expected_yard' => '0',
            'max_expected_yard' => '0',
            'target_offensive' => 1,
            'opposing_defensive' => 1,
            'pre_snap_motion' => 1,
            'play_action_fake' => 1,
            'possession' => 'offensive',
            'hmark_left' => UploadedFile::fake()->image('play-left.jpg'),
            'hmark_center' => UploadedFile::fake()->image('play-center.jpg'),
            'hmark_right' => UploadedFile::fake()->image('play-right.jpg'),
        ];
    }

    protected function makeGlobalPlay(string $name): Play
    {
        return Play::create(array_merge($this->playColumns($name), [
            'league_id' => null, 'is_global' => true, 'created_by' => 'admin',
        ]));
    }

    protected function makeLeaguePlay(int $leagueId, string $name, ?int $createdByUserId = null): Play
    {
        return Play::create(array_merge($this->playColumns($name), [
            'league_id' => $leagueId, 'is_global' => false, 'created_by_user_id' => $createdByUserId,
        ]));
    }

    private function playColumns(string $name): array
    {
        return [
            'play_name' => $name, 'play_type' => 1, 'zone_selection' => 1,
            'min_expected_yard' => '0', 'max_expected_yard' => '0',
            'pre_snap_motion' => 1, 'play_action_fake' => 1,
            'possession' => 'offensive', 'video_path' => '',
        ];
    }

    /** @test */
    public function scaffolding_helpers_work()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $this->givePlayPermissions($hc);
        $league = $this->makeLeague($hc);
        $global = $this->makeGlobalPlay('Scaffold Global');
        $owned = $this->makeLeaguePlay($league->id, 'Scaffold Owned');

        $this->assertTrue($global->is_global);
        $this->assertNull($global->league_id);
        $this->assertFalse($owned->is_global);
        $this->assertSame($league->id, $owned->league_id);
        $this->assertTrue($hc->can('play.delete'));

        Storage::fake('public');
        Sanctum::actingAs($hc);
        $response = $this->postJson('/api/uplaod-play', $this->basePlayPayload($league->id, 'Scaffold Via API'));
        $response->assertStatus(200);
    }

    /** @test */
    public function head_coach_sees_global_plays_plus_their_own_league_plays()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $this->givePlayPermissions($hc);
        $league = $this->makeLeague($hc);

        $global = $this->makeGlobalPlay('Global Sweep');
        $own = $this->makeLeaguePlay($league->id, 'My Own Play');

        Sanctum::actingAs($hc);
        $response = $this->getJson('/api/upload-play-list?league_id=' . $league->id);

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('play_name');
        $this->assertTrue($names->contains('Global Sweep'));
        $this->assertTrue($names->contains('My Own Play'));
    }

    /** @test */
    public function hidden_global_play_does_not_appear_for_that_league()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $this->givePlayPermissions($hc);
        $league = $this->makeLeague($hc);
        $global = $this->makeGlobalPlay('Hideable Play');

        \App\Models\LeaguePlayOverride::create([
            'league_id' => $league->id,
            'global_play_id' => $global->id,
            'status' => 'hidden',
        ]);

        Sanctum::actingAs($hc);
        $response = $this->getJson('/api/upload-play-list?league_id=' . $league->id);

        $names = collect($response->json('data'))->pluck('play_name');
        $this->assertFalse($names->contains('Hideable Play'));
    }

    /** @test */
    public function a_different_leagues_plays_are_never_shown()
    {
        $hcA = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $hcB = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $this->givePlayPermissions($hcA);
        $leagueA = $this->makeLeague($hcA);
        $leagueB = $this->makeLeague($hcB);
        $this->makeLeaguePlay($leagueB->id, 'Belongs To League B');

        Sanctum::actingAs($hcA);
        $response = $this->getJson('/api/upload-play-list?league_id=' . $leagueA->id);

        $names = collect($response->json('data'))->pluck('play_name');
        $this->assertFalse($names->contains('Belongs To League B'));
    }

    /** @test */
    public function assistant_coach_created_play_is_visible_to_head_coach_and_sibling_assistant()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $this->givePlayPermissions($hc);
        $league = $this->makeLeague($hc);
        $ac1 = User::factory()->create(['role' => 'assistant_coach', 'head_coach_id' => $hc->id, 'status' => 'approved']);
        $ac2 = User::factory()->create(['role' => 'assistant_coach', 'head_coach_id' => $hc->id, 'status' => 'approved']);
        $this->givePlayPermissions($ac1);
        $this->givePlayPermissions($ac2);

        Storage::fake('public');
        Sanctum::actingAs($ac1);
        $this->postJson('/api/uplaod-play', $this->basePlayPayload($league->id, 'AC Created Play'))
            ->assertStatus(200);

        $created = \App\Models\Play::where('play_name', 'AC Created Play')->first();
        $this->assertEquals($ac1->id, $created->created_by_user_id);

        foreach ([$hc, $ac2] as $viewer) {
            Sanctum::actingAs($viewer);
            $response = $this->getJson('/api/upload-play-list?league_id=' . $league->id);
            $names = collect($response->json('data'))->pluck('play_name');
            $this->assertTrue($names->contains('AC Created Play'));
        }
    }

    /** @test */
    public function head_coach_editing_a_global_play_clones_it_and_leaves_the_original_untouched()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $this->givePlayPermissions($hc);
        $league = $this->makeLeague($hc);
        $global = $this->makeGlobalPlay('Original Sweep');

        Storage::fake('public');
        Sanctum::actingAs($hc);
        $response = $this->postJson('/api/update-play/' . $global->id, $this->basePlayPayload($league->id, 'My Customized Sweep'));

        $response->assertStatus(200);

        $global->refresh();
        $this->assertSame('Original Sweep', $global->play_name); // untouched

        $override = \App\Models\LeaguePlayOverride::where([
            'league_id' => $league->id, 'global_play_id' => $global->id,
        ])->first();
        $this->assertNotNull($override);
        $this->assertSame('customized', $override->status);

        $clone = Play::find($override->customized_play_id);
        $this->assertSame('My Customized Sweep', $clone->play_name);
        $this->assertSame($league->id, $clone->league_id);
        $this->assertFalse((bool) $clone->is_global);
    }

    /** @test */
    public function assistant_coach_cannot_edit_a_global_play_via_the_endpoint()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $ac = User::factory()->create(['role' => 'assistant_coach', 'head_coach_id' => $hc->id, 'status' => 'approved']);
        $this->givePlayPermissions($ac);
        $league = $this->makeLeague($hc);
        $global = $this->makeGlobalPlay('Protected Global');

        Storage::fake('public');
        Sanctum::actingAs($ac);
        $response = $this->postJson('/api/update-play/' . $global->id, $this->basePlayPayload($league->id, 'Hacked Name'));

        $response->assertStatus(403);
        $global->refresh();
        $this->assertSame('Protected Global', $global->play_name);
    }

    /** @test */
    public function head_coach_cannot_edit_another_leagues_play_by_id()
    {
        $hcA = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $hcB = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $this->givePlayPermissions($hcA);
        $leagueB = $this->makeLeague($hcB);
        $playInB = $this->makeLeaguePlay($leagueB->id, 'League B Play');

        Storage::fake('public');
        Sanctum::actingAs($hcA);
        $response = $this->postJson('/api/update-play/' . $playInB->id, $this->basePlayPayload($leagueB->id, 'Stolen'));

        $response->assertStatus(403);
        $playInB->refresh();
        $this->assertSame('League B Play', $playInB->play_name);
    }

    /** @test */
    public function editing_the_same_global_play_twice_from_the_same_league_reuses_the_clone()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $this->givePlayPermissions($hc);
        $league = $this->makeLeague($hc);
        $global = $this->makeGlobalPlay('Reusable Global');

        Storage::fake('public');
        Sanctum::actingAs($hc);
        $this->postJson('/api/update-play/' . $global->id, $this->basePlayPayload($league->id, 'First Edit'))
            ->assertStatus(200);
        $firstOverride = \App\Models\LeaguePlayOverride::where([
            'league_id' => $league->id, 'global_play_id' => $global->id,
        ])->first();
        $firstCloneId = $firstOverride->customized_play_id;

        $this->postJson('/api/update-play/' . $firstCloneId, $this->basePlayPayload($league->id, 'Second Edit'))
            ->assertStatus(200);

        $this->assertSame(1, \App\Models\LeaguePlayOverride::where([
            'league_id' => $league->id, 'global_play_id' => $global->id,
        ])->count());
        $clone = Play::find($firstCloneId);
        $this->assertSame('Second Edit', $clone->play_name);
    }

    /** @test */
    public function customizing_a_global_play_with_a_hmark_image_does_not_delete_the_original_global_images()
    {
        // uploadImage() (app/Helpers/GeneralHelper.php) decides whether to replace
        // an "old" hmark file by checking File::exists(public_path($oldPath)) and,
        // if so, File::delete()'ing it - a real filesystem path reached via the
        // File facade, not the Storage disk abstraction. Storage::fake('public')
        // (used elsewhere in this file to stop leaking real upload files) does NOT
        // intercept this path, so proving the deletion-avoidance guard actually
        // works requires planting a real file at the exact path uploadImage()
        // checks, then confirming it survives the clone-creation edit.
        $originalPath = public_path('uploads/public/original-' . uniqid() . '.jpg');
        \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($originalPath));
        \Illuminate\Support\Facades\File::put($originalPath, 'fake-image-bytes');
        $relativeOriginalPath = 'uploads/public/' . basename($originalPath);

        try {
            $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
            $this->givePlayPermissions($hc);
            $league = $this->makeLeague($hc);

            $global = $this->makeGlobalPlay('Imaged Global');
            $global->hmark_left = $relativeOriginalPath;
            $global->hmark_center = $relativeOriginalPath;
            $global->hmark_right = $relativeOriginalPath;
            $global->save();

            Storage::fake('public');
            Sanctum::actingAs($hc);
            // basePlayPayload() already attaches fresh fake hmark_left/center/right
            // uploads, which is exactly what triggers the old-path delete check.
            $response = $this->postJson('/api/update-play/' . $global->id, $this->basePlayPayload($league->id, 'Customized With New Image'));

            $response->assertStatus(200);

            // The original Global Play row and its backing file must be completely untouched.
            $global->refresh();
            $this->assertSame($relativeOriginalPath, $global->hmark_left);
            $this->assertTrue(\Illuminate\Support\Facades\File::exists($originalPath));

            // The clone got its own new image, not the shared original path.
            $override = \App\Models\LeaguePlayOverride::where([
                'league_id' => $league->id, 'global_play_id' => $global->id,
            ])->first();
            $clone = Play::find($override->customized_play_id);
            $this->assertNotSame($relativeOriginalPath, $clone->hmark_left);
        } finally {
            \Illuminate\Support\Facades\File::delete($originalPath);
        }
    }

    /** @test */
    public function editing_a_play_cannot_migrate_it_to_a_different_league_the_coach_also_owns()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $this->givePlayPermissions($hc);
        $leagueA = $this->makeLeague($hc);
        $leagueB = $this->makeLeague($hc);
        $playInA = $this->makeLeaguePlay($leagueA->id, 'Stays In A');

        Storage::fake('public');
        Sanctum::actingAs($hc);
        $response = $this->postJson('/api/update-play/' . $playInA->id, $this->basePlayPayload($leagueB->id, 'Renamed But Not Moved'));

        $response->assertStatus(200);
        $playInA->refresh();
        $this->assertSame((int) $leagueA->id, (int) $playInA->league_id);
        $this->assertSame('Renamed But Not Moved', $playInA->play_name); // the edit itself still applies
    }

    /** @test */
    public function head_coach_deleting_a_global_play_only_hides_it_for_their_league()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $this->givePlayPermissions($hc);
        $league = $this->makeLeague($hc);
        $global = $this->makeGlobalPlay('Shared Dive');

        Sanctum::actingAs($hc);
        $response = $this->getJson('/api/delete-play/' . $global->id . '?league_id=' . $league->id);

        $response->assertStatus(200);
        $this->assertNotNull(Play::find($global->id)); // never actually deleted
        $this->assertDatabaseHas('league_play_overrides', [
            'league_id' => $league->id, 'global_play_id' => $global->id, 'status' => 'hidden',
        ]);
    }

    /** @test */
    public function hiding_a_global_play_does_not_affect_a_different_head_coach()
    {
        $hcA = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $hcB = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $this->givePlayPermissions($hcA);
        $this->givePlayPermissions($hcB);
        $leagueA = $this->makeLeague($hcA);
        $leagueB = $this->makeLeague($hcB);
        $global = $this->makeGlobalPlay('Shared Across Two HCs');

        Sanctum::actingAs($hcA);
        $this->getJson('/api/delete-play/' . $global->id . '?league_id=' . $leagueA->id)->assertStatus(200);

        Sanctum::actingAs($hcB);
        $response = $this->getJson('/api/upload-play-list?league_id=' . $leagueB->id);
        $names = collect($response->json('data'))->pluck('play_name');
        $this->assertTrue($names->contains('Shared Across Two HCs'));
    }

    /** @test */
    public function assistant_coach_cannot_delete_any_play_including_their_own()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $ac = User::factory()->create(['role' => 'assistant_coach', 'head_coach_id' => $hc->id, 'status' => 'approved']);
        $this->givePlayPermissions($ac);
        $league = $this->makeLeague($hc);
        $ownPlay = $this->makeLeaguePlay($league->id, 'AC Own Play', $ac->id);

        Sanctum::actingAs($ac);
        $response = $this->getJson('/api/delete-play/' . $ownPlay->id . '?league_id=' . $league->id);

        $response->assertStatus(403);
        $this->assertNotNull(Play::find($ownPlay->id));
    }

    /** @test */
    public function head_coach_cannot_delete_another_leagues_play_by_id()
    {
        $hcA = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $hcB = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $this->givePlayPermissions($hcA);
        $leagueB = $this->makeLeague($hcB);
        $playInB = $this->makeLeaguePlay($leagueB->id, 'League B Only');

        Sanctum::actingAs($hcA);
        $response = $this->getJson('/api/delete-play/' . $playInB->id . '?league_id=' . $leagueB->id);

        $response->assertStatus(403);
        $this->assertNotNull(Play::find($playInB->id));
    }

    /** @test */
    public function head_coach_can_restore_a_hidden_global_play()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $this->givePlayPermissions($hc);
        $league = $this->makeLeague($hc);
        $global = $this->makeGlobalPlay('Restorable Play');
        \App\Models\LeaguePlayOverride::create([
            'league_id' => $league->id, 'global_play_id' => $global->id, 'status' => 'hidden',
        ]);

        Sanctum::actingAs($hc);
        $response = $this->postJson('/api/restore-global-play/' . $global->id, ['league_id' => $league->id]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('league_play_overrides', [
            'league_id' => $league->id, 'global_play_id' => $global->id,
        ]);

        $listResponse = $this->getJson('/api/upload-play-list?league_id=' . $league->id);
        $names = collect($listResponse->json('data'))->pluck('play_name');
        $this->assertTrue($names->contains('Restorable Play'));
    }

    /** @test */
    public function assistant_coach_cannot_restore_a_hidden_global_play()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $ac = User::factory()->create(['role' => 'assistant_coach', 'head_coach_id' => $hc->id, 'status' => 'approved']);
        $this->givePlayPermissions($ac);
        $league = $this->makeLeague($hc);
        $global = $this->makeGlobalPlay('Stays Hidden');
        \App\Models\LeaguePlayOverride::create([
            'league_id' => $league->id, 'global_play_id' => $global->id, 'status' => 'hidden',
        ]);

        Sanctum::actingAs($ac);
        $response = $this->postJson('/api/restore-global-play/' . $global->id, ['league_id' => $league->id]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('league_play_overrides', [
            'league_id' => $league->id, 'global_play_id' => $global->id, 'status' => 'hidden',
        ]);
    }

    /** @test */
    public function hidden_global_plays_endpoint_lists_what_this_league_has_hidden()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $this->givePlayPermissions($hc);
        $league = $this->makeLeague($hc);
        $global = $this->makeGlobalPlay('Hidden One');
        \App\Models\LeaguePlayOverride::create([
            'league_id' => $league->id, 'global_play_id' => $global->id, 'status' => 'hidden',
        ]);

        Sanctum::actingAs($hc);
        $response = $this->getJson('/api/hidden-global-plays?league_id=' . $league->id);

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('play_name');
        $this->assertTrue($names->contains('Hidden One'));
    }

    /** @test */
    public function head_coach_cannot_restore_a_global_play_for_a_league_they_do_not_act_in()
    {
        $hcA = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $hcB = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $this->givePlayPermissions($hcA);
        $leagueB = $this->makeLeague($hcB);
        $global = $this->makeGlobalPlay('Cross League Restore Target');
        \App\Models\LeaguePlayOverride::create([
            'league_id' => $leagueB->id, 'global_play_id' => $global->id, 'status' => 'hidden',
        ]);

        Sanctum::actingAs($hcA);
        $response = $this->postJson('/api/restore-global-play/' . $global->id, ['league_id' => $leagueB->id]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('league_play_overrides', [
            'league_id' => $leagueB->id, 'global_play_id' => $global->id, 'status' => 'hidden',
        ]);
    }
}
