<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\Player;
use App\Models\Team;
use App\Models\TeamPlayer;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Schema;

class PlayerControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!Schema::hasTable('players') || !Schema::hasTable('player_positions')) {
            $this->markTestSkipped('Backend schema issue: players or player_positions table not found');
        }

        $this->user = User::factory()->create([
            'role' => 'head_coach',
            'status' => 'approved'
        ]);
    }

    protected function createPlayer($name)
    {
        $player = new Player();
        $player->name = $name;
        $player->user_id = $this->user->id;
        $player->number = rand(1, 99);
        $player->position = 'QB';
        $player->size = 70;
        $player->speed = 80;
        $player->strength = 80;
        $player->weight = 200;
        $player->height = 180;
        $player->save();
        return $player;
    }

    protected function auth()
    {
        Sanctum::actingAs($this->user);
        $this->actingAs($this->user, 'api');
    }

    public function test_can_add_player()
    {
        $this->auth();

        $response = $this->postJson('/api/add-player', [
            'type' => 'player',
            'name' => 'John Doe',
            'number' => 12,
            'position' => 'QB',
            'size' => 70,
            'weight' => 200,
            'height' => 180,
            'dob' => '1990-01-01',
            'ofp' => 85,
            'strength' => 90
        ]);

        if ($response->status() !== 200) {
            $response->dump();
        }

        $response->assertStatus(200);
        $this->assertDatabaseHas('players', ['name' => 'John Doe', 'number' => 12]);
    }

    public function test_can_add_open_player()
    {
        $this->auth();

        $response = $this->postJson('/api/add-open-player', [
            'type' => 'player',
            'name' => 'Jane Doe',
            'number' => 15,
            'position' => 'WR',
            'size' => 72,
            'weight' => 190,
            'height' => 185,
            'dob' => '1992-02-02',
            'ofp' => 88,
            'strength' => 85
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('players', ['name' => 'Jane Doe', 'number' => 15]);
    }

    public function test_can_list_players()
    {
        $this->auth();

        $this->createPlayer('Test List Player');

        $response = $this->getJson('/api/player-list');

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'message', 'data']);
    }

    public function test_can_update_player_profile()
    {
        $this->auth();

        $player = $this->createPlayer('Old Profile Name');

        $response = $this->postJson('/api/update-player/' . $player->id, [
            'type' => 'basic', // Not team_player
            'name' => 'Updated Profile Name',
            'number' => 99,
            'position' => 'TE',
            'size' => 75,
            'speed' => 85,
            'strength' => 80,
            'weight' => 220,
            'height' => 195,
            'dob' => '1988-08-08',
            'ofp' => 90
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('players', ['id' => $player->id, 'name' => 'Updated Profile Name']);
    }

    public function test_can_update_team_player_profile()
    {
        if (!Schema::hasTable('teams')) {
            $this->markTestSkipped('Backend schema issue: teams table not found');
        }

        $this->auth();

        $team = new Team();
        $team->name = 'Test Update Team';
        $team->save();

        $player = $this->createPlayer('Update Team Player');

        $teamPlayer = new TeamPlayer();
        $teamPlayer->team_id = $team->id;
        $teamPlayer->player_id = $player->id;
        $teamPlayer->name = 'Update Team Player';
        $teamPlayer->save();

        $response = $this->postJson('/api/update-player/' . $team->id, [
            'type' => 'team_player',
            'player_id' => $player->id,
            'name' => 'Updated Team Player',
            'number' => 10,
            'position' => 'LB',
            'size' => 80,
            'speed' => 75,
            'strength' => 85,
            'weight' => 240,
            'height' => 180,
            'dob' => '1991-01-01',
            'ofp' => 70
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('team_players', ['player_id' => $player->id, 'name' => 'Updated Team Player']);
    }

    public function test_can_update_ofp()
    {
        if (!Schema::hasTable('teams')) {
            $this->markTestSkipped('Backend schema issue: teams table not found');
        }

        $this->auth();

        $team = new Team();
        $team->name = 'Test OFP Team';
        $team->save();

        $player = $this->createPlayer('OFP Player');

        $teamPlayer = new TeamPlayer();
        $teamPlayer->team_id = $team->id;
        $teamPlayer->player_id = $player->id;
        $teamPlayer->rpp = 50;
        $teamPlayer->save();

        $response = $this->putJson('/api/team-players/' . $teamPlayer->id . '/ofp', [
            'rpp' => 80
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('team_players', ['id' => $teamPlayer->id, 'rpp' => 80]);
    }

    public function test_can_view_player()
    {
        $this->auth();

        $player = $this->createPlayer('View Setup Player');

        $response = $this->getJson('/api/view-player/' . $player->id);

        $response->assertStatus(200);
    }
}
