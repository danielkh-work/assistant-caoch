<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\League;
use App\Models\Play;
use App\Models\PlayResult;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class PlayControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected League $league;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!Schema::hasTable('plays') || !Schema::hasTable('play_results') || !Schema::hasTable('offensive_target_strengths')) {
            $this->markTestSkipped('Backend schema issue: required tables for play testing not found');
        }

        $this->user = User::factory()->create([
            'role' => 'head_coach',
            'status' => 'approved'
        ]);

        $sportId = DB::table('sports')->insertGetId(['title' => 'Test Sport']);
        
        $this->league = new League();
        $this->league->user_id = $this->user->id;
        $this->league->sport_id = $sportId;
        $this->league->league_rule_id = \Illuminate\Support\Facades\DB::table('league_rules')->value('id') ?? 1;
        $this->league->title = 'Test League';
        $this->league->number_of_team = 2;
        $this->league->save();
    }

    protected function auth()
    {
        Sanctum::actingAs($this->user);
        $this->actingAs($this->user, 'api');
    }

    public function test_can_upload_play()
    {
        $this->auth();

        Storage::fake('public');
        $file = UploadedFile::fake()->image('play.jpg');

        $response = $this->postJson('/api/uplaod-play', [
            'play_name' => 'Demo Play',
            'playType' => 'Pass',
            'league_id' => $this->league->id,
            'play_type' => 1,
            'zone_selection' => 2,
            'min_expected_yard' => '5',
            'max_expected_yard' => '15',
            'target_offensive' => 1,
            'opposing_defensive' => 1,
            'pre_snap_motion' => 1,
            'play_action_fake' => 1,
            'preferred_down' => 1,
            'possession' => 'offensive',
            'image' => $file
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('plays', [
            'play_name' => 'Demo Play',
            'league_id' => $this->league->id
        ]);
    }

    public function test_can_get_play_list()
    {
        $this->auth();

        $play = new Play();
        $play->play_name = 'List Play';
        $play->league_id = $this->league->id;
        $play->play_type = 1;
        $play->zone_selection = 1;
        $play->min_expected_yard = '0';
        $play->max_expected_yard = '0';
        $play->pre_snap_motion = 1;
        $play->play_action_fake = 1;
        $play->possession = 'offensive';
        $play->video_path = '';
        $play->save();

        $response = $this->getJson('/api/upload-play-list?league_id=' . $this->league->id);

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'message', 'data']);
    }

    public function test_can_duplicate_play()
    {
        $this->auth();

        $play = new Play();
        $play->play_name = 'Original Play';
        $play->league_id = $this->league->id;
        $play->play_type = 1;
        $play->zone_selection = 1;
        $play->min_expected_yard = '0';
        $play->max_expected_yard = '0';
        $play->pre_snap_motion = 1;
        $play->play_action_fake = 1;
        $play->possession = 'offensive';
        $play->video_path = '';
        $play->save();

        $response = $this->getJson('/api/duplicate-play/' . $play->id);

        $response->assertStatus(200);
        $this->assertDatabaseHas('plays', [
            'play_name' => 'Original Play (Copy)',
            'league_id' => $this->league->id
        ]);
    }

    public function test_can_delete_play()
    {
        $this->auth();

        $play = new Play();
        $play->play_name = 'Play To Delete';
        $play->league_id = $this->league->id;
        $play->play_type = 1;
        $play->zone_selection = 1;
        $play->min_expected_yard = '0';
        $play->max_expected_yard = '0';
        $play->pre_snap_motion = 1;
        $play->play_action_fake = 1;
        $play->possession = 'offensive';
        $play->video_path = '';
        $play->save();

        $response = $this->getJson('/api/delete-play/' . $play->id);

        $response->assertStatus(200);
        // Play might be soft deleted or hard deleted, check via model
        $this->assertNull(Play::find($play->id));
    }

    public function test_can_add_play_result()
    {
        $this->auth();

        $play = new Play();
        $play->play_name = 'Result Play';
        $play->league_id = $this->league->id;
        $play->play_type = 1;
        $play->zone_selection = 1;
        $play->min_expected_yard = '0';
        $play->max_expected_yard = '0';
        $play->pre_snap_motion = 1;
        $play->play_action_fake = 1;
        $play->possession = 'offensive';
        $play->video_path = '';
        $play->save();

        $response = $this->postJson('/api/play-results-add', [
            'game_id' => 1,
            'play_id' => $play->id,
            'type' => '1',
            'weather' => 'normal',
            'is_practice' => 0,
            'result' => 'win',
            'yardage_difference' => 5
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('play_results', [
            'play_id' => $play->id,
            'result' => 'win'
        ]);
    }

    public function test_can_get_offensive_positions()
    {
        if (!Schema::hasTable('offensive_positions')) {
            $this->markTestSkipped('offensive_positions table not found');
        }

        $this->auth();
        $response = $this->getJson('/api/offensive-positions');
        $response->assertStatus(200);
    }
}
