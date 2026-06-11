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
use App\Http\Responses\BaseResponse;
use App\Support\ActiveGameModeGuard;
use App\Support\BroadcastLeagueResolver;
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

    private function rejectConflictingGameMode(int $coachGroupId, bool $isPractice): ?\Illuminate\Http\JsonResponse
    {
        try {
            ActiveGameModeGuard::assertNoOtherModeActive($coachGroupId, $isPractice);
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
     * @param  WebsocketScoreboard|WebsocketPracticeScoreboard|null  $existing
     */
    private function resolvePersistedTimerRemaining($existing, Request $request, array $persistedFields): ?int
    {
        if (is_numeric($request->input('time'))) {
            return (int) $request->time;
        }

        if ($persistedFields['sync_time'] !== null) {
            return (int) $persistedFields['sync_time'];
        }

        return $existing?->timer_remaining !== null
            ? (int) $existing->timer_remaining
            : null;
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
            'action' => 'required|string'
        ]);

        $team = $validated['team'];
        $points = $validated['points'];
        $action = $validated['action'];

        // Frontend already computes final scores optimistically before sending.
        // Backend just accepts them and broadcasts — no double-counting.
        self::$scores['left']['total'] = max(0, (int) $request->teamLeftScore);
        self::$scores['right']['total'] = max(0, (int) $request->teamRightScore);

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
            $conflictResponse = $this->rejectConflictingGameMode($coachGroupId, true);
            if ($conflictResponse) {
                return $conflictResponse;
            }
        }

        $existingPractice = WebsocketPracticeScoreboard::where('user_id', $coachGroupId)
            ->where('game_id', $request->game_id)
            ->first();

        if ($action === 'EndMatch') {
            $this->completeSessionOnEndMatch($coachGroupId, $action, $request, 'practice', $existingPractice);
        }

        $sessionFields = $this->mergeScoreboardSessionFields($existingPractice, $request, $action);
        $persistedFields = $this->mergeScoreboardPersistedFields($existingPractice, $request);
        $timerRemaining = $this->resolvePersistedTimerRemaining($existingPractice, $request, $persistedFields);

        $shouldRefreshTime = !$existingPractice
            || ($existingPractice->quarter != $request->quarter);

        $hMarkPosition = $this->resolveHMarkForBroadcast($request, $coachGroupId, $request->game_id);

        $practiceValues = [
            'left_score' => self::$scores['left']['total'],
            'right_score' => self::$scores['right']['total'],
            'action' => $action,
            'quarter' => $request->quarter,
            'is_start' => $sessionFields['is_start'],
            'down' => $persistedFields['down'],
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
            'sys_time' => now()->toDateTimeString(),
        ];

        if ($shouldRefreshTime) {
            $practiceValues['time'] = now()->toDateTimeString();
        }

        WebsocketPracticeScoreboard::updateOrCreate(
            [
                'user_id' => $coachGroupId,
                'game_id' => $request->game_id,
            ],
            $practiceValues
        );


        $payload = [
            'scores' => self::$scores,
            'team' => $team,
            'game_id' => $request->game_id,
            'user_id' => auth()->id(),
            'points' => $points,
            'action' => $action,
            'isStart' => $sessionFields['is_start'],
            'time'=>$request->time,
            'sync_time' => $persistedFields['sync_time'],
            'sys_time' => now()->toDateTimeString(),
            'quarter' => $request->quarter,
            'down' => $persistedFields['down'],
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
        ];

        try {
            if ($request->league_id && in_array($action, ['Start', 'EndMatch'])) {
                $scope = $this->buildMatchStartedScope($request, true);
                $status = ($action === 'Start') ? 'started' : 'ended';
                broadcast(new MatchStarted($request->league_id, $status, $scope))->toOthers();
            }
            broadcast(new PracticeScoreUpdated($payload, $coachGroupId, $request->game_id))->toOthers();
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
            'action' => 'required|string'
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

        // Frontend already applies the score change optimistically before sending;
        // accept the final totals directly to avoid double-counting.
        self::$scores['left']['total']  = max(0, (int) $request->teamLeftScore);
        self::$scores['right']['total'] = max(0, (int) $request->teamRightScore);

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
            $conflictResponse = $this->rejectConflictingGameMode($coachGroupId, false);
            if ($conflictResponse) {
                return $conflictResponse;
            }
        }

        $existingScoreboard = WebsocketScoreboard::where('user_id', $coachGroupId)
            ->where('game_id', $request->game_id)
            ->first();

        if ($action === 'EndMatch') {
            $this->completeSessionOnEndMatch($coachGroupId, $action, $request, 'play', $existingScoreboard);
        }

        $sessionFields = $this->mergeScoreboardSessionFields($existingScoreboard, $request, $action);
        $persistedFields = $this->mergeScoreboardPersistedFields($existingScoreboard, $request);
        $timerRemaining = $this->resolvePersistedTimerRemaining($existingScoreboard, $request, $persistedFields);

        $shouldRefreshTime = !$existingScoreboard
            || ($existingScoreboard->quarter != $request->quarter);

        $hMarkPosition = $this->resolveHMarkForBroadcast($request, $coachGroupId, $request->game_id);

        $scoreboardValues = [
            'left_score' => self::$scores['left']['total'],
            'right_score' => self::$scores['right']['total'],
            'action' => $action,
            'sync_time' => $persistedFields['sync_time'],
            'quarter' => $request->quarter,
            'is_start' => $sessionFields['is_start'],
            'down' => $persistedFields['down'],
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
            'sys_time' => now()->toDateTimeString(),
        ];

        if ($shouldRefreshTime) {
            $scoreboardValues['time'] = \Carbon\Carbon::now('America/New_York')->toDateTimeString();
        }

        WebsocketScoreboard::updateOrCreate(
            [
                'user_id' => $coachGroupId,
                'game_id' => $request->game_id,
            ],
            $scoreboardValues
        );


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
            'time'=>$request->time,
            'sys_time' => now()->toDateTimeString(),
            'quarter' => $request->quarter,
            'down' => $persistedFields['down'],
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
        ];

        \Log::info(['play_mode'=>$request->is_play_mode]);
        try {
            if ($request->league_id && in_array($action, ['Start', 'EndMatch'])) {
                $scope = $this->buildMatchStartedScope($request, false);
                $status = ($action === 'Start') ? 'started' : 'ended';
                broadcast(new MatchStarted($request->league_id, $status, $scope))->toOthers();
            }
            broadcast(new ScoreUpdated($payload, $coachGroupId, $request->game_id))->toOthers();
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
        \Log::info(['checking websocket with user id working or nort'=>$coachGroupId]);
        $query = WebsocketScoreboard::where('user_id', $coachGroupId);

        if ($request->has('game_id')) {
            $query->where('game_id', $request->game_id);
        } else {
            $query->where('is_start', true);
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

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "scoreboardList", $reconciled);
    }


     public function delete($gameId){
         $user = auth()->user();
        $coachGroupId = $user->role === 'head_coach'
            ? $user->id
            : $user->head_coach_id;

        $deleted= WebsocketScoreboard::where('user_id',$coachGroupId)
        ->delete();
        if ($deleted) {
          broadcast(new ScoreUpdated((object)[], $coachGroupId,$gameId))->toOthers();

        }
        return response()->noContent();

    }

    public function deletePractice($gameId){
        \Log::info(['gameid'=>$gameId]);
        $user = auth()->user();
        $coachGroupId = $user->role === 'head_coach'
            ? $user->id
            : $user->head_coach_id;

        $deleted= WebsocketPracticeScoreboard::where('user_id',$coachGroupId)
        ->delete();
        if ($deleted) {

             broadcast(new PracticeScoreUpdated((object)[], $coachGroupId,$gameId))->toOthers();

             return response()->noContent();

         }
  }
}