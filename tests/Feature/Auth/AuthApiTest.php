<?php

namespace Tests\Feature\Auth;

use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\User;
use App\Models\PendingUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class AuthApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        User::truncate();
        PendingUser::truncate();
        Mail::fake();
    }

    /** ---------------------------
     * SIGNUP & PENDING USER
     * --------------------------- */

    /** @test */
    public function signup_request_creates_pending_user()
    {
        $response = $this->postJson('/api/signup-request', [
            'name' => 'Test User',
            'email' => 'signup@test.com',
            'password' => '12345678'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('pending_user_requests', [
            'email' => 'signup@test.com'
        ]);
    }

    /** @test */
    public function approve_user_sends_verification_code()
    {
        $pending = PendingUser::create([
            'name' => 'Pending User',
            'email' => 'approve@test.com',
            'password' => Hash::make('12345678'),
        ]);

        $response = $this->getJson("/api/approve-user/{$pending->id}");
        $response->assertStatus(200);

        $pending->refresh();
        $this->assertNotNull($pending->approved_at);
        $this->assertNotNull($pending->verification_code);
    }

    /** @test */
    public function verify_code_deletes_pending_user()
    {
        PendingUser::create([
            'name' => 'Verify User',
            'email' => 'verify@test.com',
            'password' => Hash::make('12345678'),
            'verification_code' => '123456'
        ]);

        $response = $this->postJson('/api/verify-code', [
            'email' => 'verify@test.com',
            'code' => '123456'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('pending_user_requests', [
            'email' => 'verify@test.com'
        ]);
    }

    /** ---------------------------
     * LOGIN & LOGOUT
     * --------------------------- */

    /** @test */
    public function user_can_login_successfully()
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'login@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'head_coach',
            'status' => 'approved'
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@test.com',
            'password' => '12345678'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'message', 'data', 'token']);
    }

    /** @test */
    public function login_fails_for_pending_user()
    {
        $user = User::create([
            'name' => 'Pending',
            'email' => 'pending@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'head_coach',
            'status' => 'pending'
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'pending@test.com',
            'password' => '12345678'
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['message', 'errors' => ['email']]);
    }

    /** @test */
    public function user_can_logout()
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'logout@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'head_coach',
            'status' => 'approved'
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/logout');
        $response->assertStatus(200);
    }

    /** ---------------------------
     * PROFILE & PASSWORD
     * --------------------------- */

    /** @test */
    public function profile_update_requires_authentication()
    {
        $response = $this->postJson('/api/profile-update', [
            'name' => 'New Name',
            'email' => 'newemail@test.com'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_update_profile()
    {
        $user = User::create([
            'name' => 'Old Name',
            'email' => 'profile@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'head_coach',
            'status' => 'approved'
        ]);

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/profile-update', [
                             'name' => 'New Name',
                             'email' => 'newemail@test.com'
                         ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'newemail@test.com'
        ]);
    }

    /** @test */
    public function authenticated_user_can_change_password()
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'password@test.com',
            'password' => Hash::make('oldpassword'),
            'role' => 'head_coach',
            'status' => 'approved'
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/change-password', [
            'old_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(200);
        $this->assertTrue(Hash::check('newpassword123', $user->refresh()->password));
    }

    /** @test */
    public function forgot_password_sends_reset_link()
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'forgot@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'head_coach',
            'status' => 'approved'
        ]);

        $response = $this->postJson('/api/forget-password', [
            'email' => 'forgot@test.com'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    /** @test */
    public function reset_password_resets_user_password()
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'reset@test.com',
            'password' => Hash::make('oldpassword'),
            'role' => 'head_coach',
            'status' => 'approved'
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'email' => 'reset@test.com',
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(200);
        $this->assertTrue(Hash::check('newpassword123', $user->refresh()->password));
    }

    /** ---------------------------
     * SPORT & ROLE METHODS
     * --------------------------- */


    /** @test */
    public function head_coach_can_add_qb()
    {
        $headCoach = User::create([
            'name' => 'Head Coach',
            'email' => 'head@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'head_coach',
            'status' => 'approved'
        ]);

        $response = $this->actingAs($headCoach, 'sanctum')
                         ->postJson('/api/add-qb', [
                             'name' => 'QB User',
                             'email' => 'qb@test.com'
                         ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'email' => 'qb@test.com',
            'head_coach_id' => $headCoach->id
        ]);
    }

    /** @test */
    public function head_coach_can_get_qb_user()
    {
        $headCoach = User::create([
            'name' => 'Head Coach',
            'email' => 'head@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'head_coach',
            'status' => 'approved'
        ]);

        User::create([
            'name' => 'QB User',
            'email' => 'qb@test.com',
            'role' => 'qb',
            'head_coach_id' => $headCoach->id,
            'password' => Hash::make('12345678')
        ]);

        $response = $this->actingAs($headCoach, 'sanctum')
                         ->getJson('/api/get-qb-user');

        $response->assertStatus(200)
                 ->assertJsonFragment(['role' => 'qb']);
    }

    /** @test */
    public function head_coach_can_get_assistant_coaches()
    {
        $headCoach = User::create([
            'name' => 'Head Coach',
            'email' => 'head@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'head_coach',
            'status' => 'approved'
        ]);

        User::create([
            'name' => 'Assistant',
            'email' => 'assistant@test.com',
            'role' => 'assistant_coach',
            'head_coach_id' => $headCoach->id,
            'password' => Hash::make('12345678')
        ]);

        $response = $this->actingAs($headCoach, 'sanctum')
                         ->getJson('/api/get-assistant-coach');

        $response->assertStatus(200)
                 ->assertJsonFragment(['role' => 'assistant_coach']);
    }

    /** ---------------------------
     * LOGIN WITH SESSION (QB)
     * --------------------------- */

    /** @test */
 public function qb_can_login_with_code_and_session()
{
    $headCoach = User::create([
        'name' => 'Head Coach',
        'email' => 'headcoach@test.com',
        'password' => Hash::make('12345678'),
        'role' => 'head_coach',
        'status' => 'approved'
    ]);

    $qb = User::create([
        'name' => 'QB User',
        'email' => 'qb@test.com',
        'role' => 'qb',
        'head_coach_id' => $headCoach->id,
        'password' => Hash::make('12345678'),
        'code' => '1234'
    ]);

    // ✅ Send GET request with query parameters
    $response = $this->getJson('/api/qb/login-with-session?code=1234&session_id=abc123');

    $response->assertStatus(200)
             ->assertJson([
                 'message' => 'Login successful',
                 'user' => [
                     'name' => 'QB User',
                     'session_id' => 'abc123',
                     'code' => '1234',
                     'head_coach_id' => $headCoach->id
                 ]
             ]);

    $this->assertDatabaseHas('users', [
        'id' => $qb->id,
        'session_id' => 'abc123'
    ]);
}

/** @test */
public function login_fails_for_rejected_user()
{
    $user = User::create([
        'name' => 'Rejected User',
        'email' => 'rejected@test.com',
        'password' => Hash::make('12345678'),
        'role' => 'head_coach',
        'status' => 'rejected'
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'rejected@test.com',
        'password' => '12345678'
    ]);

    $response->assertStatus(422)
             ->assertJsonStructure(['message', 'errors' => ['email']]);
}

/** @test */
public function login_fails_for_wrong_credentials()
{
    $user = User::create([
        'name' => 'Test User',
        'email' => 'wrongpass@test.com',
        'password' => Hash::make('correctpassword'),
        'role' => 'head_coach',
        'status' => 'approved'
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'wrongpass@test.com',
        'password' => 'wrongpassword'
    ]);

    $response->assertStatus(422)
             ->assertJsonStructure(['message', 'errors' => ['email']]);
}

/** @test */
public function authenticated_user_can_view_profile()
{
    Role::create(['name' => 'head_coach']);

    $user = User::factory()->create();
    $user->assignRole('head_coach');

    $response = $this->actingAs($user, 'sanctum')
                     ->getJson('/api/view-profile');

    $response->assertStatus(200)
             ->assertJsonFragment([
                 'name' => 'Profile User',
                 'email' => 'profileview@test.com'
             ]);
}

/** @test */
public function head_coach_can_add_assistant_coach()
{
    $headCoach = User::create([
        'name' => 'Head Coach',
        'email' => 'headcoach@test.com',
        'password' => Hash::make('12345678'),
        'role' => 'head_coach',
        'status' => 'approved'
    ]);

    $response = $this->actingAs($headCoach, 'sanctum')
                     ->postJson('/api/add-assistant-coach', [
                         'name' => 'Assistant User',
                         'email' => 'assistant@test.com',
                         'password' => '12345678',
                         'role' => 'assistant_coach'
                     ]);

    $response->assertStatus(200)
             ->assertJsonFragment(['role' => 'assistant_coach']);

    $this->assertDatabaseHas('users', [
        'email' => 'assistant@test.com',
        'head_coach_id' => $headCoach->id,
        'role' => 'assistant_coach'
    ]);
}

/** @test */
public function authenticated_user_can_save_sport()
{
    $user = User::create([
        'name' => 'Sport User',
        'email' => 'sportuser@test.com',
        'password' => Hash::make('12345678'),
        'role' => 'head_coach',
        'status' => 'approved'
    ]);

    $response = $this->actingAs($user, 'sanctum')
                     ->postJson('/api/save-sport', [
                         'sport_id' => 5
                     ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'sport_id' => 5
    ]);
}
}