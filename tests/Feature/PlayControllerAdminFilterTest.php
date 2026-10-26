<?php

namespace Tests\Feature;

use App\Models\Play;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlayControllerAdminFilterTest extends TestCase
{
    use DatabaseTransactions;

    private function makePlay(string $name, array $overrides = []): Play
    {
        return Play::create(array_merge([
            'play_name' => $name, 'play_type' => 1, 'zone_selection' => 1,
            'min_expected_yard' => 'medium', 'max_expected_yard' => '0',
            'pre_snap_motion' => 1, 'play_action_fake' => 1,
            'possession' => 'offensive', 'video_path' => '',
            'is_global' => true, 'league_id' => null,
            'strategies' => 'regular', 'preferred_down' => '1',
        ], $overrides));
    }

    private function ajaxGet(string $url)
    {
        return $this->get($url, ['X-Requested-With' => 'XMLHttpRequest']);
    }

    /** @test */
    public function admin_datatable_filters_by_strategies()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $this->makePlay('Red Zone Admin Play', ['strategies' => 'red zone']);
        $this->makePlay('Regular Admin Play', ['strategies' => 'regular']);

        $response = $this->actingAs($admin)->ajaxGet(
            route('play.index') . '?strategies=red%20zone&draw=1&start=0&length=10'
        );

        $names = collect($response->json('data'))->pluck('play_name');
        $this->assertTrue($names->contains('Red Zone Admin Play'));
        $this->assertFalse($names->contains('Regular Admin Play'));
    }

    /** @test */
    public function admin_datatable_filters_by_preferred_down()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $this->makePlay('Third Down Admin Play', ['preferred_down' => '3']);
        $this->makePlay('First Down Admin Play', ['preferred_down' => '1']);

        $response = $this->actingAs($admin)->ajaxGet(
            route('play.index') . '?preferred_down=3&draw=1&start=0&length=10'
        );

        $names = collect($response->json('data'))->pluck('play_name');
        $this->assertTrue($names->contains('Third Down Admin Play'));
        $this->assertFalse($names->contains('First Down Admin Play'));
    }

    /** @test */
    public function admin_datatable_filters_by_yardage_gain_and_play_target()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $this->makePlay('Long Defensive Play', ['min_expected_yard' => 'long', 'possession' => 'defensive']);
        $this->makePlay('Short Offensive Play', ['min_expected_yard' => 'short', 'possession' => 'offensive']);

        $response = $this->actingAs($admin)->ajaxGet(
            route('play.index') . '?yardage_gain=long&play_target=defensive&draw=1&start=0&length=10'
        );

        $names = collect($response->json('data'))->pluck('play_name');
        $this->assertTrue($names->contains('Long Defensive Play'));
        $this->assertFalse($names->contains('Short Offensive Play'));
    }

    /** @test */
    public function global_ids_endpoint_for_select_all_also_respects_the_filters()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $match = $this->makePlay('Matching Global Ids Play', ['strategies' => 'aggressive']);
        $nonMatch = $this->makePlay('Non Matching Global Ids Play', ['strategies' => 'regular']);

        $response = $this->actingAs($admin)->getJson(route('play.global-ids', ['strategies' => 'aggressive']));

        $ids = $response->json('ids');
        $this->assertContains($match->id, $ids);
        $this->assertNotContains($nonMatch->id, $ids);
    }
}
