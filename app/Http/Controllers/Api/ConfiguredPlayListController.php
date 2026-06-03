<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\ConfigureDefensivePlay;
use App\Models\DefensivePlay;
use App\Models\Play;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ConfiguredPlayListController extends Controller
{
    private const HMARK_POSITIONS = ['hmark_left', 'hmark_center', 'hmark_right'];

    private const EXPECTED_YARD_VALUES = ['short', 'medium', 'long', 'open_down'];

    public function index(Request $request)
    {
        $validated = $this->validateListRequest($request);
        $isOffensive = $validated['possession'] === 'offensive';

        if ($isOffensive) {
            $hMarkPosition = $validated['h_mark_position'] ?? 'hmark_center';

            $query = $this->configuredOffensiveQuery(
                (int) $validated['league_id'],
                (int) $validated['matchId']
            );

            $this->applyFilters($query, $validated, 'play_name');

            return $this->paginatedResponse(
                $query,
                $request,
                fn (Play $play) => $this->mapOffensivePlayImage($play, $hMarkPosition)
            );
        }

        $query = $this->configuredDefensiveQuery(
            (int) $validated['league_id'],
            (int) $validated['matchId']
        );

        $this->applyFilters($query, $validated, 'name');

        return $this->paginatedResponse(
            $query,
            $request,
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
            'h_mark_position' => 'nullable|string|in:' . implode(',', self::HMARK_POSITIONS),
        ];

        return $request->validate($rules);
    }

    private function configuredOffensiveQuery(int $leagueId, int $matchId): Builder
    {
        return Play::with(['roles', 'playResults', 'offensiveTargets'])
            ->whereHas('configuredLeagues', function ($q) use ($leagueId, $matchId) {
                $q->where('configure_plays.user_id', auth()->id())
                    ->where('configure_plays.league_id', $leagueId)
                    ->where('configure_plays.match_id', $matchId);
            })
            ->withCount([
                'playResults as win_result' => function ($q) {
                    $q->where('result', 'win')->where('is_practice', 0);
                },
                'playResults as loss_result' => function ($q) {
                    $q->where('result', 'loss')->where('is_practice', 0);
                },
                'playResults as practice_win_result' => function ($q) {
                    $q->where('result', 'win')->where('is_practice', 1);
                },
                'playResults as practice_loss_result' => function ($q) {
                    $q->where('result', 'win')->where('is_practice', 1);
                },
                'playResults as total_count' => function ($q) {
                    $q->where('is_practice', 0);
                },
                'playResults as total_practice_count' => function ($q) {
                    $q->where('is_practice', 1);
                },
            ])
            ->withAvg('playResults as yardage_difference', 'yardage_difference')
            ->orderByDesc('win_result');
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
            ->withCount([
                'playResults as win_result' => function ($q) {
                    $q->where('result', 'win');
                },
                'playResults as loss_result' => function ($q) {
                    $q->where('result', 'loss');
                },
                'playResults as total_count',
            ])
            ->withAvg('playResults as yardage_difference', 'yardage_difference');
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

    private function paginatedResponse(Builder $query, Request $request, callable $transform): BaseResponse
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(100, (int) $request->input('per_page', 9)));

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())
            ->map(fn ($play) => $transform($play))
            ->values()
            ->all();

        $pagination = [
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
        ];

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'Configured play list',
            $items,
            null,
            null,
            $pagination,
        );
    }

    private function mapOffensivePlayImage(Play $play, string $hMarkPosition): array
    {
        $data = $play->toArray();
        $data['image'] = $play->{$hMarkPosition} ?? $play->hmark_center ?? null;

        return $data;
    }
}
