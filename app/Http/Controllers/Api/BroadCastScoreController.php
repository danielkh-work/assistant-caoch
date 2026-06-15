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

    private function requestProvidesScoreboardField(Request $request, string $key): bool
    {
        if (! $request->has($key)) {
            return false;
        }

        $value = $request->input($key);

        return $value !== null && $value !== '';
    }

    /**
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
    private function resolvePersistedTimerRemaining(
        $existing,
        Request $request,
        array $persistedFields,
        string $action,
        int $points,
    ): ?int {
        if (is_numeric($request->input('time'))) {
            $incoming = (int) $request->time;

            if ($existing && $this->isNonScoringStateBroadcast($action, $points)) {
                $existingTimer = $existing->timer_remaining !== null
                    ? (int) $existing->timer_remaining
                    : null;

                if ($existingTimer > 0 && $incoming === 0) {
                    return $existingTimer;
                }
            }

            return $incoming;
        }

        if ($persistedFields['sync_time'] !== null) {
            return (int) $persistedFields['sync_time'];
        }

        return $existing?->timer_remaining !== null
            ? (int) $existing->timer_remaining
            : null;
    }

    /**
     * @param  WebsocketScoreboard|WebsocketPracticeScoreboard|null  $existing
     */
    private function resolvePersistedSessionId($existing, Request $request): mixed
    {
        return $request->filled('session_id')
            ? $request->session_id
            : ($existing?->session_id ?? null);
    }

    private const SCORING_BROADCAST_ACTIONS = [
        'TD', 'RED', 'SAFTEY', 'SAFETY', 'FIELD GOAL', 'PAT1', 'PAT2',
    ];

    /**
     * State-sync broadcasts (INFO, Stop, etc.) must not wipe persisted match data when
     * the client sends default zeros before session restore completes.
     */
    private function isNonScoringStateBroadcast(string $action, int $points): bool
    {
        if (in_array($action, self::SCORING_BROADCAST_ACTIONS, true)) {
            return false;
        }

        if (in_array($action, ['Start', 'EndMatch'], true)) {
            return false;
        }

        return $points === 0;
    }

    /**
     * @param  WebsocketScoreboard|WebsocketPracticeScoreboard|null  $existing
     * @return array{left: int, right: int}
     */
    private function resolvePersistedScores($existing, Request $request, string $action, int $points): array
    {
        $left = max(0, (int) $request->teamLeftScore);
        $right = max(0, (int) $request->teamRightScore);

        if ($existing && $this->isNonScoringStateBroadcast($action, $points)) {
            $existingLeft = (int) $existing->left_score;
            $existingRight = (int) $existing->right_score;

            if (($existingLeft > 0 || $existingRight > 0) && $left === 0 && $right === 0) {
                $left = $existingLeft;
                $right = $existingRight;
            }
        }

        return ['left' => $left, 'right' => $right];
    }

    /**
     * @param  WebsocketScoreboard|WebsocketPracticeScoreboard|null  $existing
     */
    private function resolvePersistedQuarter($existing, Request $request, string $action, int $points): mixed
    {
        $incoming = $request->input('quarter');

        if ($existing && $this->isNonScoringStateBroadcast($action, $points)) {
            $existingQuarter = (int) $existing->quarter;
            $incomingQuarter = (int) $incoming;

            if ($existingQuarter > 0 && $incomingQuarter > 0 && $incomingQuarter < $existingQuarter) {
                return (string) $existingQuarter;
            }
        }

        return $incoming;
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

        $existingPractice = WebsocketPracticeScoreboard::where('user_id', $coachGroupId)
            ->where('game_id', $request->game_id)
            ->first();

        $scores = $this->resolvePersistedScores($existingPractice, $request, $action, $points);
        self::$scores['left']['total'] = $scores['left'];
        self::$scores['right']['total'] = $scores['right'];

        // On a fresh Start do not carry play-state from the previous match's row.
        // mergeScoreboardPersistedFields falls back to the existing row when the
        // request omits a field, so passing null forces all fields to null on Start.
        $existingForMerge = ($action === 'Start') ? null : $existingPractice;
        $persistedFields = $this->mergeScoreboardPersistedFields($existingForMerge, $request);
        $sessionId = $this->resolvePersistedSessionId($existingPractice, $request);
        $quarter = $this->resolvePersistedQuarter($existingPractice, $request, $action, $points);
        $timerRemaining = $this->resolvePersistedTimerRemaining(
            $existingPractice,
            $request,
            $persistedFields,
            $action,
            $points,
        );

        $shouldRefreshTime = !$existingPractice
            || ((int) $existingPractice->quarter != (int) $quarter);

        $hMarkPosition = $this->resolveHMarkForBroadcast($request, $coachGroupId, $request->game_id);

        $practiceValues = [
            'left_score' => self::$scores['left']['total'],
            'right_score' => self::$scores['right']['total'],
            'action' => $action,
            'quarter' => $quarter,
            'is_start' => $action === 'EndMatch'
                ? false
                : filter_var($request->isStartTime, FILTER_VALIDATE_BOOLEAN),
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
            'h_mark_position' => $hMarkPosition,
            'session_id' => $sessionId,
            'timer_remaining' => $timerRemaining,
            'sys_time' => gmdate('Y-m-d\TH:i:s') . 'Z',
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
            'isStart'=>$request->isStartTime,
            'time'=>$timerRemaining ?? $request->time,
            'sync_time' => $persistedFields['sync_time'],
            'sys_time' => gmdate('Y-m-d\TH:i:s') . 'Z',
            'quarter' => $quarter,
            'down' => $persistedFields['down'],
            'strategies' => $persistedFields['strategies'],
            'teamPosition' => $persistedFields['team_position'],
            'expectedyardgain' => $persistedFields['expected_yard_gain'],
            'positionNumber' => $persistedFields['position_number'],
            'pkg' => $persistedFields['pkg'],
            'possession' => $persistedFields['possession'],
            'weather' => $persistedFields['weather'],
            'coverageCategory' => $persistedFields['coverage_category'],
            'session_id' => $sessionId,
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

        try {
            broadcast(new YardageBroadcast($payload, (int) $coachGroupId))->toOthers();
        } catch (\Exception $e) {
            \Log::error('YardageBroadcast failed: ' . $e->getMessage());

            return response()->json(['message' => 'Broadcast failed'], 500);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Yardage play broadcast sent to assistant coach.',
            'data' => [
                'head_coach_id' => (int) $coachGroupId,
                'channel' => 'coach-group.' . $coachGroupId,
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

        try {
            broadcast(new HeadCoachSystemSuggestion($payload, (int) $headCoachId))->toOthers();
        } catch (\Exception $e) {
            \Log::error('HeadCoachSystemSuggestion broadcast failed: ' . $e->getMessage());

            return response()->json(['message' => 'Broadcast failed'], 500);
        }

        return response()->json([
            'status' => 200,
            'message' => 'System suggestion broadcast sent to head coach.',
            'data' => [
                'head_coach_id' => (int) $headCoachId,
                'channel' => 'coach-group.' . $headCoachId,
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




         broadcast(new TeamScoreUpdated($payload, $coachGroupId))->toOthers();


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

         broadcast(new PlaySuggested($payload, $coachGroupId))->toOthers();


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

        $existingScoreboard = WebsocketScoreboard::where('user_id', $coachGroupId)
            ->where('game_id', $request->game_id)
            ->first();

        $scores = $this->resolvePersistedScores($existingScoreboard, $request, $action, $points);
        self::$scores['left']['total'] = $scores['left'];
        self::$scores['right']['total'] = $scores['right'];

        // On a fresh Start do not carry play-state from the previous match's row.
        $existingForMerge = ($action === 'Start') ? null : $existingScoreboard;
        $persistedFields = $this->mergeScoreboardPersistedFields($existingForMerge, $request);
        $sessionId = $this->resolvePersistedSessionId($existingScoreboard, $request);
        $quarter = $this->resolvePersistedQuarter($existingScoreboard, $request, $action, $points);
        $timerRemaining = $this->resolvePersistedTimerRemaining(
            $existingScoreboard,
            $request,
            $persistedFields,
            $action,
            $points,
        );

        $shouldRefreshTime = !$existingScoreboard
            || ((int) $existingScoreboard->quarter != (int) $quarter);

        $hMarkPosition = $this->resolveHMarkForBroadcast($request, $coachGroupId, $request->game_id);

        $scoreboardValues = [
            'left_score' => self::$scores['left']['total'],
            'right_score' => self::$scores['right']['total'],
            'action' => $action,
            'sync_time' => $timerRemaining ?? $persistedFields['sync_time'],
            'quarter' => $quarter,
            'is_start' => $action === 'EndMatch'
                ? false
                : filter_var($request->isStartTime, FILTER_VALIDATE_BOOLEAN),
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
            'session_id' => $sessionId,
            'timer_remaining' => $timerRemaining,
            'sys_time' => gmdate('Y-m-d\TH:i:s') . 'Z',
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
            'isStart'=>$request->isStartTime,
            'time'=>$timerRemaining ?? $request->time,
            'sys_time' => gmdate('Y-m-d\TH:i:s') . 'Z',
            'quarter' => $quarter,
            'down' => $persistedFields['down'],
            'strategies' => $persistedFields['strategies'],
            'teamPosition' => $persistedFields['team_position'],
            'expectedyardgain' => $persistedFields['expected_yard_gain'],
            'positionNumber' => $persistedFields['position_number'],
            'pkg' => $persistedFields['pkg'],
            'possession' => $persistedFields['possession'],
            'weather' => $persistedFields['weather'],
            'coverageCategory' => $persistedFields['coverage_category'],
            'session_id' => $sessionId,
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
        }

        $webSocketScorboard = $query->latest('updated_at')->first();

        if (!$webSocketScorboard) {
            \Log::debug('getWebSocketScoreBoard: no scoreboard row for coach', [
                'user_id' => $coachGroupId,
            ]);
            return response()->noContent();
        }

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "scoreboardList", $webSocketScorboard);
    }

    public function getPracticeWebSocketScoreBoard(Request $request){

        $user = auth()->user();
        $coachGroupId = $user->role === 'head_coach'
            ? $user->id
            : $user->head_coach_id;

        $query = WebsocketPracticeScoreboard::where('user_id', $coachGroupId);

        if ($request->has('game_id')) {
            $query->where('game_id', $request->game_id);
        }

        $webSocketScorboard = $query->latest('updated_at')->first();

        if (!$webSocketScorboard) {
            \Log::debug('getPracticeWebSocketScoreBoard: no scoreboard row', [
                'user_id' => $coachGroupId,
                'game_id' => $request->input('game_id'),
            ]);
            return response()->noContent();
        }

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "scoreboardList", $webSocketScorboard);
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