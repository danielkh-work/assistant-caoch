<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Formation;
use App\Models\FormationData;
use App\Models\User;

class FormationControllerTest extends TestCase
{
    use RefreshDatabase; // Resets the database before each test

    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(); // Create a test user
    }

    /** @test */
    public function it_can_create_a_formation()
    {
        $data = [
            'league_id' => 1,
            'formation_name' => '4-3-3',
            'image' => base64_encode('test-image-data'),
            'players' => [
                ['name' => 'Player 1', 'y' => 10, 'x' => 20, 'type' => 'forward', 'player_number' => 9],
                ['name' => 'Player 2', 'y' => 30, 'x' => 40, 'type' => 'midfielder', 'player_number' => 7]
            ]
        ];
    
        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => "Bearer $token"]);
        $response = $this->postJson('/api/create-formation',$data);
        
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Formation save successFully']);

        $this->assertDatabaseHas('formations', ['formation' => '4-3-3']);
        $this->assertDatabaseCount('formation_data', 2);
    }

    /** @test */
    public function it_can_view_a_formation()
    {
        $formation = Formation::factory()->create();
       
        $token = $this->user->createToken('auth_token')->plainTextToken;
        $response = $this->withHeaders(['Authorization' => "Bearer $token"]);

        $response = $this->getJson('/api/formation-view/'.$formation->id);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Formation view']);
    }

    /** @test */
    public function it_can_list_all_formations()
    {
        Formation::factory()->count(3)->create();
        $token = $this->user->createToken('auth_token')->plainTextToken;
        $response = $this->withHeaders(['Authorization' => "Bearer $token"]);

        $response = $this->getJson('/api/formation-list');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Formation List']);
    }

    /** @test */
    public function it_can_update_a_formation()
    {
        $formation = Formation::factory()->create();
        $updatedData = [
            'league_id' => 2,
            'formation_name' => '4-4-2',
            'image' => base64_encode('new-test-image'),
            'players' => [
                ['name' => 'New Player', 'y' => 15, 'x' => 25, 'type' => 'defender', 'player_number' => 5]
            ]
        ];
   
        $token = $this->user->createToken('auth_token')->plainTextToken;
        $response = $this->withHeaders(['Authorization' => "Bearer $token"]);
        $response = $this->postJson('/api/update-formation/'.$formation->id, $updatedData);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Formation save successFully']);

        $this->assertDatabaseHas('formations', ['formation' => '4-4-2']);
        $this->assertDatabaseCount('formation_data', 1);
    }
}
