<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\League;
use App\Models\DefensivePlayParameter;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DefensivePlayParameterControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected League $league;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!Schema::hasTable('defensive_play_parameters')) {
            $this->markTestSkipped('Backend schema issue: required tables for defensive play parameters testing not found');
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

    public function test_can_upload_defensive_play_parameter()
    {
        $this->auth();

        $response = $this->postJson('/api/defensive-plays-parameters', [
            'formation' => '4-3',
            'blitz_packages' => 'Strong Safety Blitz',
            'description' => 'Test Parameter Description',
            'league_id' => $this->league->id
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('defensive_play_parameters', [
            'formation' => '4-3',
            'league_id' => $this->league->id
        ]);
    }

    public function test_can_get_defensive_play_parameter_list()
    {
        $this->auth();

        $parameter = new DefensivePlayParameter();
        $parameter->league_id = $this->league->id;
        $parameter->user_id = $this->user->id;
        $parameter->formation = '3-4';
        $parameter->blitz_packages = 'Corner Blitz';
        $parameter->save();

        $response = $this->getJson('/api/defensive-plays-parameters/' . $this->league->id);

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'message', 'data']);
    }

    public function test_can_edit_defensive_play_parameter()
    {
        $this->auth();

        $parameter = new DefensivePlayParameter();
        $parameter->league_id = $this->league->id;
        $parameter->user_id = $this->user->id;
        $parameter->formation = '3-4';
        $parameter->blitz_packages = 'Corner Blitz';
        $parameter->save();

        $response = $this->getJson('/api/edit-defensive-play-parameter/' . $parameter->id);

        $response->assertStatus(200);
    }

    public function test_can_update_defensive_play_parameter()
    {
        $this->auth();

        $parameter = new DefensivePlayParameter();
        $parameter->league_id = $this->league->id;
        $parameter->user_id = $this->user->id;
        $parameter->formation = '3-4';
        $parameter->blitz_packages = 'Corner Blitz';
        $parameter->save();

        $response = $this->putJson('/api/update-defensive-play-parameter/' . $parameter->id, [
            'formation' => '5-2',
            'blitz_packages' => 'Zero Blitz',
            'description' => 'Updated Description'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('defensive_play_parameters', [
            'id' => $parameter->id,
            'formation' => '5-2'
        ]);
    }

    public function test_can_delete_defensive_play_parameter()
    {
        $this->auth();

        $parameter = new DefensivePlayParameter();
        $parameter->league_id = $this->league->id;
        $parameter->user_id = $this->user->id;
        $parameter->formation = '3-4';
        $parameter->blitz_packages = 'Corner Blitz';
        $parameter->save();

        $response = $this->getJson('/api/delete-play-parameters/' . $parameter->id);

        $response->assertStatus(200);
        $this->assertNull(DefensivePlayParameter::find($parameter->id));
    }
}
