<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\League;
use App\Models\LeagueTeam;
use App\Models\Play;
use App\Models\PlayResult;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * A Global Play's win/loss/practice/weather stats must only reflect the
 * requesting league's own games - play_results has no league column of its
 * own, so these counts are only reachable via play_results.game_id -> games.
 * league_id, and every aggregate query that forgets that join bleeds one
 * league's results into every other league that shares the same Global Play.
 */
class PlayStatsLeagueScopingTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeHeadCoach(): User
    {
        $user = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $user->syncPermissions(['play.view']);
        return $user;
    }

    protected function makeLeagueWithTeams(User $owner): array
    {
        $sportId = DB::table('sports')->insertGetId(['title' => 'Stats Scoping Test Sport']);
        $league = new League();
        $league->user_id = $owner->id;
        $league->sport_id = $sportId;
        $league->league_rule_id = DB::table('league_rules')->value('id') ?? 1;
        $league->title = 'Stats Scoping Test League ' . $league->getKey() . uniqid();
        $league->number_of_team = 2;
        $league->save();

        $team1 = LeagueTeam::forceCreate(['league_id' => $league->id, 'team_name' => 'Team1']);
        $team2 = LeagueTeam::forceCreate(['league_id' => $league->id, 'team_name' => 'Team2']);

        return [$league, $team1, $team2];
    }

    protected function makeGame(League $league, LeagueTeam $team1, LeagueTeam $team2, User $creator): Game
    {
        return Game::create([
            'league_id' => $league->id,
            'my_team_id' => $team1->id,
            'oponent_team_id' => $team2->id,
            'date' => now()->format('Y-m-d'),
            'location_type' => 'home',
            'creator_id' => $creator->id,
        ]);
    }

    protected function makeGlobalStarterPlay(): Play
    {
        return Play::create([
            'play_name' => 'Shared Global Play', 'play_type' => 1, 'zone_selection' => 1,
            'min_expected_yard' => 'medium', 'max_expected_yard' => '0',
            'pre_snap_motion' => 1, 'play_action_fake' => 1,
            'possession' => 'offensive', 'video_path' => '',
            'is_global' => true, 'league_id' => null, 'is_starter_play' => true,
            'strategies' => 'regular', 'preferred_down' => '1',
        ]);
    }

    /** @test */
    public function a_leagues_win_loss_and_practice_counts_never_include_another_leagues_results()
    {
        $hcA = $this->makeHeadCoach();
        [$leagueA, $teamA1, $teamA2] = $this->makeLeagueWithTeams($hcA);
        $gameA = $this->makeGame($leagueA, $teamA1, $teamA2, $hcA);

        $hcB = $this->makeHeadCoach();
        [$leagueB, $teamB1, $teamB2] = $this->makeLeagueWithTeams($hcB);
        $gameB = $this->makeGame($leagueB, $teamB1, $teamB2, $hcB);

        $play = $this->makeGlobalStarterPlay();

        // League A: 1 win, 1 practice win. League B: 1 loss only.
        PlayResult::create(['game_id' => $gameA->id, 'play_id' => $play->id, 'result' => 'win', 'is_practice' => 0]);
        PlayResult::create(['game_id' => $gameA->id, 'play_id' => $play->id, 'result' => 'win', 'is_practice' => 1]);
        PlayResult::create(['game_id' => $gameB->id, 'play_id' => $play->id, 'result' => 'loss', 'is_practice' => 0]);

        Sanctum::actingAs($hcA);
        $responseA = $this->getJson('/api/upload-play-list?league_id=' . $leagueA->id)->json('data');
        $playA = collect($responseA)->firstWhere('id', $play->id);

        Sanctum::actingAs($hcB);
        $responseB = $this->getJson('/api/upload-play-list?league_id=' . $leagueB->id)->json('data');
        $playB = collect($responseB)->firstWhere('id', $play->id);

        $this->assertNotNull($playA, 'League A should see the shared Global Play');
        $this->assertNotNull($playB, 'League B should see the shared Global Play');

        $this->assertSame(1, $playA['win_result']);
        $this->assertSame(0, $playA['loss_result']);
        $this->assertSame(1, $playA['practice_win_result']);
        $this->assertSame(1, $playA['total_count']);

        $this->assertSame(0, $playB['win_result']);
        $this->assertSame(1, $playB['loss_result']);
        $this->assertSame(0, $playB['practice_win_result']);
        $this->assertSame(1, $playB['total_count']);
    }

    /** @test */
    public function a_brand_new_league_sees_all_zero_stats_for_a_global_play_already_used_elsewhere()
    {
        $hcA = $this->makeHeadCoach();
        [$leagueA, $teamA1, $teamA2] = $this->makeLeagueWithTeams($hcA);
        $gameA = $this->makeGame($leagueA, $teamA1, $teamA2, $hcA);

        $play = $this->makeGlobalStarterPlay();
        PlayResult::create(['game_id' => $gameA->id, 'play_id' => $play->id, 'result' => 'win', 'is_practice' => 0]);
        PlayResult::create(['game_id' => $gameA->id, 'play_id' => $play->id, 'result' => 'win', 'is_practice' => 0]);

        $hcNew = $this->makeHeadCoach();
        [$leagueNew] = $this->makeLeagueWithTeams($hcNew);

        Sanctum::actingAs($hcNew);
        $response = $this->getJson('/api/upload-play-list?league_id=' . $leagueNew->id)->json('data');
        $playForNewLeague = collect($response)->firstWhere('id', $play->id);

        $this->assertNotNull($playForNewLeague);
        $this->assertSame(0, $playForNewLeague['win_result']);
        $this->assertSame(0, $playForNewLeague['loss_result']);
        $this->assertSame(0, $playForNewLeague['total_count']);
    }
}
