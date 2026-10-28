<?php

namespace Tests\Feature;

use App\Models\Play;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlayControllerDeleteTest extends TestCase
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

    private function logPlayUsage(Play $play): void
    {
        DB::table('play_game_logs')->insert([
            'play_id' => $play->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function a_play_with_no_game_history_can_be_deleted()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $play = $this->makePlay('Unused Play');

        $this->actingAs($admin)->delete(route('play.destroy', $play->id));

        $this->assertDatabaseMissing('plays', ['id' => $play->id]);
    }

    /** @test */
    public function an_ajax_delete_gets_a_json_response_instead_of_a_redirect()
    {
        // A redirect response to an XHR DELETE makes the browser's redirect-follow
        // re-issue as DELETE against the Location URL, which 405s even though the
        // play is already gone - AJAX callers must get plain JSON back instead.
        $admin = User::factory()->create(['status' => 'approved']);
        $play = $this->makePlay('Ajax Delete Play');

        $response = $this->actingAs($admin)->delete(
            route('play.destroy', $play->id),
            [],
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Play deleted successfully.']);
        $this->assertDatabaseMissing('plays', ['id' => $play->id]);
    }

    /** @test */
    public function a_play_used_in_a_match_can_still_be_deleted_and_its_history_rows_go_with_it()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $play = $this->makePlay('Played Play');
        $this->logPlayUsage($play);
        $this->logPlayUsage($play);

        $response = $this->actingAs($admin)->delete(route('play.destroy', $play->id));

        $this->assertDatabaseMissing('plays', ['id' => $play->id]);
        $this->assertDatabaseMissing('play_game_logs', ['play_id' => $play->id]);
        $response->assertSessionHas('success');
    }

    /** @test */
    public function bulk_delete_removes_every_selected_play_and_its_history_rows()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $unused = $this->makePlay('Bulk Unused Play');
        $used = $this->makePlay('Bulk Used Play');
        $this->logPlayUsage($used);

        $response = $this->actingAs($admin)->post(route('play.bulk-destroy'), [
            'ids' => [$unused->id, $used->id],
        ]);

        $this->assertDatabaseMissing('plays', ['id' => $unused->id]);
        $this->assertDatabaseMissing('plays', ['id' => $used->id]);
        $this->assertDatabaseMissing('play_game_logs', ['play_id' => $used->id]);
        $response->assertSessionHas('success');
    }

    /** @test */
    public function the_admin_datatable_tells_the_delete_button_how_much_history_a_play_has()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $used = $this->makePlay('History Count Play');
        $this->logPlayUsage($used);
        $this->logPlayUsage($used);
        $unused = $this->makePlay('No History Play');

        $response = $this->actingAs($admin)->get(
            route('play.index') . '?draw=1&start=0&length=10',
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $rows = collect($response->json('data'));
        $usedAction = $rows->firstWhere('id', $used->id)['action'];
        $unusedAction = $rows->firstWhere('id', $unused->id)['action'];

        $this->assertStringContainsString('data-history-count="2"', $usedAction);
        $this->assertStringContainsString('data-history-count="0"', $unusedAction);
    }

    /** @test */
    public function bulk_delete_requires_at_least_one_id()
    {
        $admin = User::factory()->create(['status' => 'approved']);

        $response = $this->actingAs($admin)->post(route('play.bulk-destroy'), ['ids' => []]);

        $response->assertSessionHasErrors('ids');
    }
}
