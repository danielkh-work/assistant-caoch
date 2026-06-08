<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\MapsHmarkPlayImage;
use App\Http\Controllers\Controller;
use App\Models\ConfigureDefensivePlay;
use App\Models\DefensivePlay;
use App\Models\Play;
use App\Services\PlayRppScoreCalculator;
use App\Support\ConfiguredPlaySort;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ConfiguredPlayListController extends Controller
{
    use MapsHmarkPlayImage;

    private const EXPECTED_YARD_VALUES = ['short', 'medium', 'long', 'open_down'];

    public function __construct(
        private readonly ConfiguredPlaySort $playSort,
        private readonly PlayRppScoreCalculator $rppCalculator,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $this->validateListRequest($request);
        $sorts = $this->playSort->parseSort($validated['sort'] ?? null);
        $isOffensive = $validated['possession'] === 'offensive';

        if ($isOffensive) {
            $hMarkPosition = $this->resolveHMarkPosition($request);

            $query = $this->configuredOffensiveQuery(
                (int) $validated['league_id'],
                (int) $validated['matchId']
            );

            $this->applyFilters($query, $validated, 'play_name');
            $this->applySorting($query, $sorts, 'plays');

            return $this->respondWithPlays(
                $query,
                $request,
                $sorts,
                true,
                (int) $validated['matchId'],
                fn (Play $play) => $this->mapOffensivePlayImage($play, $hMarkPosition)
            );
        }

        $query = $this->configuredDefensiveQuery(
            (int) $validated['league_id'],
            (int) $validated['matchId']
        );

        $this->applyFilters($query, $validated, 'name');
        $this->applySorting($query, $sorts, 'defensive_plays');

        return $this->respondWithPlays(
            $query,
            $request,
            $sorts,
            false,
            (int) $validated['matchId'],
            fn (DefensivePlay $play) => $play->toArray()
        );
    }

    private function validateListRequest(Request $request): array
    {
        $rules = [
            'league_id' => 'required|integer|exists:leagues,id',
            'matchId' => 'required|integer',
            'possession' => 'required|string|in:offensive,defensive',
            'down' => 'nullable|integer|in:1,2,3,4',
            'expectedyard' => 'nullable|string|in:' . implode(',', self::EXPECTED_YARD_VALUES),
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string',
            'sort' => 'nullable|string|max:500',
            'h_mark_position' => $this->hMarkPositionValidationRule(),
        ];

        return $request->validate($rules);
    }

    private function configuredOffensiveQuery(int $leagueId, int $matchId): Builder
    {
        return Play::with([
            'roles',
            'playResults',
            'offensiveTargets.offensivePosition',
            'offensiveTargets.defensivePosition',
        ])
            ->whereHas('configuredLeagues', function ($q) use ($leagueId, $matchId) {
                $q->where('configure_plays.user_id', auth()->id())
                    ->where('configure_plays.league_id', $leagueId)
                    ->where('configure_plays.match_id', $matchId);
            })
            ->withCount($this->playResultCountDefinitions())
            ->withAvg('playResults as yardage_difference', 'yardage_difference');
    }

    private function configuredDefensiveQuery(int $leagueId, int $matchId): Builder
    {
        $configuredPlayIds = ConfigureDefensivePlay::query()
            ->where('user_id', auth()->id())
            ->where('league_id', $leagueId)
            ->where('game_id', $matchId)
            ->pluck('play_id');

        return DefensivePlay::with('playResults', 'strategyBlitz', 'formation', 'personals.teamPlayer.player')
            ->whereIn('id', $configuredPlayIds)
            ->withCount($this->playResultCountDefinitions())
            ->withAvg('playResults as yardage_difference', 'yardage_difference');
    }

    /**
     * @return array<string, callable>
     */
    private function playResultCountDefinitions(): array
    {
        return [
            'playResults as win_result' => function ($q) {
                $q->where('result', 'win')->where('is_practice', 0);
            },
            'playResults as win_result_rain' => function ($q) {
                $q->where('result', 'win')->where('weather', 'rain');
            },
            'playResults as win_result_snow' => function ($q) {
                $q->where('result', 'win')->where('weather', 'snow');
            },
            'playResults as loss_result' => function ($q) {
                $q->where('result', 'loss')->where('is_practice', 0);
            },
            'playResults as practice_win_result' => function ($q) {
                $q->where('result', 'win')->where('is_practice', 1);
            },
            'playResults as practice_loss_result' => function ($q) {
                $q->where('result', 'loss')->where('is_practice', 1);
            },
            'playResults as total_count' => function ($q) {
                $q->where('is_practice', 0);
            },
            'playResults as total_practice_count' => function ($q) {
                $q->where('is_practice', 1);
            },
            'playResults as total_rain' => function ($q) {
                $q->where('weather', 'rain');
            },
            'playResults as total_snow' => function ($q) {
                $q->where('weather', 'snow');
            },
        ];
    }

    private function applySorting(Builder $query, array $sorts, string $table): void
    {
        if ($sorts === []) {
            $this->playSort->applyDefaultSqlSort($query, $table);

            return;
        }

        if (!$this->playSort->requiresTotalScore($sorts)) {
            $this->playSort->applySqlSorts($query, $sorts, $table);
        }
    }

    private function applyFilters(Builder $query, array $validated, string $searchColumn): void
    {
        $down = $validated['down'] ?? null;
        if ($down !== null) {
            $query->whereRaw('FIND_IN_SET(?, preferred_down)', [$down]);
        }

        $expectedYard = $validated['expectedyard'] ?? null;
        if ($expectedYard !== null && $expectedYard !== '') {
            $query->where('min_expected_yard', $expectedYard);
        }

        $searchTerm = trim((string) ($validated['search'] ?? ''));
        if ($searchTerm !== '') {
            $needle = '%' . addcslashes($searchTerm, '%_\\') . '%';
            $query->where($searchColumn, 'like', $needle);
        }
    }

    private function respondWithPlays(
        Builder $query,
        Request $request,
        array $sorts,
        bool $isOffensive,
        int $matchId,
        callable $transform
    ): JsonResponse {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(100, (int) $request->input('per_page', 9)));

        if ($this->playSort->requiresTotalScore($sorts)) {
            return $this->paginatedCollectionResponse(
                $query->get(),
                $sorts,
                $isOffensive,
                $matchId,
                $page,
                $perPage,
                $transform
            );
        }

        return $this->paginatedResponse($query, $page, $perPage, $transform);
    }

    private function paginatedResponse(Builder $query, int $page, int $perPage, callable $transform): JsonResponse
    {
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())
            ->map(fn ($play) => $transform($play))
            ->values()
            ->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * @param Collection<int, Play|DefensivePlay> $plays
     */
    private function paginatedCollectionResponse(
        Collection $plays,
        array $sorts,
        bool $isOffensive,
        int $matchId,
        int $page,
        int $perPage,
        callable $transform
    ): JsonResponse {
        if ($isOffensive) {
            [$offenseByPosition, $defenseByPosition] = $this->rppCalculator->buildBenchPlayersByPosition($matchId, false);

            $plays = $plays->map(function ($play) use ($offenseByPosition, $defenseByPosition) {
                return $this->rppCalculator->enrichPlayWithRppScore($play, $offenseByPosition, $defenseByPosition);
            });
        } else {
            $plays = $plays->map(function ($play) {
                $play->total_score = 0;

                return $play;
            });
        }

        $sorted = $this->playSort->applyCollectionSorts($plays, $sorts);
        $total = $sorted->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $currentPage = min($page, $lastPage);
        $offset = ($currentPage - 1) * $perPage;

        $items = $sorted->slice($offset, $perPage)
            ->map(fn ($play) => $transform($play))
            ->values()
            ->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'total' => $total,
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'last_page' => $lastPage,
            ],
        ]);
    }
}
