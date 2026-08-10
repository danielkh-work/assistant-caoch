<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssistantCoachControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $headCoach;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headCoach = User::factory()->create([
            'role' => 'head_coach',
            'status' => 'approved',
        ]);
    }

    protected function createAssistant(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'Assistant Coach',
            'email' => 'assistant-'.uniqid().'@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'assistant_coach',
            'head_coach_id' => $this->headCoach->id,
            'status' => 'approved',
        ], $overrides));
    }

    /** @test */
    public function head_coach_can_list_assistant_coaches()
    {
        $assistant = $this->createAssistant(['email' => 'assistant-list@test.com']);

        Sanctum::actingAs($this->headCoach);

        $response = $this->getJson('/api/assistant-coaches');

        $response->assertStatus(200)
            ->assertJsonFragment(['email' => $assistant->email]);
    }

    /** @test */
    public function legacy_get_assistant_coach_route_still_works()
    {
        $assistant = $this->createAssistant(['email' => 'legacy-list@test.com']);

        Sanctum::actingAs($this->headCoach);

        $response = $this->getJson('/api/get-assistant-coach');

        $response->assertStatus(200)
            ->assertJsonFragment(['email' => $assistant->email]);
    }

    /** @test */
    public function head_coach_can_create_assistant_coach_with_approved_status()
    {
        Sanctum::actingAs($this->headCoach);

        $response = $this->postJson('/api/assistant-coaches', [
            'name' => 'New Assistant',
            'email' => 'new-assistant@test.com',
            'password' => '12345678',
            'role' => 'assistant_coach',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'email' => 'new-assistant@test.com',
                'role' => 'assistant_coach',
                'status' => 'approved',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'new-assistant@test.com',
            'head_coach_id' => $this->headCoach->id,
            'status' => 'approved',
        ]);
    }

    /** @test */
    public function legacy_add_assistant_coach_route_still_works()
    {
        Sanctum::actingAs($this->headCoach);

        $response = $this->postJson('/api/add-assistant-coach', [
            'name' => 'Legacy Assistant',
            'email' => 'legacy-create@test.com',
            'password' => '12345678',
            'role' => 'performance_coach',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'email' => 'legacy-create@test.com',
                'role' => 'performance_coach',
            ]);
    }

    /** @test */
    public function create_assistant_coach_requires_valid_role()
    {
        Sanctum::actingAs($this->headCoach);

        $response = $this->postJson('/api/assistant-coaches', [
            'name' => 'Invalid Role',
            'email' => 'invalid-role@test.com',
            'password' => '12345678',
            'role' => 'head_coach',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function head_coach_can_show_own_assistant_coach()
    {
        $assistant = $this->createAssistant(['email' => 'show@test.com']);

        Sanctum::actingAs($this->headCoach);

        $response = $this->getJson('/api/assistant-coaches/'.$assistant->id);

        $response->assertStatus(200)
            ->assertJsonFragment(['email' => 'show@test.com']);
    }

    /** @test */
    public function head_coach_cannot_access_another_head_coach_assistant()
    {
        $otherHeadCoach = User::factory()->create([
            'role' => 'head_coach',
            'status' => 'approved',
        ]);

        $foreignAssistant = User::factory()->create([
            'role' => 'assistant_coach',
            'head_coach_id' => $otherHeadCoach->id,
            'status' => 'approved',
        ]);

        Sanctum::actingAs($this->headCoach);

        $response = $this->getJson('/api/assistant-coaches/'.$foreignAssistant->id);

        $response->assertStatus(404);
    }

    /** @test */
    public function head_coach_can_update_assistant_coach()
    {
        $assistant = $this->createAssistant(['email' => 'update@test.com']);

        Sanctum::actingAs($this->headCoach);

        $response = $this->putJson('/api/assistant-coaches/'.$assistant->id, [
            'name' => 'Updated Assistant',
            'role' => 'performance_coach',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Assistant',
                'role' => 'performance_coach',
            ]);
    }

    /** @test */
    public function head_coach_can_deactivate_assistant_coach_and_revoke_tokens()
    {
        $assistant = $this->createAssistant(['email' => 'inactive@test.com']);
        $token = $assistant->createToken('auth_token');

        Sanctum::actingAs($this->headCoach);

        $response = $this->patchJson('/api/assistant-coaches/'.$assistant->id.'/status', [
            'status' => 'inactive',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'inactive']);

        $this->assertDatabaseHas('users', [
            'id' => $assistant->id,
            'status' => 'inactive',
        ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    /** @test */
    public function inactive_assistant_cannot_login()
    {
        User::factory()->create([
            'email' => 'blocked-assistant@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'assistant_coach',
            'head_coach_id' => $this->headCoach->id,
            'status' => 'inactive',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'blocked-assistant@test.com',
            'password' => '12345678',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonFragment([
                'email' => ['you account access is blocked please contact your headcoach'],
            ]);
    }

    /** @test */
    public function reactivated_assistant_can_login_again()
    {
        $assistant = $this->createAssistant(['email' => 'reactivated@test.com']);

        Sanctum::actingAs($this->headCoach);

        $this->patchJson('/api/assistant-coaches/'.$assistant->id.'/status', [
            'status' => 'inactive',
        ])->assertStatus(200);

        $this->patchJson('/api/assistant-coaches/'.$assistant->id.'/status', [
            'status' => 'approved',
        ])->assertStatus(200);

        $response = $this->postJson('/api/login', [
            'email' => 'reactivated@test.com',
            'password' => '12345678',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function head_coach_can_delete_assistant_coach()
    {
        $assistant = $this->createAssistant(['email' => 'delete@test.com']);

        Sanctum::actingAs($this->headCoach);

        $response = $this->deleteJson('/api/assistant-coaches/'.$assistant->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', [
            'id' => $assistant->id,
        ]);
    }

    /** @test */
    public function non_head_coach_cannot_manage_assistant_coaches()
    {
        $assistantCoach = User::factory()->create([
            'role' => 'assistant_coach',
            'head_coach_id' => $this->headCoach->id,
            'status' => 'approved',
        ]);

        Sanctum::actingAs($assistantCoach);

        $this->getJson('/api/assistant-coaches')->assertStatus(403);
        $this->postJson('/api/assistant-coaches', [
            'name' => 'Blocked',
            'email' => 'blocked-create@test.com',
            'password' => '12345678',
            'role' => 'assistant_coach',
        ])->assertStatus(403);
    }
}
