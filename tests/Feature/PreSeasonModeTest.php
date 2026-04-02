<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sport;
use App\Models\League;
use App\Models\LeagueRule;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class PreSeasonModeTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'status' => 'approved',
            'role' => 'head_coach',
        ]);

        Role::firstOrCreate(['name' => 'head_coach']);
        $this->user->assignRole('head_coach');
    }

    protected function auth(): void
    {
        Sanctum::actingAs($this->user);
        $this->actingAs($this->user, 'api');
    }

    protected function createLeague(): League
    {
        $sport = new Sport();
        $sport->title = 'Football';
        $sport->save();

        $rule = new LeagueRule();
        $rule->title = 'Standard';
        $rule->save();

        $league = new League();
        $league->sport_id = $sport->id;
        $league->league_rule_id = $rule->id;
        $league->title = 'Preseason League';
        $league->number_of_team = 2;
        $league->save();

        return $league;
    }

    /** @test */
    public function can_view_team_list()
    {
        if (!Schema::hasTable('teams')) {
            $this->markTestSkipped('Backend schema issue: teams table not found');
        }

        $league = $this->createLeague();
        
        $team1 = new \App\Models\LeagueTeam();
        $team1->league_id = $league->id;
        $team1->team_name = 'Alpha';
        $team1->save();

        $team2 = new \App\Models\LeagueTeam();
        $team2->league_id = $league->id;
        $team2->team_name = 'Beta';
        $team2->save();
        $this->auth();

        $response = $this->getJson('/api/team-list');

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'message', 'data']);
    }

    /** @test */
    public function can_add_player_as_team_player()
    {
        if (!Schema::hasTable('teams') || !Schema::hasTable('player_positions')) {
            $this->markTestSkipped('Backend schema issue: teams or player_positions table not found');
        }

        $this->auth();

        $league = $this->createLeague();
        $team = new \App\Models\LeagueTeam();
        $team->league_id = $league->id;
        $team->team_name = 'Alpha';
        $team->save();

        $payload = [
            'type' => 'team',
            'team_id' => $team->id,
            'name' => 'Player One',
            'number' => 11,
            'position' => 'QB',
            'size' => 72,
            'weight' => 190,
            'height' => 180,
            'dob' => '2000-01-01',
            'ofp' => 50,
            'strength' => 75,
        ];

        $response = $this->postJson('/api/add-player', $payload);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Player Added SuccessFully ']);

        $this->assertDatabaseHas('players', ['name' => 'Player One', 'number' => 11]);
    }

    /** @test */
    public function can_add_open_player_to_league()
    {
        $this->auth();

        $league = $this->createLeague();

        $payload = [
            'type' => 'league',
            'league_id' => $league->id,
            'name' => 'Open Player',
            'number' => 2,
            'position' => 'RB',
            'size' => 70,
            'weight' => 180,
            'height' => 175,
            'dob' => '2001-01-01',
            'ofp' => 45,
            'strength' => 70,
        ];

        $response = $this->postJson('/api/add-open-player', $payload);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Player Added SuccessFully ']);

        $this->assertDatabaseHas('players', ['name' => 'Open Player', 'number' => 2]);
    }

    /** @test */
    public function can_update_league_players_count()
    {
        $this->auth();

        $league = $this->createLeague();

        $response = $this->postJson('/api/update-leagueplayers/' . $league->id, [
            'number_of_players' => 11,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    /** @test */
    public function can_upload_offensive_play()
    {
        $this->auth();

        $league = $this->createLeague();

        Storage::fake('public');
        $file = UploadedFile::fake()->image('offense.jpg');

        $response = $this->postJson('/api/uplaod-play', [
            'image' => $file,
            'play_name' => 'Offensive Test Play',
            'playType' => 'run',
            'league_id' => $league->id,
            'play_type' => 1,
            'zone_selection' => 2,
            'min_expected_yard' => '5',
            'max_expected_yard' => '15',
            'target_offensive' => 1,
            'opposing_defensive' => 1,
            'pre_snap_motion' => 0,
            'play_action_fake' => 1,
            'possession' => 'offensive',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Play Uploaded Successfully']);

        $this->assertDatabaseHas('plays', ['play_name' => 'Offensive Test Play']);
    }

    /** @test */
    public function can_view_offensive_play_list()
    {
        $this->auth();

        $league = $this->createLeague();

        $response = $this->getJson('/api/upload-play-list?league_id=' . $league->id);

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'message', 'data']);
    }

    /** @test */
    public function can_upload_defensive_play()
    {
        $this->auth();

        $league = $this->createLeague();

        Storage::fake('public');
        $file = UploadedFile::fake()->image('defense.jpg');

        $response = $this->postJson('/api/defensive-plays', [
            'image' => $file,
            'name' => 'Defensive Test Play',
            'formation' => '4-3',
            'strategy_blitz' => 'Zone',
            'coverage_category' => 'Cover 2',
            'league_id' => $league->id,
            'opponent_personnel_grouping' => '3-2-6',
            'min_expected_yard' => 3,
            'preferred_down' => 2,
            'strategies' => 'Aggressive',
            'description' => 'Test defensive play',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Play Uploaded Successfully']);

        $this->assertDatabaseHas('defensive_plays', ['name' => 'Defensive Test Play']);
    }

    /** @test */
    public function can_view_defensive_play_list()
    {
        $this->auth();

        $league = $this->createLeague();

        $response = $this->getJson('/api/upload-defensive-play-list?league_id=' . $league->id);

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'message', 'data']);
    }
}
