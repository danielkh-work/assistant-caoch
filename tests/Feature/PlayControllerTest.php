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
        $leftFile = UploadedFile::fake()->image('play-left.jpg');
        $centerFile = UploadedFile::fake()->image('play-center.jpg');
        $rightFile = UploadedFile::fake()->image('play-right.jpg');

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
            'hmark_left' => $leftFile,
            'hmark_center' => $centerFile,
            'hmark_right' => $rightFile,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('plays', [
            'play_name' => 'Demo Play',
            'league_id' => $this->league->id,
        ]);

        $play = Play::where('play_name', 'Demo Play')->first();
        $this->assertNotNull($play->hmark_left);
        $this->assertNotNull($play->hmark_center);
        $this->assertNotNull($play->hmark_right);
    }

    public function test_play_list_includes_hmark_image_columns()
    {
        $this->auth();

        $play = new Play();
        $play->play_name = 'Hmark List Play';
        $play->league_id = $this->league->id;
        $play->play_type = 1;
        $play->zone_selection = 1;
        $play->min_expected_yard = '0';
        $play->max_expected_yard = '0';
        $play->pre_snap_motion = 1;
        $play->play_action_fake = 1;
        $play->possession = 'offensive';
        $play->video_path = '';
        $play->hmark_left = 'uploads/public/left.png';
        $play->hmark_center = 'uploads/public/center.png';
        $play->hmark_right = 'uploads/public/right.png';
        $play->save();

        $response = $this->getJson('/api/upload-play-list?league_id=' . $this->league->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.hmark_left', $play->hmark_left)
            ->assertJsonPath('data.0.hmark_center', $play->hmark_center)
            ->assertJsonPath('data.0.hmark_right', $play->hmark_right);
    }

    public function test_edit_play_includes_hmark_image_columns()
    {
        $this->auth();

        $play = new Play();
        $play->play_name = 'Hmark Edit Play';
        $play->league_id = $this->league->id;
        $play->play_type = 1;
        $play->zone_selection = 1;
        $play->min_expected_yard = '0';
        $play->max_expected_yard = '0';
        $play->pre_snap_motion = 1;
        $play->play_action_fake = 1;
        $play->possession = 'offensive';
        $play->video_path = '';
        $play->hmark_left = 'uploads/public/left.png';
        $play->hmark_center = 'uploads/public/center.png';
        $play->hmark_right = 'uploads/public/right.png';
        $play->save();

        $response = $this->getJson('/api/edit-play/' . $play->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.hmark_left', $play->hmark_left)
            ->assertJsonPath('data.hmark_center', $play->hmark_center)
            ->assertJsonPath('data.hmark_right', $play->hmark_right);
    }

    public function test_update_play_requires_all_hmark_images_when_missing()
    {
        $this->auth();

        $play = new Play();
        $play->play_name = 'Update Play';
        $play->league_id = $this->league->id;
        $play->play_type = 1;
        $play->zone_selection = 1;
        $play->min_expected_yard = '0';
        $play->max_expected_yard = '0';
        $play->pre_snap_motion = 1;
        $play->play_action_fake = 1;
        $play->possession = 'offensive';
        $play->video_path = '';
        $play->hmark_center = 'uploads/public/center.png';
        $play->save();

        $response = $this->postJson('/api/update-play/' . $play->id, [
            'play_name' => 'Update Play',
            'league_id' => $this->league->id,
            'play_type' => 1,
            'playType' => 'run',
            'zone_selection' => 1,
            'min_expected_yard' => '0',
            'max_expected_yard' => '0',
            'target_offensive' => 1,
            'opposing_defensive' => 1,
            'pre_snap_motion' => 1,
            'play_action_fake' => 1,
            'possession' => 'offensive',
        ]);

        $response->assertStatus(422);
    }

    public function test_can_update_play_without_reuploading_hmark_images()
    {
        $this->auth();

        $play = new Play();
        $play->play_name = 'Update Play Existing Images';
        $play->league_id = $this->league->id;
        $play->play_type = 1;
        $play->zone_selection = 1;
        $play->min_expected_yard = '0';
        $play->max_expected_yard = '0';
        $play->pre_snap_motion = 1;
        $play->play_action_fake = 1;
        $play->possession = 'offensive';
        $play->video_path = '';
        $play->hmark_left = 'uploads/public/left.png';
        $play->hmark_center = 'uploads/public/center.png';
        $play->hmark_right = 'uploads/public/right.png';
        $play->save();

        $response = $this->postJson('/api/update-play/' . $play->id, [
            'play_name' => 'Updated Play Name',
            'league_id' => $this->league->id,
            'play_type' => 1,
            'playType' => 'run',
            'zone_selection' => 1,
            'min_expected_yard' => '0',
            'max_expected_yard' => '0',
            'target_offensive' => 1,
            'opposing_defensive' => 1,
            'pre_snap_motion' => 1,
            'play_action_fake' => 1,
            'possession' => 'offensive',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('plays', [
            'id' => $play->id,
            'play_name' => 'Updated Play Name',
            'hmark_left' => 'uploads/public/left.png',
            'hmark_center' => 'uploads/public/center.png',
            'hmark_right' => 'uploads/public/right.png',
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

    public function test_play_list_supports_pagination_and_search_query_param()
    {
        $this->auth();

        foreach (['Alpha List Play', 'Beta List Play', 'Gamma List Play'] as $title) {
            $play = new Play();
            $play->play_name = $title;
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
        }

        $query = '/api/upload-play-list?'
            . 'league_id=' . $this->league->id
            . '&page=1&per_page=2&search=Beta';

        $response = $this->getJson($query);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data',
                'pagination' => ['total', 'current_page', 'per_page', 'last_page'],
            ]);

        $data = $response->json('data');
        $pagination = $response->json('pagination');

        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertSame('Beta List Play', $data[0]['play_name']);
        $this->assertSame(1, $pagination['total']);
        $this->assertSame(1, $pagination['current_page']);
        $this->assertSame(2, $pagination['per_page']);
        $this->assertSame(1, $pagination['last_page']);
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
