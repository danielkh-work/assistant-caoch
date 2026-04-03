<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\League;
use App\Models\PlayGameLog;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class LogControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected League $league;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!Schema::hasTable('play_game_logs') || !Schema::hasTable('leagues')) {
            $this->markTestSkipped('Backend schema issue: required tables for log testing not found');
        }

        $this->user = User::factory()->create([
            'role' => 'head_coach',
            'status' => 'approved'
        ]);

        $sportId = DB::table('sports')->insertGetId(['title' => 'Test Sport']);
        
        $this->league = new League();
        $this->league->user_id = $this->user->id;
        $this->league->sport_id = $sportId;
        $this->league->league_rule_id = DB::table('league_rules')->value('id') ?? 1;
        $this->league->title = 'Test League';
        $this->league->number_of_team = 2;
        $this->league->save();
    }

    protected function auth()
    {
        Sanctum::actingAs($this->user);
        $this->actingAs($this->user, 'api');
    }

    public function test_can_get_logs_index()
    {
        $this->auth();

        $log = new PlayGameLog();
        $log->league_id = $this->league->id;
        $log->game_id = 1;
        $log->my_team_id = 1;
        $log->oponent_team_id = 2;
        $log->target = 1;
        $log->save();

        $response = $this->getJson('/api/leagues/' . $this->league->id . '/matches/1/logs');

        $response->assertStatus(200);
    }
}
