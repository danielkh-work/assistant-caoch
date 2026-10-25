<?php

namespace Tests\Unit;

use App\Models\League;
use App\Models\Play;
use App\Models\User;
use App\Services\PlayAuthorizationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlayAuthorizationServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeLeague(User $owner): League
    {
        $sportId = \DB::table('sports')->insertGetId(['title' => 'Test Sport']);
        $league = new League();
        $league->user_id = $owner->id;
        $league->sport_id = $sportId;
        $league->league_rule_id = \DB::table('league_rules')->value('id') ?? 1;
        $league->title = 'Auth Test League';
        $league->number_of_team = 2;
        $league->save();
        return $league;
    }

    /** @test */
    public function head_coach_can_modify_a_play_in_their_own_league()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $league = $this->makeLeague($hc);
        $play = Play::create([
            'league_id' => $league->id, 'play_name' => 'X', 'play_type' => 1,
            'zone_selection' => 1, 'min_expected_yard' => '0', 'max_expected_yard' => '0',
            'pre_snap_motion' => 1, 'play_action_fake' => 1, 'possession' => 'offensive', 'video_path' => '',
        ]);

        $service = app(PlayAuthorizationService::class);
        $this->assertTrue($service->canModify($hc, $play));
        $this->assertTrue($service->canDelete($hc, $play));
    }

    /** @test */
    public function assistant_coach_can_never_delete_any_play()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $league = $this->makeLeague($hc);
        $ac = User::factory()->create([
            'role' => 'assistant_coach', 'head_coach_id' => $hc->id, 'status' => 'approved',
        ]);
        $ownPlay = Play::create([
            'league_id' => $league->id, 'play_name' => 'AC Own', 'play_type' => 1,
            'zone_selection' => 1, 'min_expected_yard' => '0', 'max_expected_yard' => '0',
            'pre_snap_motion' => 1, 'play_action_fake' => 1, 'possession' => 'offensive', 'video_path' => '',
            'created_by_user_id' => $ac->id,
        ]);

        $service = app(PlayAuthorizationService::class);
        $this->assertFalse($service->canDelete($ac, $ownPlay));
    }

    /** @test */
    public function assistant_coach_cannot_customize_a_global_play()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $this->makeLeague($hc);
        $ac = User::factory()->create([
            'role' => 'assistant_coach', 'head_coach_id' => $hc->id, 'status' => 'approved',
        ]);
        $global = Play::create([
            'league_id' => null, 'is_global' => true, 'play_name' => 'Global X', 'play_type' => 1,
            'zone_selection' => 1, 'min_expected_yard' => '0', 'max_expected_yard' => '0',
            'pre_snap_motion' => 1, 'play_action_fake' => 1, 'possession' => 'offensive', 'video_path' => '',
        ]);

        $service = app(PlayAuthorizationService::class);
        $this->assertFalse($service->canModify($ac, $global));
    }

    /** @test */
    public function head_coach_cannot_modify_another_leagues_play()
    {
        $hcA = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $hcB = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $leagueB = $this->makeLeague($hcB);
        $playInB = Play::create([
            'league_id' => $leagueB->id, 'play_name' => 'Not Yours', 'play_type' => 1,
            'zone_selection' => 1, 'min_expected_yard' => '0', 'max_expected_yard' => '0',
            'pre_snap_motion' => 1, 'play_action_fake' => 1, 'possession' => 'offensive', 'video_path' => '',
        ]);

        $service = app(PlayAuthorizationService::class);
        $this->assertFalse($service->canModify($hcA, $playInB));
        $this->assertFalse($service->canDelete($hcA, $playInB));
    }

    /** @test */
    public function head_coach_can_customize_or_delete_a_global_play_for_a_league_they_act_in()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $league = $this->makeLeague($hc);
        $global = Play::create([
            'league_id' => null, 'is_global' => true, 'play_name' => 'Global Y', 'play_type' => 1,
            'zone_selection' => 1, 'min_expected_yard' => '0', 'max_expected_yard' => '0',
            'pre_snap_motion' => 1, 'play_action_fake' => 1, 'possession' => 'offensive', 'video_path' => '',
        ]);

        $service = app(PlayAuthorizationService::class);
        $this->assertTrue($service->canModify($hc, $global, $league->id));
        $this->assertTrue($service->canDelete($hc, $global, $league->id));
    }

    /** @test */
    public function head_coach_cannot_customize_or_hide_a_global_play_for_a_league_they_do_not_act_in()
    {
        $hcA = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $hcB = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $leagueB = $this->makeLeague($hcB);
        $global = Play::create([
            'league_id' => null, 'is_global' => true, 'play_name' => 'Global Z', 'play_type' => 1,
            'zone_selection' => 1, 'min_expected_yard' => '0', 'max_expected_yard' => '0',
            'pre_snap_motion' => 1, 'play_action_fake' => 1, 'possession' => 'offensive', 'video_path' => '',
        ]);

        $service = app(PlayAuthorizationService::class);
        $this->assertFalse($service->canModify($hcA, $global, $leagueB->id));
        $this->assertFalse($service->canDelete($hcA, $global, $leagueB->id));
    }

    /** @test */
    public function head_coach_acting_on_a_global_play_without_a_league_id_is_denied()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $global = Play::create([
            'league_id' => null, 'is_global' => true, 'play_name' => 'Global W', 'play_type' => 1,
            'zone_selection' => 1, 'min_expected_yard' => '0', 'max_expected_yard' => '0',
            'pre_snap_motion' => 1, 'play_action_fake' => 1, 'possession' => 'offensive', 'video_path' => '',
        ]);

        $service = app(PlayAuthorizationService::class);
        $this->assertFalse($service->canModify($hc, $global));
        $this->assertFalse($service->canDelete($hc, $global));
    }

    /** @test */
    public function assistant_coach_cannot_restore_a_global_play_for_their_head_coachs_league()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $league = $this->makeLeague($hc);
        $ac = User::factory()->create(['role' => 'assistant_coach', 'head_coach_id' => $hc->id, 'status' => 'approved']);

        $service = app(PlayAuthorizationService::class);
        $this->assertFalse($service->canRestore($ac, $league->id));
    }

    /** @test */
    public function head_coach_cannot_restore_for_another_head_coachs_league()
    {
        $hcA = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $hcB = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $leagueB = $this->makeLeague($hcB);

        $service = app(PlayAuthorizationService::class);
        $this->assertFalse($service->canRestore($hcA, $leagueB->id));
    }

    /** @test */
    public function assistant_coach_from_a_different_head_coach_cannot_modify_or_delete_a_play_in_this_league()
    {
        $hcA = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $hcB = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $leagueA = $this->makeLeague($hcA);
        $acOfB = User::factory()->create(['role' => 'assistant_coach', 'head_coach_id' => $hcB->id, 'status' => 'approved']);
        $playInA = Play::create([
            'league_id' => $leagueA->id, 'play_name' => 'League A Play', 'play_type' => 1,
            'zone_selection' => 1, 'min_expected_yard' => '0', 'max_expected_yard' => '0',
            'pre_snap_motion' => 1, 'play_action_fake' => 1, 'possession' => 'offensive', 'video_path' => '',
        ]);

        $service = app(PlayAuthorizationService::class);
        $this->assertFalse($service->canModify($acOfB, $playInA));
        $this->assertFalse($service->canDelete($acOfB, $playInA));
    }
}
