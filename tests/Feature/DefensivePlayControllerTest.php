<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\League;
use App\Models\DefensivePlay;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DefensivePlayControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected League $league;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!Schema::hasTable('defensive_plays')) {
            $this->markTestSkipped('Backend schema issue: required tables for defensive plays testing not found');
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

    public function test_can_upload_defensive_play()
    {
        $this->auth();

        Storage::fake('public');
        $file = UploadedFile::fake()->image('defensive_play.jpg');

        $response = $this->postJson('/api/defensive-plays', [
            'name' => 'Demo Defensive Play',
            'formation' => '4-3',
            'strategy_blitz' => 'Zone',
            'coverage_category' => 'Cover 2',
            'league_id' => $this->league->id,
            'opponent_personnel_grouping' => 'Test Grouping',
            'min_expected_yard' => '-5',
            'preferred_down' => '1,2',
            'strategies' => 'Aggressive',
            'description' => 'Test Description',
            'image' => $file
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('defensive_plays', [
            'name' => 'Demo Defensive Play',
            'league_id' => $this->league->id
        ]);
    }

    public function test_can_get_defensive_play_list()
    {
        $this->auth();

        $play = new DefensivePlay();
        $play->name = 'List Defensive Play';
        $play->league_id = $this->league->id;
        $play->formation = '3-4';
        $play->strategy_blitz = 'Man';
        $play->coverage_category = 'Cover 1';
        $play->save();

        $response = $this->getJson('/api/upload-defensive-play-list?league_id=' . $this->league->id);

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'message', 'data']);
    }

    public function test_can_edit_defensive_play()
    {
        $this->auth();

        $play = new DefensivePlay();
        $play->name = 'Edit Defensive Play';
        $play->league_id = $this->league->id;
        $play->formation = '3-4';
        $play->strategy_blitz = 'Man';
        $play->coverage_category = 'Cover 1';
        $play->save();

        $response = $this->getJson('/api/edit-defensive-play/' . $play->id);

        $response->assertStatus(200);
    }

    public function test_can_update_defensive_play()
    {
        $this->auth();

        $play = new DefensivePlay();
        $play->name = 'Old Defensive Play';
        $play->league_id = $this->league->id;
        $play->formation = '3-4';
        $play->strategy_blitz = 'Man';
        $play->coverage_category = 'Cover 1';
        $play->save();

        $response = $this->postJson('/api/update-defensive-play/' . $play->id, [
            'name' => 'Updated Defensive Play',
            'formation' => '5-2',
            'strategy_blitz' => 'Zone Blitz',
            'description' => 'Updated Description'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('defensive_plays', [
            'id' => $play->id,
            'name' => 'Updated Defensive Play'
        ]);
    }

    public function test_can_duplicate_defensive_play()
    {
        $this->auth();

        $play = new DefensivePlay();
        $play->name = 'Duplicate Me';
        $play->league_id = $this->league->id;
        $play->formation = '3-4';
        $play->strategy_blitz = 'Man';
        $play->coverage_category = 'Cover 1';
        $play->save();

        $response = $this->getJson('/api/duplicate-defensive-play/' . $play->id);

        $response->assertStatus(200);
        $this->assertDatabaseHas('defensive_plays', [
            'name' => 'Duplicate Me (Copy)',
            'league_id' => $this->league->id
        ]);
    }

    public function test_can_delete_defensive_play()
    {
        $this->auth();

        $play = new DefensivePlay();
        $play->name = 'Delete Me';
        $play->league_id = $this->league->id;
        $play->formation = '3-4';
        $play->strategy_blitz = 'Man';
        $play->coverage_category = 'Cover 1';
        $play->save();

        $response = $this->getJson('/api/delete-defensive-play/' . $play->id);

        $response->assertStatus(200);
        $this->assertNull(DefensivePlay::find($play->id));
    }
}
