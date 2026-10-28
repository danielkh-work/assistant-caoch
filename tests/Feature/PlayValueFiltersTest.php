<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\Play;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlayValueFiltersTest extends TestCase
{
    use DatabaseTransactions;

    private function makeLeague(User $owner): League
    {
        $sportId = DB::table('sports')->insertGetId(['title' => 'Play Filter Test Sport']);
        $league = new League();
        $league->user_id = $owner->id;
        $league->sport_id = $sportId;
        $league->league_rule_id = DB::table('league_rules')->value('id') ?? 1;
        $league->title = 'Play Filter Test League';
        $league->number_of_team = 2;
        $league->save();
        return $league;
    }

    private function makePlay(int $leagueId, string $name, array $overrides = []): Play
    {
        return Play::create(array_merge([
            'play_name' => $name, 'play_type' => 1, 'zone_selection' => 1,
            'min_expected_yard' => 'medium', 'max_expected_yard' => '0',
            'pre_snap_motion' => 1, 'play_action_fake' => 1,
            'possession' => 'offensive', 'video_path' => '',
            'league_id' => $leagueId, 'is_global' => false,
            'strategies' => 'regular', 'preferred_down' => '1',
        ], $overrides));
    }

    private function actingAsHeadCoach(): array
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $hc->syncPermissions(['play.view']);
        $league = $this->makeLeague($hc);
        Sanctum::actingAs($hc);
        return [$hc, $league];
    }

    /** @test */
    public function filtering_by_strategies_returns_only_plays_that_contain_any_selected_strategy()
    {
        [, $league] = $this->actingAsHeadCoach();
        $this->makePlay($league->id, 'Red Zone Special', ['strategies' => 'red zone,aggressive']);
        $this->makePlay($league->id, 'Plain Regular Play', ['strategies' => 'regular']);

        $response = $this->getJson('/api/upload-play-list?league_id=' . $league->id . '&strategies=red%20zone');
        $names = collect($response->json('data'))->pluck('play_name');

        $this->assertTrue($names->contains('Red Zone Special'));
        $this->assertFalse($names->contains('Plain Regular Play'));
    }

    /** @test */
    public function filtering_by_preferred_down_returns_only_plays_that_contain_any_selected_down()
    {
        [, $league] = $this->actingAsHeadCoach();
        $this->makePlay($league->id, 'Third Down Play', ['preferred_down' => '3']);
        $this->makePlay($league->id, 'First Down Play', ['preferred_down' => '1']);

        $response = $this->getJson('/api/upload-play-list?league_id=' . $league->id . '&preferred_down=3,4');
        $names = collect($response->json('data'))->pluck('play_name');

        $this->assertTrue($names->contains('Third Down Play'));
        $this->assertFalse($names->contains('First Down Play'));
    }

    /** @test */
    public function filtering_by_yardage_gain_matches_exactly()
    {
        [, $league] = $this->actingAsHeadCoach();
        $this->makePlay($league->id, 'Long Gain Play', ['min_expected_yard' => 'long']);
        $this->makePlay($league->id, 'Short Gain Play', ['min_expected_yard' => 'short']);

        $response = $this->getJson('/api/upload-play-list?league_id=' . $league->id . '&yardage_gain=long');
        $names = collect($response->json('data'))->pluck('play_name');

        $this->assertTrue($names->contains('Long Gain Play'));
        $this->assertFalse($names->contains('Short Gain Play'));
    }

    /** @test */
    public function filtering_by_yardage_gain_always_also_returns_open_down_plays()
    {
        [, $league] = $this->actingAsHeadCoach();
        $this->makePlay($league->id, 'Long Gain Play Open', ['min_expected_yard' => 'long']);
        $this->makePlay($league->id, 'Short Gain Play Open', ['min_expected_yard' => 'short']);
        $this->makePlay($league->id, 'Open Down Play', ['min_expected_yard' => 'open_down']);

        $response = $this->getJson('/api/upload-play-list?league_id=' . $league->id . '&yardage_gain=long');
        $names = collect($response->json('data'))->pluck('play_name');

        $this->assertTrue($names->contains('Long Gain Play Open'));
        $this->assertTrue($names->contains('Open Down Play'), 'An open_down play should match any yardage gain filter');
        $this->assertFalse($names->contains('Short Gain Play Open'));
    }

    /** @test */
    public function selecting_the_open_down_filter_itself_returns_every_yardage_gain()
    {
        [, $league] = $this->actingAsHeadCoach();
        $this->makePlay($league->id, 'Long Gain Play Selected Open', ['min_expected_yard' => 'long']);
        $this->makePlay($league->id, 'Short Gain Play Selected Open', ['min_expected_yard' => 'short']);
        $this->makePlay($league->id, 'Open Down Play Selected', ['min_expected_yard' => 'open_down']);

        $response = $this->getJson('/api/upload-play-list?league_id=' . $league->id . '&yardage_gain=open_down');
        $names = collect($response->json('data'))->pluck('play_name');

        $this->assertTrue($names->contains('Long Gain Play Selected Open'), 'Open Down selected should act as no yardage restriction');
        $this->assertTrue($names->contains('Short Gain Play Selected Open'));
        $this->assertTrue($names->contains('Open Down Play Selected'));
    }

    /** @test */
    public function filtering_by_play_target_matches_possession()
    {
        [$hc, $league] = $this->actingAsHeadCoach();
        $this->makePlay($league->id, 'Offensive Play', ['possession' => 'offensive']);
        $this->makePlay($league->id, 'Defensive Play', ['possession' => 'defensive']);

        $response = $this->getJson('/api/upload-play-list?league_id=' . $league->id . '&play_target=defensive');
        $names = collect($response->json('data'))->pluck('play_name');

        $this->assertTrue($names->contains('Defensive Play'));
        $this->assertFalse($names->contains('Offensive Play'));
    }

    /** @test */
    public function filtering_by_multiple_yardage_gains_returns_any_selected_value()
    {
        [, $league] = $this->actingAsHeadCoach();
        $this->makePlay($league->id, 'Long Gain Play Two', ['min_expected_yard' => 'long']);
        $this->makePlay($league->id, 'Short Gain Play Two', ['min_expected_yard' => 'short']);
        $this->makePlay($league->id, 'Medium Gain Play Two', ['min_expected_yard' => 'medium']);

        $response = $this->getJson('/api/upload-play-list?league_id=' . $league->id . '&yardage_gain=long,short');
        $names = collect($response->json('data'))->pluck('play_name');

        $this->assertTrue($names->contains('Long Gain Play Two'));
        $this->assertTrue($names->contains('Short Gain Play Two'));
        $this->assertFalse($names->contains('Medium Gain Play Two'));
    }

    /** @test */
    public function filtering_by_multiple_play_targets_returns_any_selected_value()
    {
        [, $league] = $this->actingAsHeadCoach();
        $this->makePlay($league->id, 'Offensive Play Two', ['possession' => 'offensive']);
        $this->makePlay($league->id, 'Defensive Play Two', ['possession' => 'defensive']);

        $response = $this->getJson('/api/upload-play-list?league_id=' . $league->id . '&play_target=offensive,defensive');
        $names = collect($response->json('data'))->pluck('play_name');

        $this->assertTrue($names->contains('Offensive Play Two'));
        $this->assertTrue($names->contains('Defensive Play Two'));
    }

    /** @test */
    public function combining_filters_applies_them_together_not_either_or()
    {
        [, $league] = $this->actingAsHeadCoach();
        $this->makePlay($league->id, 'Matches Both', ['strategies' => 'red zone', 'preferred_down' => '2']);
        $this->makePlay($league->id, 'Matches Only Strategy', ['strategies' => 'red zone', 'preferred_down' => '1']);
        $this->makePlay($league->id, 'Matches Only Down', ['strategies' => 'regular', 'preferred_down' => '2']);

        $response = $this->getJson('/api/upload-play-list?league_id=' . $league->id . '&strategies=red zone&preferred_down=2');
        $names = collect($response->json('data'))->pluck('play_name');

        $this->assertTrue($names->contains('Matches Both'));
        $this->assertFalse($names->contains('Matches Only Strategy'));
        $this->assertFalse($names->contains('Matches Only Down'));
    }
}
