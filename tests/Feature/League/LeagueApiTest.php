<?php

namespace Tests\Feature\League;

use Tests\TestCase;
use App\Models\User;
use App\Models\League;
use App\Models\LeagueRule;
use App\Models\Sport;
use App\Models\LeagueTeam;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class LeagueApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        LeagueTeam::truncate();
        League::truncate();
        User::truncate();
        LeagueRule::truncate();
        Sport::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    protected function createHeadCoachUser(string $email = 'coach@test.com'): User
    {
        return User::create([
            'name' => 'Coach',
            'email' => $email,
            'password' => Hash::make('12345678'),
            'role' => 'head_coach',
            'status' => 'approved',
        ]);
    }

    /** @test */
    public function user_can_create_league()
    {
        $user = $this->createHeadCoachUser();

        $sport = new Sport();
        $sport->title = 'Football';
        $sport->save();

        $rule = new LeagueRule();
        $rule->title = 'Standard';
        $rule->save();

        Sanctum::actingAs($user);
        $this->actingAs($user, 'api');
        $response = $this->postJson('/api/leaque-create', [
            'sport_id' => $sport->id,
            'league_rule_id' => $rule->id,
            'title' => 'Test League',
            'practice_number_players' => 7,

            'number_of_team' => 2,
            'team_name' => [
                ['name' => 'Team A', 'is_practice' => 1],
                ['name' => 'Team B', 'is_practice' => 1],
            ]
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'League Created SuccessFully']);

        $this->assertDatabaseHas('leagues', [
            'title' => 'Test League'
        ]);
    }

    /** @test */
    public function user_can_get_league_list()
    {
        $user = $this->createHeadCoachUser();

        $sport = new Sport();
        $sport->title = 'Football';
        $sport->save();

        $rule = new LeagueRule();
        $rule->title = 'Standard';
        $rule->save();

        $league = new League();
        $league->user_id = $user->id;
        $league->sport_id = $sport->id;
        $league->league_rule_id = $rule->id;
        $league->title = 'League 1';
        $league->save();

        Sanctum::actingAs($user);
        $this->actingAs($user, 'api');
        $response = $this->getJson('/api/leaque?sport_id=' . $sport->id);

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'message', 'data']);
    }

    /** @test */
    public function user_can_view_league_details()
    {
        $user = $this->createHeadCoachUser();

        $sport = new Sport();
        $sport->title = 'Football';
        $sport->save();

        $rule = new LeagueRule();
        $rule->title = 'Standard';
        $rule->save();

        $league = new League();
        $league->user_id = $user->id;
        $league->sport_id = $sport->id;
        $league->league_rule_id = $rule->id;
        $league->title = 'League View';
        $league->save();

        Sanctum::actingAs($user);
        $this->actingAs($user, 'api');
        $response = $this->getJson('/api/leaque-view/' . $league->id);

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'league', 'pointsTable']);
    }

    /** @test */
    public function user_can_update_league()
    {
        $user = $this->createHeadCoachUser();

        $sport = new Sport();
        $sport->title = 'Football';
        $sport->save();

        $rule = new LeagueRule();
        $rule->title = 'Standard';
        $rule->save();

        $league = new League();
        $league->user_id = $user->id;
        $league->sport_id = $sport->id;
        $league->league_rule_id = $rule->id;
        $league->title = 'Old League';
        $league->save();

        Sanctum::actingAs($user);
        $this->actingAs($user, 'api');
        $response = $this->postJson('/api/leaque-update/' . $league->id, [
            'id' => $league->id,
            'sport_id' => $sport->id,
            'league_rule_id' => $rule->id,
            'title' => 'Updated League',
            'practice_number_players' => 7,
            'team_name' => [
                ['name' => 'Updated Team']
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('leagues', [
            'id' => $league->id,
            'title' => 'Updated League'
        ]);
    }

    /** @test */
    public function user_can_update_number_of_players()
    {
        $user = $this->createHeadCoachUser();

        $sport = new Sport();
        $sport->title = 'Football';
        $sport->save();

        $rule = new LeagueRule();
        $rule->title = 'Standard';
        $rule->save();

        $league = new League();
        $league->user_id = $user->id;
        $league->sport_id = $sport->id;
        $league->league_rule_id = $rule->id;
        $league->title = 'League Players';
        $league->save();

        Sanctum::actingAs($user);
        $this->actingAs($user, 'api');
        $response = $this->postJson('/api/update-leagueplayers/' . $league->id, [
            'number_of_players' => 7
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    /** @test */
    public function user_can_update_team_points()
    {
        $user = $this->createHeadCoachUser();

        $team = new LeagueTeam();
        $team->league_id = 1;
        $team->team_name = 'Team A';
        $team->save();

        Sanctum::actingAs($user);
        $this->actingAs($user, 'api');
        $response = $this->postJson('/api/leaque-update-points/1', [
            'team_id' => $team->id,
            'won' => 1,
            'lost' => 0,
            'drawn' => 0,
            'points' => 2
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Team Points Update ']);
    }

    /** @test */
    public function user_can_get_league_rules()
    {
        $user = $this->createHeadCoachUser();

        $rule = new LeagueRule();
        $rule->title = 'Rule 1';
        $rule->save();

        Sanctum::actingAs($user);
        $this->actingAs($user, 'api');
        $response = $this->getJson('/api/leaque-rule');

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'message', 'data']);
    }
}