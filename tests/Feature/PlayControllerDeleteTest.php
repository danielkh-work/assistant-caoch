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
    public function a_play_used_in_a_match_cannot_be_deleted()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $play = $this->makePlay('Played Play');
        $this->logPlayUsage($play);

        $response = $this->actingAs($admin)->delete(route('play.destroy', $play->id));

        $this->assertDatabaseHas('plays', ['id' => $play->id]);
        $response->assertSessionHas('error');
    }

    /** @test */
    public function bulk_delete_removes_unused_plays_and_skips_ones_with_game_history()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $unused = $this->makePlay('Bulk Unused Play');
        $used = $this->makePlay('Bulk Used Play');
        $this->logPlayUsage($used);

        $response = $this->actingAs($admin)->post(route('play.bulk-destroy'), [
            'ids' => [$unused->id, $used->id],
        ]);

        $this->assertDatabaseMissing('plays', ['id' => $unused->id]);
        $this->assertDatabaseHas('plays', ['id' => $used->id]);
        $response->assertSessionHas('error');
    }

    /** @test */
    public function bulk_delete_requires_at_least_one_id()
    {
        $admin = User::factory()->create(['status' => 'approved']);

        $response = $this->actingAs($admin)->post(route('play.bulk-destroy'), ['ids' => []]);

        $response->assertSessionHasErrors('ids');
    }
}
