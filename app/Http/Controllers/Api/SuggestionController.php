<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\MapsHmarkPlayImage;
use App\Http\Controllers\Controller;
use App\Models\DefensivePlay;
use App\Models\Play;
use App\Services\PlayRppScoreCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SuggestionController extends Controller
{
    use MapsHmarkPlayImage;

    public function __construct(
        private readonly PlayRppScoreCalculator $rppCalculator,
    ) {
    }

    public function getSuggestedPlays($league, Request $request)
    {
        $request->validate([
            'h_mark_position' => $this->hMarkPositionValidationRule(),
        ]);

        $possession = $request->input('possession');
        Log::info(['possession' => $possession]);

        if ($possession === 'defensive') {
            Log::info(['possession defensive' => $possession]);

            return $this->getDefensivePlays($request);
        }

        Log::info(['possession offensive' => $possession]);

        return $this->getOffensivePlays($request);
    }

    protected function getOffensivePlays(Request $request)
    {
        $leagueId = $request->league_id;
        $matchId = $request->match_id;
        $isPractice = filter_var($request->is_practice, FILTER_VALIDATE_BOOLEAN);

        [$offenseByPosition, $defenseByPosition] = $this->rppCalculator->buildBenchPlayersByPosition(
            (int) $matchId,
            $isPractice
        );

        $query = Play::with(['roles', 'playResults', 'offensiveTargets.offensivePosition', 'offensiveTargets.defensivePosition'])
            ->whereHas('configuredLeagues', function ($q) use ($leagueId, $matchId) {
                $q->where('configure_plays.league_id', $leagueId)
                    ->where('configure_plays.match_id', $matchId);
            });

        $filters = [
            'preferred_down' => $request->input('down'),
            'possession' => $request->input('possession'),
            'strategies' => $request->input('strategy'),
            'min_expected_yard' => $request->input('expectedyard'),
        ];

        foreach ($filters as $field => $value) {
            if (!in_array($value, [null, '', 'null'], true)) {
                if ($field == 'preferred_down' || $field == 'strategies') {
                    $query->whereRaw("FIND_IN_SET(?, $field)", [$value]);
                } else {
                    $query->where($field, $value);
                }
            }
        }

        $plays = $query->inRandomOrder()->limit(6)->withCount([
            'playResults as win_result' => fn ($q) => $q->where('result', 'win')->where('is_practice', 0),
            'playResults as win_result_rain' => fn ($q) => $q->where('result', 'win')->where('weather', 'rain'),
            'playResults as win_result_snow' => fn ($q) => $q->where('result', 'win')->where('weather', 'snow'),
            'playResults as loss_result' => fn ($q) => $q->where('result', 'loss')->where('is_practice', 0),
            'playResults as practice_win_result' => fn ($q) => $q->where('result', 'win')->where('is_practice', 1),
            'playResults as practice_loss_result' => fn ($q) => $q->where('result', 'loss')->where('is_practice', 1),
            'playResults as total_count' => fn ($q) => $q->where('is_practice', 0),
            'playResults as total_practice_count' => fn ($q) => $q->where('is_practice', 1),
            'playResults as total_rain' => fn ($q) => $q->where('weather', 'rain'),
            'playResults as total_snow' => fn ($q) => $q->where('weather', 'snow'),
        ])->withAvg('playResults as yardage_difference', 'yardage_difference')->get();

        $plays = $plays->map(function ($play) use ($offenseByPosition, $defenseByPosition) {
            return $this->rppCalculator->enrichPlayWithRppScore($play, $offenseByPosition, $defenseByPosition);
        });

        $winField = $isPractice ? 'practice_win_result' : 'win_result';

        $plays = $plays->sortByDesc('total_score')->values();

        $topByScore = $plays->where('total_score', '>', 0)->sortByDesc('total_score')
            ->take(3)
            ->values();

        $winningPlays = $plays->where($winField, '>', 0)->shuffle();
        $nonWinningPlays = $plays->where($winField, '<=', 0)->shuffle();
        $topByWins = $winningPlays->take(3);

        if ($topByWins->count() < 3) {
            $needed = 3 - $topByWins->count();
            $topByWins = $topByWins->concat($nonWinningPlays->take($needed));
        }

        $topByWins = $topByWins->values();
        $hMarkPosition = $this->resolveHMarkPosition($request);

        return response()->json([
            'top_by_score' => $topByScore
                ->map(fn (Play $play) => $this->mapOffensivePlayImage($play, $hMarkPosition))
                ->values(),
            'top_by_success' => $topByWins
                ->map(fn (Play $play) => $this->mapOffensivePlayImage($play, $hMarkPosition))
                ->values(),
        ]);
    }

    public function getDefensivePlays(Request $request)
    {
        $leagueId = $request->input('league_id');

        $playerIds = \DB::table('opponent_package_player')
            ->where('opponent_team_package_id', $request->input('pkg'))
            ->pluck('player_id')
            ->toArray();

        $hasMatchingPlayers = false;

        if (!empty($playerIds)) {
            $matchingCount = DefensivePlay::whereHas('personals', function ($query) use ($playerIds) {
                $query->whereIn('teamplayer_id', $playerIds);
            })
                ->where('league_id', $leagueId)
                ->count();

            if ($matchingCount > 0) {
                $hasMatchingPlayers = true;
            }
        }

        $query = DefensivePlay::with([
            'playResults',
            'strategyBlitz',
            'formation',
            'personals.teamPlayer.player',
            'personals',
        ])->where('league_id', $leagueId);

        if ($hasMatchingPlayers) {
            $query->whereHas('personals', function ($subQuery) use ($playerIds) {
                $subQuery->whereIn('teamplayer_id', $playerIds);
            });
        } else {
            $filters = [
                'preferred_down' => $request->input('down'),
                'strategies' => $request->input('strategy'),
                'min_expected_yard' => $request->input('expectedyard'),
            ];

            foreach ($filters as $field => $value) {
                if (!in_array($value, [null, '', 'null'], true)) {
                    switch ($field) {
                        case 'preferred_down':
                        case 'strategies':
                            $query->whereRaw("FIND_IN_SET(?, $field)", [$value]);
                            break;
                        default:
                            $query->where($field, $value);
                            break;
                    }
                }
            }
        }

        $defensivePlays = $query->withCount([
            'playResults as win_result' => function ($q) {
                $q->where('result', 'win')->where('is_practice', 0);
            },
            'playResults as practice_win_result' => function ($q) {
                $q->where('result', 'win')->where('is_practice', 1);
            },
            'playResults as win_result_rain' => function ($q) {
                $q->where('result', 'win')->where('weather', 'rain');
            },
            'playResults as win_result_snow' => function ($q) {
                $q->where('result', 'win')->where('weather', 'snow');
            },
            'playResults as total_rain' => function ($q) {
                $q->where('weather', 'rain');
            },
            'playResults as total_snow' => function ($q) {
                $q->where('weather', 'snow');
            },
            'playResults as loss_result' => function ($q) {
                $q->where('result', 'loss');
            },
            'playResults as total_count' => function ($q) {
                $q->where('is_practice', 0);
            },
            'playResults as total_practice_count' => function ($q) {
                $q->where('is_practice', 1);
            },
        ])
            ->withAvg('playResults as yardage_difference', 'yardage_difference')
            ->get();

        return response()->json($defensivePlays);
    }
}
