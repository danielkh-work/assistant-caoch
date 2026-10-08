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
}
