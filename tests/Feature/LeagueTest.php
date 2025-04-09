<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LeagueTest extends TestCase
{
    use RefreshDatabase; // Resets the database after each test
    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(); // Create a test user
    }

    public function test_league_creation()
    {  
        // Create related models

        $payload = [
                    "sport_id"=>1,
                    "league_rule_id"=>4,
                    "number_of_team"=>4,
                    "team_name"=>["A", "B", "C"],
                    "title"=>"Test League",
                    "number_of_downs"=> 3,
                    "length_of_field"=>"110 yards",
                    "number_of_timeouts"=>1,
                    "clock_time"=> "CFL",
                    "number_of_quarters"=> 4,
                    "length_of_quarters"=> 15,
                    "stop_time_reason"=> 1,
                    "overtime_rules"=> 1,
                    "number_of_players"=> 12,
                    "flag_tbd"=> "No"
        ];
        
        $token = $this->user->createToken('auth_token')->plainTextToken;
        $response = $this->withHeaders(['Authorization' => "Bearer $token"]);
        $response = $this->postJson('/api/leaque-create',$payload);

       
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'League Created SuccessFully'
                 ]);


        $this->assertDatabaseHas('leagues', ['title' => 'Test League']);
    }

    public function test_league_list()
    {
        

        League::factory()->count(2)->create();
        
        $token = $this->user->createToken('auth_token')->plainTextToken;
        $response = $this->withHeaders(['Authorization' => "Bearer $token"]);
        $response = $this->getJson('/api/leaque');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status', 'message', 'data'
                 ]);
    }

    public function test_league_view()
    {
        
        $league = League::factory()->create();

        $token = $this->user->createToken('auth_token')->plainTextToken;
        $response = $this->withHeaders(['Authorization' => "Bearer $token"]);
        $response = $this->getJson('/api/leaque-view/'.$league->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 200,
                     'message' => 'leauqe List  '
                 ]);
    }
}
