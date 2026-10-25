<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\Play;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlayControllerAdminStarterTest extends TestCase
{
    use DatabaseTransactions;

    private function makeGlobalPlay(bool $isStarter = true): Play
    {
        return Play::create([
            'play_name' => 'Admin Starter Test Play',
            'play_type' => 1,
            'zone_selection' => 1,
            'min_expected_yard' => '0',
            'max_expected_yard' => '0',
            'pre_snap_motion' => 1,
            'play_action_fake' => 1,
            'possession' => 'offensive',
            'video_path' => '',
            'league_id' => null,
            'is_global' => true,
            'is_starter_play' => $isStarter,
        ]);
    }

    private function makeLeagueScopedPlay(): Play
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $sportId = DB::table('sports')->insertGetId(['title' => 'Admin Starter Test Sport']);
        $league = new League();
        $league->user_id = $admin->id;
        $league->sport_id = $sportId;
        $league->league_rule_id = DB::table('league_rules')->value('id') ?? 1;
        $league->title = 'Admin Starter Test League';
        $league->number_of_team = 1;
        $league->save();

        return Play::create([
            'play_name' => 'League Scoped Test Play',
            'play_type' => 1,
            'zone_selection' => 1,
            'min_expected_yard' => '0',
            'max_expected_yard' => '0',
            'pre_snap_motion' => 1,
            'play_action_fake' => 1,
            'possession' => 'offensive',
            'video_path' => '',
            'league_id' => $league->id,
            'is_global' => false,
        ]);
    }

    /** @test */
    public function admin_can_toggle_a_global_plays_starter_status()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $play = $this->makeGlobalPlay(isStarter: true);

        $response = $this->actingAs($admin)->postJson(route('play.toggle-starter', $play->id));

        $response->assertStatus(200);
        $response->assertJson(['is_starter_play' => false]);
        $this->assertFalse($play->fresh()->is_starter_play);
    }

    /** @test */
    public function toggling_starter_status_twice_returns_it_to_the_original_value()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $play = $this->makeGlobalPlay(isStarter: true);

        $this->actingAs($admin)->postJson(route('play.toggle-starter', $play->id));
        $this->actingAs($admin)->postJson(route('play.toggle-starter', $play->id));

        $this->assertTrue($play->fresh()->is_starter_play);
    }

    /** @test */
    public function a_league_scoped_play_cannot_be_made_a_starter_play()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $play = $this->makeLeagueScopedPlay();

        $response = $this->actingAs($admin)->postJson(route('play.toggle-starter', $play->id));

        $response->assertStatus(422);
        $this->assertTrue($play->fresh()->is_starter_play); // column default, never changed
    }
}
