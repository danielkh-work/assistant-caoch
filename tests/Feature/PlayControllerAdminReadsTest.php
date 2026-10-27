<?php

namespace Tests\Feature;

use App\Models\Play;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlayControllerAdminReadsTest extends TestCase
{
    use DatabaseTransactions;

    private function basePlayPayload(array $overrides = []): array
    {
        return array_merge([
            'play_name' => 'Admin Reads Test Play',
            'min_expected_yard' => 'medium',
            'preferred_down' => ['1'],
            'strategies' => ['regular'],
            'possession' => 'offensive',
            'description' => 'Read 1 text',
            'read_1' => 'Read 2 text',
            'read_2' => 'Read 3 text',
        ], $overrides);
    }

    /** @test */
    public function admin_can_set_all_three_reads_when_creating_a_play()
    {
        $admin = User::factory()->create(['status' => 'approved']);

        $this->actingAs($admin)->post(route('play.store'), $this->basePlayPayload());

        $play = Play::where('play_name', 'Admin Reads Test Play')->firstOrFail();
        $this->assertSame('Read 1 text', $play->description);
        $this->assertSame('Read 2 text', $play->read_1);
        $this->assertSame('Read 3 text', $play->read_2);
    }

    /** @test */
    public function admin_can_update_all_three_reads_on_an_existing_play()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $play = Play::create([
            'play_name' => 'Pre-Existing Play', 'play_type' => 1, 'zone_selection' => 1,
            'min_expected_yard' => 'short', 'max_expected_yard' => '0',
            'pre_snap_motion' => 1, 'play_action_fake' => 1,
            'possession' => 'offensive', 'video_path' => '',
            'is_global' => true,
        ]);

        $this->actingAs($admin)->put(route('play.update', $play->id), $this->basePlayPayload([
            'play_name' => 'Pre-Existing Play',
        ]));

        $play->refresh();
        $this->assertSame('Read 1 text', $play->description);
        $this->assertSame('Read 2 text', $play->read_1);
        $this->assertSame('Read 3 text', $play->read_2);
    }
}
