<?php

namespace Tests\Feature;

use App\Models\ConfigureDefensivePlay;
use App\Models\ConfigurePlay;
use App\Models\DefensivePlay;
use App\Models\League;
use App\Models\Play;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConfiguredPlayListControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected League $league;

    protected int $matchId = 99;

    protected function setUp(): void
    {
        parent::setUp();

        $requiredTables = ['plays', 'configure_plays', 'defensive_plays', 'configure_defensive_plays'];

        foreach ($requiredTables as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Backend schema issue: required table {$table} not found");
            }
        }

        $this->user = User::factory()->create([
            'role' => 'head_coach',
            'status' => 'approved',
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

    protected function auth(): void
    {
        Sanctum::actingAs($this->user);
        $this->actingAs($this->user, 'api');
    }

    protected function createOffensivePlay(array $overrides = []): Play
    {
        $play = new Play();
        $play->play_name = $overrides['play_name'] ?? 'Configured Play';
        $play->league_id = $this->league->id;
        $play->play_type = 1;
        $play->zone_selection = 1;
        $play->min_expected_yard = $overrides['min_expected_yard'] ?? 'short';
        $play->max_expected_yard = '10';
        $play->pre_snap_motion = 1;
        $play->play_action_fake = 1;
        $play->possession = 'offensive';
        $play->video_path = '';
        $play->preferred_down = $overrides['preferred_down'] ?? '1,2,3';
        $play->hmark_left = $overrides['hmark_left'] ?? 'uploads/public/left.png';
        $play->hmark_center = $overrides['hmark_center'] ?? 'uploads/public/center.png';
        $play->hmark_right = $overrides['hmark_right'] ?? 'uploads/public/right.png';
        $play->save();

        return $play;
    }

    protected function configureOffensivePlay(Play $play, ?int $matchId = null): void
    {
        $configure = new ConfigurePlay();
        $configure->user_id = $this->user->id;
        $configure->league_id = $this->league->id;
        $configure->match_id = $matchId ?? $this->matchId;
        $configure->play_id = $play->id;
        $configure->save();
    }

    protected function createDefensivePlay(array $overrides = []): DefensivePlay
    {
        $play = new DefensivePlay();
        $play->name = $overrides['name'] ?? 'Configured Defensive Play';
        $play->league_id = $this->league->id;
        $play->formation = '4-3';
        $play->strategy_blitz = 'Zone';
        $play->coverage_category = 'Cover 2';
        $play->min_expected_yard = $overrides['min_expected_yard'] ?? 'short';
        $play->preferred_down = $overrides['preferred_down'] ?? '1,2,3';
        $play->image = $overrides['image'] ?? 'uploads/public/defensive.png';
        $play->save();

        return $play;
    }

    protected function configureDefensivePlay(DefensivePlay $play, ?int $matchId = null): void
    {
        $configure = new ConfigureDefensivePlay();
        $configure->user_id = $this->user->id;
        $configure->league_id = $this->league->id;
        $configure->game_id = $matchId ?? $this->matchId;
        $configure->play_id = $play->id;
        $configure->save();
    }

    protected function configuredListQuery(string $possession, array $params = []): string
    {
        $query = array_merge([
            'league_id' => $this->league->id,
            'matchId' => $this->matchId,
            'possession' => $possession,
        ], $params);

        return '/api/configured-play-list?' . http_build_query($query);
    }

    protected function offensiveListQuery(array $params = []): string
    {
        return $this->configuredListQuery('offensive', $params);
    }

    protected function defensiveListQuery(array $params = []): string
    {
        return $this->configuredListQuery('defensive', $params);
    }

    public function test_requires_possession_parameter(): void
    {
        $this->auth();

        $response = $this->getJson('/api/configured-play-list?league_id=' . $this->league->id . '&matchId=' . $this->matchId);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['possession']);
    }

    public function test_offensive_list_returns_only_configured_plays_for_user_and_game(): void
    {
        $this->auth();

        $configured = $this->createOffensivePlay(['play_name' => 'Configured Only']);
        $this->configureOffensivePlay($configured);

        $unconfigured = $this->createOffensivePlay(['play_name' => 'Not Configured']);

        $response = $this->getJson($this->offensiveListQuery());

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Configured play list');

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($configured->id, $ids);
        $this->assertNotContains($unconfigured->id, $ids);
    }

    public function test_offensive_list_filters_by_down_and_expectedyard(): void
    {
        $this->auth();

        $matching = $this->createOffensivePlay([
            'play_name' => 'First Down Short',
            'preferred_down' => '1,2',
            'min_expected_yard' => 'short',
        ]);
        $this->configureOffensivePlay($matching);

        $nonMatching = $this->createOffensivePlay([
            'play_name' => 'Third Down Long',
            'preferred_down' => '3,4',
            'min_expected_yard' => 'long',
        ]);
        $this->configureOffensivePlay($nonMatching);

        $response = $this->getJson($this->offensiveListQuery([
            'down' => 1,
            'expectedyard' => 'short',
        ]));

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($matching->id, $ids);
        $this->assertNotContains($nonMatching->id, $ids);
    }

    public function test_offensive_list_maps_h_mark_position_to_image(): void
    {
        $this->auth();

        $play = $this->createOffensivePlay([
            'hmark_left' => 'uploads/public/left-only.png',
            'hmark_center' => 'uploads/public/center-only.png',
            'hmark_right' => 'uploads/public/right-only.png',
        ]);
        $this->configureOffensivePlay($play);

        $response = $this->getJson($this->offensiveListQuery([
            'h_mark_position' => 'hmark_left',
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('data.0.image', 'uploads/public/left-only.png')
            ->assertJsonPath('data.0.hmark_left', 'uploads/public/left-only.png');
    }

    public function test_offensive_list_includes_pagination_metadata(): void
    {
        $this->auth();

        for ($i = 1; $i <= 5; $i++) {
            $play = $this->createOffensivePlay(['play_name' => "Paginated Play {$i}"]);
            $this->configureOffensivePlay($play);
        }

        $response = $this->getJson($this->offensiveListQuery([
            'page' => 1,
            'per_page' => 2,
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'pagination' => ['total', 'current_page', 'per_page', 'last_page'],
            ])
            ->assertJsonPath('pagination.total', 5)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonPath('pagination.last_page', 3);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_defensive_list_returns_only_configured_plays_and_ignores_h_mark_position(): void
    {
        $this->auth();

        $configured = $this->createDefensivePlay([
            'name' => 'Configured Defense',
            'image' => 'uploads/public/defense-configured.png',
        ]);
        $this->configureDefensivePlay($configured);

        $unconfigured = $this->createDefensivePlay([
            'name' => 'Unconfigured Defense',
            'image' => 'uploads/public/defense-unconfigured.png',
        ]);

        $response = $this->getJson($this->defensiveListQuery([
            'h_mark_position' => 'hmark_left',
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Configured play list');

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($configured->id, $ids);
        $this->assertNotContains($unconfigured->id, $ids);
        $this->assertSame('uploads/public/defense-configured.png', $response->json('data.0.image'));
    }

    public function test_defensive_list_filters_by_down_and_expectedyard(): void
    {
        $this->auth();

        $matching = $this->createDefensivePlay([
            'name' => 'Match Filter',
            'preferred_down' => '1,2',
            'min_expected_yard' => 'medium',
        ]);
        $this->configureDefensivePlay($matching);

        $nonMatching = $this->createDefensivePlay([
            'name' => 'No Match Filter',
            'preferred_down' => '3,4',
            'min_expected_yard' => 'long',
        ]);
        $this->configureDefensivePlay($nonMatching);

        $response = $this->getJson($this->defensiveListQuery([
            'down' => 1,
            'expectedyard' => 'medium',
        ]));

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($matching->id, $ids);
        $this->assertNotContains($nonMatching->id, $ids);
    }
}
