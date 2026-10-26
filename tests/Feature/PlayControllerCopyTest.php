<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\Play;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlayControllerCopyTest extends TestCase
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
        $sportId = DB::table('sports')->insertGetId(['title' => 'Copy Test Sport']);
        $league = new League();
        $league->user_id = $owner->id;
        $league->sport_id = $sportId;
        $league->league_rule_id = DB::table('league_rules')->value('id') ?? 1;
        $league->title = 'Copy Test League';
        $league->number_of_team = 2;
        $league->save();
        return $league;
    }

    /** @test */
    public function dropping_a_league_play_onto_global_creates_a_global_copy_and_leaves_the_original_untouched()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $league = $this->makeLeague($admin);
        $original = $this->makePlay('League Source Play', [
            'is_global' => false, 'league_id' => $league->id, 'is_starter_play' => false,
        ]);

        $response = $this->actingAs($admin)->postJson(route('play.copy', $original->id), [
            'target' => 'global',
        ]);

        $response->assertOk();
        $copyId = $response->json('id');
        $this->assertNotEquals($original->id, $copyId);

        $copy = Play::findOrFail($copyId);
        $this->assertTrue((bool) $copy->is_global);
        $this->assertNull($copy->league_id);
        $this->assertSame('League Source Play', $copy->play_name);
        $this->assertFalse((bool) $copy->is_starter_play);

        $original->refresh();
        $this->assertFalse((bool) $original->is_global);
        $this->assertSame($league->id, $original->league_id);
    }

    /** @test */
    public function dropping_a_global_play_onto_league_creates_a_league_copy_for_the_chosen_league()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $league = $this->makeLeague($admin);
        $original = $this->makePlay('Global Source Play', ['is_starter_play' => true]);

        $response = $this->actingAs($admin)->postJson(route('play.copy', $original->id), [
            'target' => 'league',
            'league_id' => $league->id,
        ]);

        $response->assertOk();
        $copy = Play::findOrFail($response->json('id'));
        $this->assertFalse((bool) $copy->is_global);
        $this->assertSame($league->id, $copy->league_id);
        $this->assertFalse((bool) $copy->is_starter_play);

        $original->refresh();
        $this->assertTrue((bool) $original->is_global);
    }

    /** @test */
    public function copying_to_league_without_a_league_id_is_rejected()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $original = $this->makePlay('No League Id Play');

        $response = $this->actingAs($admin)->postJson(route('play.copy', $original->id), [
            'target' => 'league',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function copying_to_a_nonexistent_league_is_rejected()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $original = $this->makePlay('Bad League Id Play');

        $response = $this->actingAs($admin)->postJson(route('play.copy', $original->id), [
            'target' => 'league',
            'league_id' => 999999,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function copying_to_global_is_blocked_when_a_global_play_with_the_same_name_already_exists()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $league = $this->makeLeague($admin);
        $this->makePlay('Shared Name Play');
        $source = $this->makePlay('Shared Name Play', ['is_global' => false, 'league_id' => $league->id]);

        $countBefore = Play::where('is_global', true)->count();

        $response = $this->actingAs($admin)->postJson(route('play.copy', $source->id), [
            'target' => 'global',
        ]);

        $response->assertStatus(409);
        $this->assertSame($countBefore, Play::where('is_global', true)->count());
    }

    /** @test */
    public function copying_to_a_league_is_blocked_when_that_league_already_has_a_play_with_the_same_name()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $league = $this->makeLeague($admin);
        $this->makePlay('League Shared Name Play', ['is_global' => false, 'league_id' => $league->id]);
        $source = $this->makePlay('League Shared Name Play');

        $countBefore = Play::where('league_id', $league->id)->count();

        $response = $this->actingAs($admin)->postJson(route('play.copy', $source->id), [
            'target' => 'league',
            'league_id' => $league->id,
        ]);

        $response->assertStatus(409);
        $this->assertSame($countBefore, Play::where('league_id', $league->id)->count());
    }

    /** @test */
    public function copying_to_a_different_league_is_allowed_even_if_the_name_matches_a_play_in_another_league()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $leagueA = $this->makeLeague($admin);
        $leagueB = $this->makeLeague($admin);
        $this->makePlay('Cross League Name Play', ['is_global' => false, 'league_id' => $leagueA->id]);
        $source = $this->makePlay('Cross League Name Play');

        $response = $this->actingAs($admin)->postJson(route('play.copy', $source->id), [
            'target' => 'league',
            'league_id' => $leagueB->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('plays', [
            'id' => $response->json('id'),
            'league_id' => $leagueB->id,
        ]);
    }
}
