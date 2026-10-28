<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\Play;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
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

    private function makeLeague(User $owner): League
    {
        $sportId = DB::table('sports')->insertGetId(['title' => 'Admin Filter Test Sport']);
        $league = new League();
        $league->user_id = $owner->id;
        $league->sport_id = $sportId;
        $league->league_rule_id = DB::table('league_rules')->value('id') ?? 1;
        $league->title = 'Admin Filter Test League';
        $league->number_of_team = 2;
        $league->save();
        return $league;
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
    public function admin_datatable_yardage_gain_filter_always_also_returns_open_down_plays()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $this->makePlay('Long Admin Play Open', ['min_expected_yard' => 'long']);
        $this->makePlay('Short Admin Play Open', ['min_expected_yard' => 'short']);
        $this->makePlay('Open Down Admin Play', ['min_expected_yard' => 'open_down']);

        $response = $this->actingAs($admin)->ajaxGet(
            route('play.index') . '?yardage_gain=long&draw=1&start=0&length=10'
        );

        $names = collect($response->json('data'))->pluck('play_name');
        $this->assertTrue($names->contains('Long Admin Play Open'));
        $this->assertTrue($names->contains('Open Down Admin Play'), 'An open_down play should match any yardage gain filter');
        $this->assertFalse($names->contains('Short Admin Play Open'));
    }

    /** @test */
    public function admin_datatable_selecting_the_open_down_filter_itself_returns_every_yardage_gain()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $this->makePlay('Long Admin Play Selected Open', ['min_expected_yard' => 'long']);
        $this->makePlay('Short Admin Play Selected Open', ['min_expected_yard' => 'short']);
        $this->makePlay('Open Down Admin Play Selected', ['min_expected_yard' => 'open_down']);

        $response = $this->actingAs($admin)->ajaxGet(
            route('play.index') . '?yardage_gain=open_down&draw=1&start=0&length=10'
        );

        $names = collect($response->json('data'))->pluck('play_name');
        $this->assertTrue($names->contains('Long Admin Play Selected Open'), 'Open Down selected should act as no yardage restriction');
        $this->assertTrue($names->contains('Short Admin Play Selected Open'));
        $this->assertTrue($names->contains('Open Down Admin Play Selected'));
    }

    /** @test */
    public function admin_datatable_filters_by_multiple_yardage_gains_and_play_targets()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $this->makePlay('Long Admin Play', ['min_expected_yard' => 'long', 'possession' => 'offensive']);
        $this->makePlay('Short Admin Play', ['min_expected_yard' => 'short', 'possession' => 'defensive']);
        $this->makePlay('Medium Admin Play', ['min_expected_yard' => 'medium', 'possession' => 'offensive']);

        $response = $this->actingAs($admin)->ajaxGet(
            route('play.index') . '?yardage_gain=long,short&play_target=offensive,defensive&draw=1&start=0&length=10'
        );

        $names = collect($response->json('data'))->pluck('play_name');
        $this->assertTrue($names->contains('Long Admin Play'));
        $this->assertTrue($names->contains('Short Admin Play'));
        $this->assertFalse($names->contains('Medium Admin Play'));
    }

    /** @test */
    public function admin_datatable_scope_global_returns_only_global_plays()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $league = $this->makeLeague($admin);
        $this->makePlay('Global Scoped Play');
        $this->makePlay('League Scoped Play', ['is_global' => false, 'league_id' => $league->id]);

        $response = $this->actingAs($admin)->ajaxGet(
            route('play.index') . '?scope=global&draw=1&start=0&length=10'
        );

        $names = collect($response->json('data'))->pluck('play_name');
        $this->assertTrue($names->contains('Global Scoped Play'));
        $this->assertFalse($names->contains('League Scoped Play'));
    }

    /** @test */
    public function admin_datatable_scope_league_returns_only_league_plays_and_their_league_name()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $league = $this->makeLeague($admin);
        $this->makePlay('Global Scoped Play Two');
        $this->makePlay('League Scoped Play Two', ['is_global' => false, 'league_id' => $league->id]);

        $response = $this->actingAs($admin)->ajaxGet(
            route('play.index') . '?scope=league&draw=1&start=0&length=10'
        );

        $rows = collect($response->json('data'));
        $names = $rows->pluck('play_name');
        $this->assertFalse($names->contains('Global Scoped Play Two'));
        $this->assertTrue($names->contains('League Scoped Play Two'));

        $leagueRow = $rows->firstWhere('play_name', 'League Scoped Play Two');
        $this->assertSame('Admin Filter Test League', $leagueRow['league']);
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
