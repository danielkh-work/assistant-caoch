<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\League;
use App\Models\Formation;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FormationControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected League $league;
    protected string $base64Image;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!Schema::hasTable('formations') || !Schema::hasTable('formation_data')) {
            $this->markTestSkipped('Backend schema issue: required tables for formations testing not found');
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

        // A valid dummy 1x1 transparent base64 png
        $this->base64Image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
    }

    protected function auth()
    {
        Sanctum::actingAs($this->user);
        $this->actingAs($this->user, 'api');
    }

    public function test_can_upload_formation()
    {
        $this->auth();

        $response = $this->postJson('/api/create-formation', [
            'league_id' => $this->league->id,
            'formation_name' => 'Spread Formation',
            'image' => $this->base64Image,
            'players' => [
                [
                    'name' => 'QB',
                    'y' => 50,
                    'x' => 50,
                    'type' => 'offense',
                    'player_number' => 12
                ]
            ]
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('formations', [
            'formation' => 'Spread Formation',
            'league_id' => $this->league->id
        ]);
        
        $formation = Formation::where('formation', 'Spread Formation')->first();
        $this->assertDatabaseHas('formation_datas', [
            'formation_id' => $formation->id,
            'name' => 'QB'
        ]);
    }

    public function test_can_get_formation_list()
    {
        $this->auth();

        $formation = new Formation();
        $formation->league_id = $this->league->id;
        $formation->formation = 'I Form';
        $formation->base_64 = 'somebase';
        $formation->image = 'path/to/img.png';
        $formation->save();

        $response = $this->getJson('/api/formation-list?league_id=' . $this->league->id);

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'message', 'data']);
    }

    public function test_can_view_formation()
    {
        $this->auth();

        $formation = new Formation();
        $formation->league_id = $this->league->id;
        $formation->formation = 'I Form';
        $formation->base_64 = 'somebase';
        $formation->image = 'path/to/img.png';
        $formation->save();

        $response = $this->getJson('/api/formation-view/' . $formation->id);

        $response->assertStatus(200);
    }

    public function test_can_update_formation()
    {
        $this->auth();

        $formation = new Formation();
        $formation->league_id = $this->league->id;
        $formation->formation = 'Old Form';
        $formation->base_64 = 'somebase';
        $formation->image = 'path/to/img.png';
        $formation->save();

        $response = $this->postJson('/api/update-formation/' . $formation->id, [
            'league_id' => $this->league->id,
            'formation_name' => 'New Awesome Form',
            'image' => $this->base64Image,
            'players' => [
                [
                    'name' => 'WR',
                    'y' => 10,
                    'x' => 90,
                    'type' => 'offense',
                    'player_number' => 88
                ]
            ]
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('formations', [
            'id' => $formation->id,
            'formation' => 'New Awesome Form'
        ]);
    }

    public function test_can_delete_formation()
    {
        $this->auth();

        $formation = new Formation();
        $formation->league_id = $this->league->id;
        $formation->formation = 'Delete Form';
        $formation->base_64 = 'somebase';
        $formation->image = 'path/to/img.png';
        $formation->save();

        $response = $this->getJson('/api/delete-formation/' . $formation->id);

        $response->assertStatus(200);
        $this->assertNull(Formation::find($formation->id));
    }
}
