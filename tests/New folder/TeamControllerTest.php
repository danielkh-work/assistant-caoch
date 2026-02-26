<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Team;
use App\Models\TeamPlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TeamControllerTest extends TestCase
{
    use RefreshDatabase; // Resets the database after each test
    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(); // Create a test user
    }
    /** @test */
    public function it_can_create_a_team()
    {
        Storage::fake('uploads'); // Fake storage for image upload

        $image = UploadedFile::fake()->image('team.jpg'); // Fake image

        $data = [
            'team_name' => 'Test Team',
            'image' => $image,
            'playerid' => [1, 2, 3],// Fake player IDs
            'playertype' => ['offensive','defensive', 'offensive']
        ];
        $token = $this->user->createToken('auth_token')->plainTextToken;
        $response = $this->withHeaders(['Authorization' => "Bearer $token"]);
        $response = $this->postJson('/api/create-team', $data);
        
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Team save successFully'
                 ]);

        $this->assertDatabaseHas('teams', ['name' => 'Test Team']);
    }

    /** @test */
    public function it_can_list_teams()
    {
        Team::factory()->count(3)->create();
        $token = $this->user->createToken('auth_token')->plainTextToken;
        $response = $this->withHeaders(['Authorization' => "Bearer $token"]);
        $response = $this->getJson('/api/team-list');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    /** @test */
    public function it_can_view_a_team()
    {
        $team = Team::factory()->create();
        $token = $this->user->createToken('auth_token')->plainTextToken;
        $response = $this->withHeaders(['Authorization' => "Bearer $token"]);
        $response = $this->getJson('/api/view-team/'.$team->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Team view'
                 ]);
    }

    /** @test */
    public function it_can_update_a_team()
    {
        Storage::fake('uploads');

        $team = Team::factory()->create();
        $image = UploadedFile::fake()->image('new_team.jpg');

        $data = [
            'team_name' => 'Updated Team',
            'image' => $image,
            'playerid' => [4, 5, 6],
            'playertype' => ['offensive','defensive', 'offensive']
        ];
        $token = $this->user->createToken('auth_token')->plainTextToken;
        $response = $this->withHeaders(['Authorization' => "Bearer $token"]);
        
        $response = $this->postJson('/api/update-team/'.$team->id, $data);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Team update successFully'
                 ]);

        $this->assertDatabaseHas('teams', ['name' => 'Updated Team']);
    }
}
