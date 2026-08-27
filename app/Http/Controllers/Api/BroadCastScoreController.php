<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Events\PracticeScoreUpdated;
use App\Events\ScoreUpdated;
use App\Events\MatchStarted;
use App\Events\TeamScoreUpdated;
use App\Events\YardageBroadcast;
use App\Events\PlaySuggested;
use App\Events\HeadCoachSystemSuggestion;
use App\Models\WebsocketScoreboard;
use App\Models\WebsocketPracticeScoreboard;
use App\Models\Game;
use App\Models\League;
use App\Http\Responses\BaseResponse;
use App\Services\GameClockService;
use App\Support\ActiveGameModeGuard;
use App\Support\BroadcastLeagueResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class BroadCastScoreController extends Controller
{
    private const HMARK_POSITIONS = ['hmark_left', 'hmark_center', 'hmark_right'];

    /**
     * Normalize API value or scoreboard hash radio alias to `hmark_*`.
     */
    private function normalizeHMarkPosition($hMark = null, $hashPosition = null): ?string
    {
        if (is_string($hMark) && in_array($hMark, self::HMARK_POSITIONS, true)) {
            return $hMark;
        }

        $hash = strtolower((string) ($hashPosition ?? ''));
        $map = [
            'h-left' => 'hmark_left',
            'h-center' => 'hmark_center',
            'h-right' => 'hmark_right',
        ];

        return $map[$hash] ?? null;
    }

    /**
     * Resolve `h_mark_position` from request body and/or nested suggestion context.
     */
    private function resolveBroadcastHMarkPosition(Request $request, ?array $suggestionData = null): ?string
    {
        $resolved = $this->normalizeHMarkPosition(
            $request->input('h_mark_position'),
            $request->input('hashPosition')
        );

        if ($resolved !== null) {
            return $resolved;
        }

        $suggestion = $suggestionData ?? $request->input('suggestionData');
        if (is_array($suggestion)) {
            return $this->normalizeHMarkPosition(
                $suggestion['h_mark_position'] ?? null,
                $suggestion['hashPosition'] ?? null
            );
        }

        return null;
    }

    /**
     * Last persisted H-mark for this coach group / game (practice first, then play mode).
     */
    private function scoreboardStoredHMarkPosition(?int $coachGroupId, $gameId = null): ?string
    {
        if (!$coachGroupId) {
            return null;
        }

        foreach ([WebsocketPracticeScoreboard::class, WebsocketScoreboard::class] as $model) {
            $table = (new $model)->getTable();

            if (! Schema::hasColumn($table, 'h_mark_position')) {
                continue;
            }

            $query = $model::where('user_id', $coachGroupId);

            if ($gameId !== null && $gameId !== '') {
                $query->where('game_id', $gameId);
            }

            $stored = $query->latest('updated_at')->value('h_mark_position');

            if (is_string($stored) && in_array($stored, self::HMARK_POSITIONS, true)) {
                return $stored;
            }
        }

        return null;
    }

    /**
     * Resolve H-mark for Pusher payloads: request → stored scoreboard → default center.
     */
    private function resolveHMarkForBroadcast(
        Request $request,
        ?int $coachGroupId = null,
        $gameId = null,
        ?array $suggestionData = null
    ): string {
        return $this->resolveBroadcastHMarkPosition($request, $suggestionData)
            ?? $this->scoreboardStoredHMarkPosition($coachGroupId, $gameId)
            ?? 'hmark_center';
    }

    private function resolveBroadcastLeagueId(Request $request, ?array $nested = null): ?int
    {
        $leagueId = BroadcastLeagueResolver::fromRequest($request, $nested);
        if ($leagueId !== null) {
            return $leagueId;
        }

        $user = auth()->user();
        if ($user && $user->role === 'qb' && $user->league_id) {
            return (int) $user->league_id;
        }

        return null;
    }

      public static $scores = [
        'left' => [
            'total' => 0
        ],
        'right' => [
            'total' => 0
        ]
    ];

     public static $qb = [
        'left' => [
            'total' => 0
        ],
        'right' => [
            'total' => 0
        ]
    ];

    /**
     * Scope payload for match.started / match.ended league broadcasts.
     *
     * @return array<string, mixed>
     */
    private function buildMatchStartedScope(Request $request, bool $isPracticeScoreboard): array
    {
        if ($isPracticeScoreboard) {
            return [
                'mode' => 'practice',
                'is_play_mode' => false,
                'scoreboard' => 'practice',
                'game_id' => $request->game_id,
                'session_id' => $request->session_id ?: null,
            ];
        }

        $isPlayMode = filter_var($request->input('is_play_mode', false), FILTER_VALIDATE_BOOLEAN);

        return [
            'mode' => $isPlayMode ? 'real' : 'practice',
            'is_play_mode' => $isPlayMode,
            'scoreboard' => $isPlayMode ? 'play' : 'practice',
            'game_id' => $request->game_id,
            'session_id' => $request->session_id ?: null,
        ];
    }

    private function rejectConflictingGameMode(int $coachGroupId, bool $isPractice, ?int $leagueId = null): ?\Illuminate\Http\JsonResponse
    {
        try {
            ActiveGameModeGuard::assertNoOtherModeActive($coachGroupId, $isPractice, $leagueId);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }

        return null;
    }

    private function resolveIsStartForBroadcast(string $action, Request $request, $existingRow): bool
    {
        if ($action === 'EndMatch') {
            return false;
        }

        if ($existingRow && (bool) $existingRow->is_start) {
            if ($action === 'Start') {
                return filter_var($request->isStartTime, FILTER_VALIDATE_BOOLEAN) ?: true;
            }

            return true;
        }

        if (in_array($action, ['Start', 'Stop', 'Resume'], true)) {
            return filter_var($request->isStartTime, FILTER_VALIDATE_BOOLEAN);
        }

        if ($existingRow) {
            return (bool) $existingRow->is_start;
        }

        return filter_var($request->isStartTime, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param  WebsocketScoreboard|WebsocketPracticeScoreboard|null  $existing
     * @return array{session_id: int|string|null, is_start: bool}
     */
    private function mergeScoreboardSessionFields($existing, Request $request, string $action): array
    {
        return [
            'session_id' => $request->filled('session_id')
                ? $request->session_id
                : ($existing?->session_id ?? null),
            'is_start' => $this->resolveIsStartForBroadcast($action, $request, $existing),
        ];
    }

    private function requestProvidesScoreboardField(Request $request, string $key): bool
    {
        if (! $request->has($key)) {
            return false;
        }

        $value = $request->input($key);

        return $value !== null && $value !== '';
    }

    /**
     * First non-empty request alias, or null when the client omitted the field.
     *
     * @param  list<string>  $aliases
     */
    private function incomingScoreboardValue(Request $request, string $primaryKey, array $aliases = []): mixed
    {
        if ($this->requestProvidesScoreboardField($request, $primaryKey)) {
            return $request->input($primaryKey);
        }

        foreach ($aliases as $alias) {
            if ($this->requestProvidesScoreboardField($request, $alias)) {
                return $request->input($alias);
            }
        }

        return null;
    }

    /**
     * Preserve persisted game-state columns when partial broadcasts omit fields
     * (e.g. team-position-only INFO payloads that would otherwise null session_id).
     *
     * @param  WebsocketScoreboard|WebsocketPracticeScoreboard|null  $existing
     * @return array<string, mixed>
     */
    private function mergeScoreboardPersistedFields($existing, Request $request): array
    {
        $fields = [
            'league_id' => ['request' => 'league_id', 'aliases' => []],
            'down' => ['request' => 'down', 'aliases' => []],
            'distance' => ['request' => 'distance', 'aliases' => []],
            'strategies' => ['request' => 'strategies', 'aliases' => []],
            'position_number' => ['request' => 'positionNumber', 'aliases' => ['position_number']],
            'team_position' => ['request' => 'teamPosition', 'aliases' => ['team_position']],
            'expected_yard_gain' => ['request' => 'expectedyardgain', 'aliases' => ['expected_yard_gain']],
            'pkg' => ['request' => 'pkg', 'aliases' => []],
            'possession' => ['request' => 'possession', 'aliases' => []],
            'weather' => ['request' => 'weather', 'aliases' => []],
            'coverage_category' => ['request' => 'coverageCategory', 'aliases' => ['coverage_category']],
            // Frontend sends elapsed seconds as `time`; SyncTime uses `sync_time`.
            'sync_time' => ['request' => 'sync_time', 'aliases' => ['time']],
        ];

        $merged = [];

        foreach ($fields as $column => $config) {
            $incoming = $this->incomingScoreboardValue(
                $request,
                $config['request'],
                $config['aliases'],
            );

            $merged[$column] = $incoming !== null
                ? $incoming
                : ($existing?->{$column} ?? null);
        }

        if ($merged['sync_time'] === null && $existing?->timer_remaining !== null) {
            $merged['sync_time'] = (int) $existing->timer_remaining;
        }

        return $merged;
    }

    /**
     * Distance to First Down is computed server-side, never taken from the request:
     *   - down changes to 1 -> 10
     *   - any other down change -> leave whatever was already showing
     */
    private function resolveDistanceToFirstDown(?int $newDown, ?int $existingDistance): int
    {
        if ($newDown === 1) {
            return 10;
        }

        return $existingDistance ?? 0;
    }

    /**
     * @param  WebsocketScoreboard|WebsocketPracticeScoreboard|null  $existing
     */
    private function resolvePersistedTimerRemaining($existing, Request $request, array $persistedFields): ?int
    {
        if ($request->input('action') === 'SyncTime' && $request->has('sync_time') && is_numeric($request->input('sync_time'))) {
            return (int) $request->input('sync_time');
        }

        // Reject time=0: zero means the timer hit the quarter boundary and hasn't
        // been reset yet. Storing 0 causes session restore to see diffSeconds=quarterSeconds
        // and auto-advance the quarter on the next page refresh.
        if (is_numeric($request->input('time')) && (int) $request->input('time') > 0) {
            return (int) $request->time;
        }

        if ($persistedFields['sync_time'] !== null && (int) $persistedFields['sync_time'] > 0) {
            return (int) $persistedFields['sync_time'];
        }

        return $existing?->timer_remaining !== null
            ? (int) $existing->timer_remaining
            : null;
    }

    private function clockService(): GameClockService
    {
        return app(GameClockService::class);
    }

    /**
     * @param  WebsocketScoreboard|WebsocketPracticeScoreboard|null  $existing
     */
    private function leagueForScoreboard($existing, Request $request): ?League
    {
        $leagueId = $request->input('league_id') ?? $existing?->league_id;

        return $leagueId ? League::find((int) $leagueId) : null;
    }

    /**
     * Authoritative quarter + remaining for broadcast writes.
     *
     * @param  WebsocketScoreboard|WebsocketPracticeScoreboard|null  $existing
     * @return array{quarter: int|string|null, timer_remaining: int|null, sys_time: string}
     */
    private function resolveClockFieldsForBroadcast(
        $existing,
        Request $request,
        string $action,
        bool $isRestoredStart
    ): array {
        $clock = $this->clockService();
        $league = $this->leagueForScoreboard($existing, $request);
        $quarterLength = $clock->quarterLengthSeconds($league);
        $maxQuarters = $clock->maxQuarters($league);
        $now = Carbon::now();
        $nowString = $now->toDateTimeString();
        $requestQuarter = $request->has('quarter') ? (int) $request->quarter : null;
        $existingQuarter = $existing?->quarter !== null ? (int) $existing->quarter : null;
        $clientTime = is_numeric($request->input('time')) ? (int) $request->input('time') : 0;

        if ($action === 'Start' && ! $isRestoredStart) {
            return [
                'quarter' => $requestQuarter ?: 1,
                'timer_remaining' => $clientTime > 0 ? $clientTime : $quarterLength,
                'sys_time' => $nowString,
            ];
        }

        if ($isRestoredStart) {
            return [
                'quarter' => $existingQuarter ?? ($requestQuarter ?: 1),
                'timer_remaining' => $existing?->timer_remaining !== null
                    ? (int) $existing->timer_remaining
                    : $quarterLength,
                'sys_time' => $nowString,
            ];
        }

        if ($action === 'Stop') {
            if ($clock->isPaused($existing?->action)) {
                return [
                    'quarter' => $existingQuarter ?? ($requestQuarter ?: 1),
                    'timer_remaining' => (int) ($existing?->timer_remaining ?? 0),
                    'sys_time' => $nowString,
                ];
            }

            $resolved = $clock->resolve([
                'quarter' => $existingQuarter ?? ($requestQuarter ?: 1),
                'timer_remaining' => $existing?->timer_remaining,
                'sys_time' => $existing?->sys_time,
                'action' => $existing?->action ?? 'Start',
            ], $quarterLength, $maxQuarters, $now);

            return [
                'quarter' => $resolved['quarter'],
                'timer_remaining' => $resolved['timer_remaining'],
                'sys_time' => $nowString,
            ];
        }

        if ($action === 'Resume') {
            return [
                'quarter' => $requestQuarter ?? $existingQuarter ?? 1,
                'timer_remaining' => $clientTime > 0
                    ? $clientTime
                    : (int) ($existing?->timer_remaining ?? $quarterLength),
                'sys_time' => $nowString,
            ];
        }

        if ($action === 'SyncTime') {
            $syncTime = $request->has('sync_time') && is_numeric($request->input('sync_time'))
                ? (int) $request->input('sync_time')
                : null;

            return [
                'quarter' => $requestQuarter ?? $existingQuarter ?? 1,
                'timer_remaining' => $syncTime !== null
                    ? $syncTime
                    : ($clientTime > 0
                        ? $clientTime
                        : (int) ($existing?->timer_remaining ?? $quarterLength)),
                'sys_time' => $nowString,
            ];
        }

        if (
            $existing
            && $requestQuarter !== null
            && $existingQuarter !== null
            && $requestQuarter !== $existingQuarter
        ) {
            $isAdvance = $requestQuarter > $existingQuarter;
            $isIntentional = $clientTime > 0 || $isAdvance;
            if ($isIntentional) {
                return [
                    'quarter' => $requestQuarter,
                    'timer_remaining' => $clientTime > 0 ? $clientTime : $quarterLength,
                    'sys_time' => $nowString,
                ];
            }
        }

        if ($existing && $existing->is_start && ! $clock->isPaused($existing->action)) {
            $resolved = $clock->resolve([
                'quarter' => $existingQuarter ?? 1,
                'timer_remaining' => $existing->timer_remaining,
                'sys_time' => $existing->sys_time,
                'action' => $existing->action,
            ], $quarterLength, $maxQuarters, $now);

            return [
                'quarter' => $existingQuarter ?? ($requestQuarter ?: $resolved['quarter']),
                'timer_remaining' => $resolved['timer_remaining'],
                'sys_time' => $nowString,
            ];
        }

        $persisted = $this->mergeScoreboardPersistedFields($existing, $request);

        return [
            'quarter' => $requestQuarter ?? $existingQuarter ?? 1,
            'timer_remaining' => $this->resolvePersistedTimerRemaining($existing, $request, $persisted),
            'sys_time' => $nowString,
        ];
    }

    /**
     * Advance a live scoreboard row to wall-clock time and persist when needed.
     *
     * @param  WebsocketScoreboard|WebsocketPracticeScoreboard|null  $row
     * @return WebsocketScoreboard|WebsocketPracticeScoreboard|null
     */
    private function applyLiveClockToScoreboardRow($row)
    {
        if (! $row || ! $row->is_start) {
            return $row;
        }

        $table = $row instanceof WebsocketPracticeScoreboard
            ? 'websocket_practice_scoreboards'
            : 'websocket_scoreboards';

        $clock = $this->clockService();
        $league = $row->league_id ? League::find((int) $row->league_id) : null;

        // Persist regulation end even when the clock is paused at 0:00 on the final quarter.
        if (Schema::hasColumn($table, 'regulation_ended_at') && empty($row->regulation_ended_at)) {
            $endedAt = ActiveGameModeGuard::resolveRegulationEndedAt($row, $league);
            if ($endedAt) {
                $row->regulation_ended_at = $endedAt->toDateTimeString();
            }
        }

        if ($clock->isPaused($row->action)) {
            if ($row->isDirty()) {
                $row->save();

                return $row->fresh();
            }

            return $row;
        }

        $resolved = $clock->resolveForLeague([
            'quarter' => $row->quarter,
            'timer_remaining' => $row->timer_remaining,
            'sys_time' => $row->sys_time,
            'action' => $row->action,
        ], $league);

        if ($resolved['exhausted']
            && Schema::hasColumn($table, 'regulation_ended_at')
            && empty($row->regulation_ended_at)
        ) {
            $row->regulation_ended_at = $resolved['exhausted_at'] ?? now()->toDateTimeString();
        }

        // After regulation ends, keep the parked clock snapshot stable. Polls must not
        // rewrite sys_time (that used to make abandoned matches look freshly active).
        if ($resolved['exhausted'] && ! empty($row->regulation_ended_at) && (int) $row->timer_remaining === 0) {
            if ($row->isDirty()) {
                $row->save();

                return $row->fresh();
            }

            return $row;
        }

        if (! $resolved['advanced'] && ! $row->isDirty()) {
            return $row;
        }

        if ($resolved['advanced']) {
            $row->quarter = $resolved['quarter'];
            $row->timer_remaining = $resolved['timer_remaining'];
            $row->sys_time = $resolved['sys_time'];
        }

        $row->save();

        return $row->fresh();
    }

    /**
     * Timestamps used for auto-end. Broadcast writes count as meaningful activity;
     * scoreboard polls must not call this.
     *
     * @param  WebsocketScoreboard|WebsocketPracticeScoreboard|null  $existing
     * @param  array{quarter?: mixed, timer_remaining?: mixed}  $clockFields
     * @return array{last_meaningful_activity_at?: string, regulation_ended_at?: string|null}
     */
    private function scoreboardActivityFields(
        $existing,
        string $action,
        array $clockFields,
        ?League $league,
        string $table
    ): array {
        $fields = [];

        if (Schema::hasColumn($table, 'last_meaningful_activity_at')) {
            $fields['last_meaningful_activity_at'] = now()->toDateTimeString();
        }

        if (! Schema::hasColumn($table, 'regulation_ended_at')) {
            return $fields;
        }

        if ($action === 'Start') {
            $fields['regulation_ended_at'] = null;

            return $fields;
        }

        if (! empty($existing?->regulation_ended_at)) {
            $fields['regulation_ended_at'] = $existing->regulation_ended_at;

            return $fields;
        }

        $clock = $this->clockService();
        $maxQuarters = $clock->maxQuarters($league);
        $quarter = max(1, (int) ($clockFields['quarter'] ?? $existing?->quarter ?? 1));
        $remaining = array_key_exists('timer_remaining', $clockFields) && $clockFields['timer_remaining'] !== null
            ? max(0, (int) $clockFields['timer_remaining'])
            : null;

        if ($remaining === 0 && $quarter >= $maxQuarters) {
            $fields['regulation_ended_at'] = now()->toDateTimeString();
        }

        return $fields;
    }

    /**
     * @param  WebsocketScoreboard|WebsocketPracticeScoreboard|null  $existing
     * @return array{left: int, right: int}
     */
    private function resolveScoreTotals($existing, Request $request, string $action): array
    {
        $existingLeft = (int) ($existing?->left_score ?? 0);
        $existingRight = (int) ($existing?->right_score ?? 0);

        if ($this->isRestoredScoreboardStart($existing, $action)) {
            return ['left' => $existingLeft, 'right' => $existingRight];
        }

        $isScoreChangingAction = (int) $request->input('points', 0) !== 0
            || $request->input('team') !== 'both';

        if ($existing && ! $isScoreChangingAction && $action !== 'EndMatch' && $action !== 'Start') {
            return ['left' => $existingLeft, 'right' => $existingRight];
        }

        return [
            'left' => (int) ($request->teamLeftScore ?? $existingLeft),
            'right' => (int) ($request->teamRightScore ?? $existingRight),
        ];
    }

    private function isRestoredScoreboardStart($existing, string $action): bool
    {
        return $existing && $action === 'Start' && $existing->action === 'EndMatch';
    }

    private function preserveRestoredStartFields(array $persistedFields, $existing): array
    {
        foreach ([
            'league_id',
            'down',
            'distance',
            'strategies',
            'position_number',
            'team_position',
            'expected_yard_gain',
            'pkg',
            'possession',
            'weather',
            'coverage_category',
            'sync_time',
        ] as $field) {
            if ($existing?->{$field} !== null) {
                $persistedFields[$field] = $existing->{$field};
            }
        }

        return $persistedFields;
    }

    private function completeSessionOnEndMatch(int $coachGroupId, string $action, Request $request, string $gameMode, $existingRow = null): void
    {
        if ($action !== 'EndMatch') {
            return;
        }

        $sessionId = $request->filled('session_id')
            ? (int) $request->session_id
            : ($existingRow?->session_id ? (int) $existingRow->session_id : null);

        if ($sessionId) {
            ActiveGameModeGuard::completeSession($coachGroupId, $sessionId);

            return;
        }

        ActiveGameModeGuard::completeActiveSessionsForMode(
            $coachGroupId,
            $gameMode,
            $request->league_id ? (int) $request->league_id : null,
        );
    }

     public function practiceScoreBoardBroadCast(Request $request)
    {


        $validated = $request->validate([
            'team' => 'required|in:left,right,both',
            'points' => 'required|integer',
            'action' => 'required|string',
            // No rule for `distance` - it's computed server-side (see
            // resolveDistanceToFirstDown) and can legitimately be 0, so whatever
            // the request sends for it is ignored entirely, not just unvalidated.
        ]);

        $team = $validated['team'];
        $points = $validated['points'];
        $action = $validated['action'];

        $user = auth()->user();
        $coachGroupId = $user->role === 'head_coach'
            ? $user->id
            : $user->head_coach_id;

        if ($coachGroupId === null) {
            \Log::warning('practiceScoreBoardBroadCast: missing coach group id', [
                'user_id' => $user->id,
                'role' => $user->role,
            ]);
            return response()->json(['error' => 'missing coach group id'], 400);
        }

        if ($action === 'Start') {
            $leagueId = $request->league_id ? (int) $request->league_id : null;
            $conflictResponse = $this->rejectConflictingGameMode($coachGroupId, true, $leagueId);
            if ($conflictResponse) {
                return $conflictResponse;
            }
        }

        $existingPractice = WebsocketPracticeScoreboard::where('user_id', $coachGroupId)
            ->where('game_id', $request->game_id)
            ->first();

        // Update game status and dates
        if ($action === 'Start') {
            Game::where('id', $request->game_id)->update([
                'status' => 'in_progress',
                'match_start_date' => now(),
            ]);
        }

        if ($action === 'EndMatch') {
            Game::where('id', $request->game_id)->update([
                'status' => 'ended',
                'match_end_date' => now(),
            ]);
        }

        $sessionFields = $this->mergeScoreboardSessionFields($existingPractice, $request, $action);
        $persistedFields = $this->mergeScoreboardPersistedFields($existingPractice, $request);
        $isRestoredStart = $this->isRestoredScoreboardStart($existingPractice, $action);

        if ($isRestoredStart) {
            $persistedFields = $this->preserveRestoredStartFields($persistedFields, $existingPractice);
        }

        // On Start, never carry over per-play settings from the prior session on this fixture.
        // mergeScoreboardPersistedFields treats null/'' as "not provided" and falls back to the
        // DB row, so without this override a new match inherits the previous match's down,
        // strategies, pkg, etc. — causing AC to see old settings after refresh.
        if ($action === 'Start' && ! $isRestoredStart) {
            foreach (['down', 'distance', 'strategies', 'pkg', 'expected_yard_gain', 'position_number', 'team_position', 'possession', 'coverage_category'] as $field) {
                $persistedFields[$field] = null;
            }
        }

        $persistedFields['distance'] = $this->resolveDistanceToFirstDown(
            $persistedFields['down'] !== null ? (int) $persistedFields['down'] : null,
            $existingPractice?->distance !== null ? (int) $existingPractice->distance : null,
        );

        $clockFields = $this->resolveClockFieldsForBroadcast(
            $existingPractice,
            $request,
            $action,
            $isRestoredStart
        );
        $timerRemaining = $clockFields['timer_remaining'];

        $shouldRefreshTime = ! $existingPractice
            || ((int) $existingPractice->quarter != (int) $clockFields['quarter']);

        $hMarkPosition = $isRestoredStart && $existingPractice?->h_mark_position
            ? $existingPractice->h_mark_position
            : $this->resolveHMarkForBroadcast($request, $coachGroupId, $request->game_id);

        $scoreTotals = $this->resolveScoreTotals($existingPractice, $request, $action);
        self::$scores['left']['total'] = $scoreTotals['left'];
        self::$scores['right']['total'] = $scoreTotals['right'];

        $activityFields = $this->scoreboardActivityFields(
            $existingPractice,
            $action,
            $clockFields,
            $this->leagueForScoreboard($existingPractice, $request),
            'websocket_practice_scoreboards'
        );

        $practiceValues = [
            'left_score' => self::$scores['left']['total'],
            'right_score' => self::$scores['right']['total'],
            'action' => $action,
            'quarter' => $clockFields['quarter'],
            'is_start' => $sessionFields['is_start'],
            'down' => $persistedFields['down'],
            'distance' => $persistedFields['distance'],
            'team_position' => $persistedFields['team_position'],
            'expected_yard_gain' => $persistedFields['expected_yard_gain'],
            'position_number' => $persistedFields['position_number'],
            'pkg' => $persistedFields['pkg'],
            'strategies' => $persistedFields['strategies'],
            'possession' => $persistedFields['possession'],
            'weather' => $persistedFields['weather'],
            'league_id' => $persistedFields['league_id'],
            'coverage_category' => $persistedFields['coverage_category'],
            'sync_time' => $persistedFields['sync_time'],
            'h_mark_position' => $hMarkPosition,
            'session_id' => $sessionFields['session_id'],
            'timer_remaining' => $timerRemaining,
            'sys_time' => $clockFields['sys_time'],
            ...$activityFields,
        ];

        if ($shouldRefreshTime && ! $isRestoredStart) {
            $practiceValues['time'] = now()->toDateTimeString();
        }

        WebsocketPracticeScoreboard::updateOrCreate(
            [
                'user_id' => $coachGroupId,
                'game_id' => $request->game_id,
            ],
            $practiceValues
        );

        $this->completeSessionOnEndMatch($coachGroupId, $action, $request, 'practice', $existingPractice);

        // Add team names to scores from request
        if ($request->leftTeamName) {
            self::$scores['left']['name'] = $request->leftTeamName;
        }
        if ($request->rightTeamName) {
            self::$scores['right']['name'] = $request->rightTeamName;
        }

        $payload = [
            'scores' => self::$scores,
            'team' => $team,
            'game_id' => $request->game_id,
            'user_id' => auth()->id(),
            'points' => $points,
            'action' => $action,
            'isStart' => $sessionFields['is_start'],
            'time' => $timerRemaining !== null ? $timerRemaining : $request->time,
            'sync_time' => $persistedFields['sync_time'],
            'sys_time' => $clockFields['sys_time'],
            'quarter' => $practiceValues['quarter'],
            'down' => $persistedFields['down'],
            'distance' => $persistedFields['distance'],
            'strategies' => $persistedFields['strategies'],
            'teamPosition' => $persistedFields['team_position'],
            'expectedyardgain' => $persistedFields['expected_yard_gain'],
            'positionNumber' => $persistedFields['position_number'],
            'pkg' => $persistedFields['pkg'],
            'possession' => $persistedFields['possession'],
            'weather' => $persistedFields['weather'],
            'coverageCategory' => $persistedFields['coverage_category'],
            'session_id' => $sessionFields['session_id'],
            'h_mark_position' => $hMarkPosition,
            'league_id' => $persistedFields['league_id'],
            'myteamId' => $request->myteamId,
            'oppteamId' => $request->oppteamId,
            'teamRightScore' => $isRestoredStart ? self::$scores['right']['total'] : ($request->teamRightScore ?? self::$scores['right']['total']),
            'teamLeftScore' => $isRestoredStart ? self::$scores['left']['total'] : ($request->teamLeftScore ?? self::$scores['left']['total']),
        ];

        try {
            if ($request->league_id && in_array($action, ['Start', 'EndMatch'])) {
                $scope = $this->buildMatchStartedScope($request, true);
                $status = ($action === 'Start') ? 'started' : 'ended';
                broadcast(new MatchStarted($request->league_id, $status, $scope))->toOthers();
            }

            // Only broadcast score update if not ending the match
            if ($action !== 'EndMatch') {
                broadcast(new PracticeScoreUpdated(
                    $payload,
                    $coachGroupId,
                    $request->game_id,
                    $request->league_id ? (int) $request->league_id : BroadcastLeagueResolver::fromGameId((int) $request->game_id)
                ))->toOthers();
            }
            \Log::info('After broadcast');
        } catch (\Exception $e) {
            \Log::error('PracticeScoreUpdated broadcast failed: ' . $e->getMessage());
        }

        return response()->noContent();
    }

    public function yardagePlaytoAssistant(Request $request)
    {
        \Log::info(['data all' => $request->all()]);

        $user = auth()->user();
        $coachGroupId = $user->role === 'head_coach'
            ? $user->id
            : $user->head_coach_id;

        if (!$coachGroupId) {
            return response()->json(['message' => 'Head coach group is not available for this user.'], 422);
        }

        $suggestionData = $request->input('suggestionData');
        if (!is_array($suggestionData)) {
            $suggestionData = [];
        }

        $gameId = $suggestionData['game_id'] ?? $request->input('game_id');
        $hMarkPosition = $this->resolveHMarkForBroadcast($request, $coachGroupId, $gameId, $suggestionData);
        $suggestionData['h_mark_position'] = $hMarkPosition;
        $suggestionData['distance'] = $request->input('distance', $suggestionData['distance'] ?? null);

        $payload = [
            'playName' => $request->input('PlayName'),
            'yardageGain' => $request->input('playYardageGain'),
            'sliderDirection' => $request->input('playSliderDirection'),
            'targetTeam' => $request->input('targetTeam'),
            'suggestionData' => $suggestionData,
            'selectedPlayIds' => $request->input('selectedPlayIds'),
            'play' => $request->input('playObject', $request->input('play')),
            'type' => $request->input('type'),
            'targetPlayers' => $request->input('targetPlayers'),
            'my_team' => $request->input('my_team'),
            'opponent_team' => $request->input('opponent_team'),
            'mode' => $request->input('mode'),
            'h_mark_position' => $hMarkPosition,
            'distance' => $suggestionData['distance'],
        ];

        $leagueId = $this->resolveBroadcastLeagueId($request, $suggestionData);
        if ($leagueId === null) {
            \Log::warning('YardageBroadcast skipped: league_id could not be resolved', [
                'coach_group_id' => $coachGroupId,
                'game_id' => $gameId,
            ]);

            return response()->json(['message' => 'league_id is required for broadcast'], 422);
        }

        try {
            broadcast(new YardageBroadcast($payload, (int) $coachGroupId, $leagueId))->toOthers();
        } catch (\Exception $e) {
            \Log::error('YardageBroadcast failed: ' . $e->getMessage());

            return response()->json(['message' => 'Broadcast failed'], 500);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Yardage play broadcast sent to assistant coach.',
            'data' => [
                'head_coach_id' => (int) $coachGroupId,
                'league_id' => $leagueId,
                'channel' => 'coach-group.' . $coachGroupId . '.league.' . $leagueId,
                'event' => 'assistant.coaches',
                'payload' => $payload,
            ],
        ], 200);
    }

    public function systemSuggestionToHeadCoach(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'assistant_coach') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $headCoachId = $user->head_coach_id;
        if (!$headCoachId) {
            return response()->json(['message' => 'Head coach is not linked to this assistant.'], 422);
        }

        $request->merge([
            'weather' => $request->input('weather', $request->input('weather_status')),
            'expected_yardage_gain' => $request->input(
                'expected_yardage_gain',
                $request->input('expectedyard', $request->input('yardage'))
            ),
        ]);

        $validated = $request->validate([
            'down' => 'required|integer|min:1|max:4',
            // min:0, not min:1 - distance is computed server-side now (see
            // resolveDistanceToFirstDown) and 0 is a real, valid situation
            // (right at the sticks), not a missing/invalid value.
            'distance' => 'required|integer|min:0|max:100',
            'weather' => 'required|string|in:Normal,Rain,Snow',
            'strategies' => ['required', 'string', Rule::in(['regular', 'red zone', 'hurry up', 'aggressive', 'chew clock'])],
            'expected_yardage_gain' => 'required|integer',
            'h_mark_position' => 'required|string|in:hmark_left,hmark_center,hmark_right',
            'game_id' => 'nullable|integer',
            'league_id' => 'nullable|integer',
            'mode' => 'nullable|string|in:practice,play',
        ]);

        $payload = [
            'down' => $validated['down'],
            'distance' => $validated['distance'],
            'weather' => $validated['weather'],
            'strategies' => $validated['strategies'],
            'expected_yardage_gain' => $validated['expected_yardage_gain'],
            'h_mark_position' => $validated['h_mark_position'],
            'game_id' => $validated['game_id'] ?? null,
            'league_id' => $validated['league_id'] ?? null,
            'mode' => $validated['mode'] ?? null,
            'actor_id' => $user->id,
            'actor_name' => $user->name,
        ];

        $leagueId = $this->resolveBroadcastLeagueId($request);
        if ($leagueId === null) {
            \Log::warning('HeadCoachSystemSuggestion skipped: league_id could not be resolved', [
                'head_coach_id' => $headCoachId,
                'game_id' => $validated['game_id'] ?? null,
            ]);

            return response()->json(['message' => 'league_id is required for broadcast'], 422);
        }

        try {
            broadcast(new HeadCoachSystemSuggestion($payload, (int) $headCoachId, $leagueId))->toOthers();
        } catch (\Exception $e) {
            \Log::error('HeadCoachSystemSuggestion broadcast failed: ' . $e->getMessage());

            return response()->json(['message' => 'Broadcast failed'], 500);
        }

        return response()->json([
            'status' => 200,
            'message' => 'System suggestion broadcast sent to head coach.',
            'data' => [
                'head_coach_id' => (int) $headCoachId,
                'league_id' => $leagueId,
                'channel' => 'coach-group.' . $headCoachId . '.league.' . $leagueId,
                'event' => 'head.coach.suggestion',
                'payload' => $payload,
            ],
        ], 200);
    }

    public function scoreBoardBroadCastQB(Request $request)
    {


        $validated = $request->validate([
            'team' => 'required|in:left,right,both',
            'points' => 'required|integer',
            'action' => 'required|string',
            'distance' => 'nullable|integer|min:1|max:100',
        ]);

        $team = $validated['team'];
        $points = $validated['points'];
        $action = $validated['action'];

        if($team=='left'){
            $operation = strtolower(trim($request->operation));
            $adjustedPoints = ($operation == 'subtract')
            ? $request->teamLeftScore - $points
            : $request->teamLeftScore + $points;

          self::$qb[$team]['total'] =  $adjustedPoints;
          self::$qb['right']['total'] =  $request->teamRightScore;

        }
        else if($team=='right'){
            $operation = strtolower(trim($request->operation));
            $adjustedPoints = ($operation == 'subtract')
            ? $request->teamRightScore - $points
            : $request->teamRightScore + $points;
           // $request->teamRightScore+$points;
             self::$qb[$team]['total'] = $adjustedPoints;
             self::$qb['left']['total'] =  $request->teamLeftScore;


        }else{

            self::$qb['left']['total'] = $request->teamLeftScore;
            self::$qb['right']['total'] = $request->teamRightScore;
        }

        $payload = [

            'team_left_score'=>$request->teamLeftScore,
            'team_right_score'=>$request->teamRightScore,
            'right'=>$request->myteam,
            'left'=>$request->oponentTeam,
            'points' => $points,
            'quarter_length'=>$request->quarter_length/4,
            'isStart'=>$request->isStartTime,
            'time'=>$request->time,

        ];



      \Log::info(['data log'=>'qb post working sucesslfulll']);
        $user = auth()->user();
        $coachGroupId = $user->role === 'head_coach'
            ? $user->id
            : $user->head_coach_id;

        $leagueId = $this->resolveBroadcastLeagueId($request);
        if ($leagueId === null) {
            \Log::warning('TeamScoreUpdated skipped: league_id could not be resolved', [
                'coach_group_id' => $coachGroupId,
            ]);

            return;
        }

         broadcast(new TeamScoreUpdated($payload, $coachGroupId, $leagueId))->toOthers();


    }

    public function scoreBoardBroadCastPlay(Request $request)
    {




        $validated = $request->validate([
            'title' => 'required|string',
            'image' => 'required|string',
            'type'  => 'required|in:offensive,defensive',
            'read1' => 'nullable|string',
            'read2' => 'nullable|string',
            'read3' => 'nullable|string',
            'yardageGain' => 'nullable|integer',
            'yard' => 'nullable|string',
            'game_id' => 'nullable|integer',
            'h_mark_position' => 'nullable|string|in:hmark_left,hmark_center,hmark_right',
            'hashPosition' => 'nullable|string|in:h-left,h-center,h-right',
        ]);

        $user = auth()->user();
        $coachGroupId = $user->role === 'head_coach'
            ? $user->id
            : $user->head_coach_id;

        $suggestionData = is_array($request->input('suggestionData')) ? $request->input('suggestionData') : null;
        $hMarkPosition = $this->resolveHMarkForBroadcast(
            $request,
            $coachGroupId,
            $validated['game_id'] ?? ($suggestionData['game_id'] ?? null),
            $suggestionData
        );

        $payload = [
            'title' => $validated['title'],
            'image' => $validated['image'],
            'type' => $validated['type'],
            'read1' => $validated['read1'] ?? null,
            'read2' => $validated['read2'] ?? null,
            'read3' => $validated['read3'] ?? null,
            'yardageGain' => $validated['yardageGain'] ?? null,
            'yard' => $validated['yard'] ?? null,
            'h_mark_position' => $hMarkPosition,
        ];

        \Log::info(['playe suggested broad cast'=>  $payload]);

        $leagueId = $this->resolveBroadcastLeagueId($request, $suggestionData);
        if ($leagueId === null) {
            \Log::warning('PlaySuggested skipped: league_id could not be resolved', [
                'coach_group_id' => $coachGroupId,
                'game_id' => $validated['game_id'] ?? null,
            ]);

            return;
        }

         broadcast(new PlaySuggested($payload, $coachGroupId, $leagueId))->toOthers();


    }


    public function scoreBoardBroadCast(Request $request)
    {





        $validated = $request->validate([
            'team' => 'required|in:left,right,both',
            'points' => 'required|integer',
            'action' => 'required|string'
        ]);

        $team = $validated['team'];
        $points = $validated['points'];
        $action = $validated['action'];

        $user = auth()->user();
        $coachGroupId = $user->role === 'head_coach'
            ? $user->id
            : $user->head_coach_id;

        if ($coachGroupId === null) {
            \Log::warning('scoreBoardBroadCast: missing coach group id', [
                'user_id' => $user->id,
                'role' => $user->role,
            ]);
            return response()->json(['error' => 'missing coach group id'], 400);
        }

        if ($action === 'Start') {
            $leagueId = $request->league_id ? (int) $request->league_id : null;
            $conflictResponse = $this->rejectConflictingGameMode($coachGroupId, false, $leagueId);
            if ($conflictResponse) {
                return $conflictResponse;
            }
        }

        $existingScoreboard = WebsocketScoreboard::where('user_id', $coachGroupId)
            ->where('game_id', $request->game_id)
            ->first();

        // Update game status and dates
        if ($action === 'Start') {
            Game::where('id', $request->game_id)->update([
                'status' => 'in_progress',
                'match_start_date' => now(),
            ]);
        }

        if ($action === 'EndMatch') {
            Game::where('id', $request->game_id)->update([
                'status' => 'ended',
                'match_end_date' => now(),
            ]);
        }

        $sessionFields = $this->mergeScoreboardSessionFields($existingScoreboard, $request, $action);
        $persistedFields = $this->mergeScoreboardPersistedFields($existingScoreboard, $request);
        $isRestoredStart = $this->isRestoredScoreboardStart($existingScoreboard, $action);

        if ($isRestoredStart) {
            $persistedFields = $this->preserveRestoredStartFields($persistedFields, $existingScoreboard);
        }

        // On Start, never carry over per-play settings from the prior session on this fixture.
        // Same fix as practiceScoreBoardBroadCast — see that method for explanation.
        if ($action === 'Start' && ! $isRestoredStart) {
            foreach (['down', 'distance', 'strategies', 'pkg', 'expected_yard_gain', 'position_number', 'team_position', 'possession', 'coverage_category'] as $field) {
                $persistedFields[$field] = null;
            }
        }

        $persistedFields['distance'] = $this->resolveDistanceToFirstDown(
            $persistedFields['down'] !== null ? (int) $persistedFields['down'] : null,
            $existingScoreboard?->distance !== null ? (int) $existingScoreboard->distance : null,
        );

        $clockFields = $this->resolveClockFieldsForBroadcast(
            $existingScoreboard,
            $request,
            $action,
            $isRestoredStart
        );
        $timerRemaining = $clockFields['timer_remaining'];

        $shouldRefreshTime = ! $existingScoreboard
            || ((int) $existingScoreboard->quarter != (int) $clockFields['quarter']);

        $hMarkPosition = $isRestoredStart && $existingScoreboard?->h_mark_position
            ? $existingScoreboard->h_mark_position
            : $this->resolveHMarkForBroadcast($request, $coachGroupId, $request->game_id);

        $scoreTotals = $this->resolveScoreTotals($existingScoreboard, $request, $action);
        self::$scores['left']['total'] = $scoreTotals['left'];
        self::$scores['right']['total'] = $scoreTotals['right'];

        $activityFields = $this->scoreboardActivityFields(
            $existingScoreboard,
            $action,
            $clockFields,
            $this->leagueForScoreboard($existingScoreboard, $request),
            'websocket_scoreboards'
        );

        $scoreboardValues = [
            'left_score' => self::$scores['left']['total'],
            'right_score' => self::$scores['right']['total'],
            'action' => $action,
            'sync_time' => $persistedFields['sync_time'],
            'quarter' => $clockFields['quarter'],
            'is_start' => $sessionFields['is_start'],
            'down' => $persistedFields['down'],
            'distance' => $persistedFields['distance'],
            'team_position' => $persistedFields['team_position'],
            'expected_yard_gain' => $persistedFields['expected_yard_gain'],
            'position_number' => $persistedFields['position_number'],
            'pkg' => $persistedFields['pkg'],
            'strategies' => $persistedFields['strategies'],
            'possession' => $persistedFields['possession'],
            'weather' => $persistedFields['weather'],
            'coverage_category' => $persistedFields['coverage_category'],
            'h_mark_position' => $hMarkPosition,
            'league_id' => $persistedFields['league_id'],
            'session_id' => $sessionFields['session_id'],
            'timer_remaining' => $timerRemaining,
            'sys_time' => $clockFields['sys_time'],
            ...$activityFields,
        ];

        if ($shouldRefreshTime && ! $isRestoredStart) {
            $scoreboardValues['time'] = \Carbon\Carbon::now('America/New_York')->toDateTimeString();
        }

        WebsocketScoreboard::updateOrCreate(
            [
                'user_id' => $coachGroupId,
                'game_id' => $request->game_id,
            ],
            $scoreboardValues
        );

        $this->completeSessionOnEndMatch($coachGroupId, $action, $request, 'play', $existingScoreboard);

        // Add team names to scores from request
        if ($request->leftTeamName) {
            self::$scores['left']['name'] = $request->leftTeamName;
        }
        if ($request->rightTeamName) {
            self::$scores['right']['name'] = $request->rightTeamName;
        }

        $payload = [
            'scores' => self::$scores,
            'team' => $team,
            'game_id' => $request->game_id,
          'user_id' => auth()->user()->role === 'head_coach'
        ? auth()->id()
        : auth()->user()->head_coach_id,

            'points' => $points,
            'action' => $action,
            'sync_time' => $persistedFields['sync_time'],
            'isStart' => $sessionFields['is_start'],
            'time' => $timerRemaining !== null ? $timerRemaining : $request->time,
            'sys_time' => $clockFields['sys_time'],
            'quarter' => $scoreboardValues['quarter'],
            'down' => $persistedFields['down'],
            'distance' => $persistedFields['distance'],
            'strategies' => $persistedFields['strategies'],
            'teamPosition' => $persistedFields['team_position'],
            'expectedyardgain' => $persistedFields['expected_yard_gain'],
            'positionNumber' => $persistedFields['position_number'],
            'pkg' => $persistedFields['pkg'],
            'possession' => $persistedFields['possession'],
            'weather' => $persistedFields['weather'],
            'coverageCategory' => $persistedFields['coverage_category'],
            'session_id' => $sessionFields['session_id'],
            'h_mark_position' => $hMarkPosition,
            'league_id' => $persistedFields['league_id'],
            'myteamId' => $request->myteamId,
            'oppteamId' => $request->oppteamId,
            'teamRightScore' => $isRestoredStart ? self::$scores['right']['total'] : ($request->teamRightScore ?? self::$scores['right']['total']),
            'teamLeftScore' => $isRestoredStart ? self::$scores['left']['total'] : ($request->teamLeftScore ?? self::$scores['left']['total']),
        ];

        \Log::info(['play_mode'=>$request->is_play_mode]);
        try {
            if ($request->league_id && in_array($action, ['Start', 'EndMatch'])) {
                $scope = $this->buildMatchStartedScope($request, false);
                $status = ($action === 'Start') ? 'started' : 'ended';
                broadcast(new MatchStarted($request->league_id, $status, $scope))->toOthers();
            }

            // Only broadcast score update if not ending the match
            if ($action !== 'EndMatch') {
                broadcast(new ScoreUpdated(
                    $payload,
                    $coachGroupId,
                    $request->game_id,
                    $request->league_id ? (int) $request->league_id : BroadcastLeagueResolver::fromGameId((int) $request->game_id)
                ))->toOthers();
            }
        } catch (\Exception $e) {
            \Log::error('ScoreUpdated broadcast failed: ' . $e->getMessage());
        }

        return response()->noContent();
    }

    public function getWebSocketScoreBoard(Request $request){

        $user = auth()->user();
        $coachGroupId = $user->role === 'head_coach'
            ? $user->id
            : $user->head_coach_id;

        $query = WebsocketScoreboard::where('user_id', $coachGroupId);

        if ($request->has('game_id')) {
            $query->where('game_id', $request->game_id);
        } else {
            $query->where('is_start', true);
            if ($request->has('league_id')) {
                $query->where('league_id', $request->league_id);
            }
        }

        $webSocketScorboard = $query->latest('updated_at')->first();

        if (!$webSocketScorboard) {
            \Log::debug('getWebSocketScoreBoard: no scoreboard row for coach', [
                'user_id' => $coachGroupId,
            ]);
            return response()->noContent();
        }

        $reconciled = ActiveGameModeGuard::reconcileScoreboardRow(
            $webSocketScorboard,
            (int) $coachGroupId,
            'play',
            'websocket_scoreboards',
        );

        if (! $reconciled) {
            return response()->noContent();
        }

        ActiveGameModeGuard::touchLiveScoreboardRow($reconciled, 'websocket_scoreboards');

        $reconciled = $this->applyLiveClockToScoreboardRow($reconciled);

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "scoreboardList", $reconciled);
    }

    public function getPracticeWebSocketScoreBoard(Request $request){

        $user = auth()->user();
        $coachGroupId = $user->role === 'head_coach'
            ? $user->id
            : $user->head_coach_id;

        $query = WebsocketPracticeScoreboard::where('user_id', $coachGroupId);

        if ($request->has('game_id')) {
            $query->where('game_id', $request->game_id);
        } else {
            $query->where('is_start', true);
            if ($request->has('league_id')) {
                $query->where('league_id', $request->league_id);
            }
        }

        $webSocketScorboard = $query->latest('updated_at')->first();

        if (!$webSocketScorboard) {
            \Log::debug('getPracticeWebSocketScoreBoard: no scoreboard row', [
                'user_id' => $coachGroupId,
                'game_id' => $request->input('game_id'),
            ]);
            return response()->noContent();
        }

        $reconciled = ActiveGameModeGuard::reconcileScoreboardRow(
            $webSocketScorboard,
            (int) $coachGroupId,
            'practice',
            'websocket_practice_scoreboards',
        );

        if (! $reconciled) {
            return response()->noContent();
        }

        ActiveGameModeGuard::touchLiveScoreboardRow($reconciled, 'websocket_practice_scoreboards');

        $reconciled = $this->applyLiveClockToScoreboardRow($reconciled);

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "scoreboardList", $reconciled);
    }


     public function delete($gameId){
         $user = auth()->user();
        $coachGroupId = $user->role === 'head_coach'
            ? $user->id
            : $user->head_coach_id;

        $deleted= WebsocketScoreboard::where('user_id',$coachGroupId)
        ->where('game_id', $gameId)
        ->delete();
        if ($deleted) {
          $leagueId = BroadcastLeagueResolver::fromGameId((int) $gameId);
          broadcast(new ScoreUpdated((object)[], $coachGroupId, $gameId, $leagueId))->toOthers();

        }
        return response()->noContent();

    }

    public function deletePractice($gameId){
        $user = auth()->user();
        $coachGroupId = $user->role === 'head_coach'
            ? $user->id
            : $user->head_coach_id;

        $deleted= WebsocketPracticeScoreboard::where('user_id',$coachGroupId)
        ->where('game_id', $gameId)
        ->delete();
        if ($deleted) {

             $leagueId = BroadcastLeagueResolver::fromGameId((int) $gameId);
             broadcast(new PracticeScoreUpdated((object)[], $coachGroupId, $gameId, $leagueId))->toOthers();

             return response()->noContent();

         }
  }
}
