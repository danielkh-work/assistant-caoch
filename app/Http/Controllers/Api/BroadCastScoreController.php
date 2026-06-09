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
use App\Support\BroadcastLeagueResolver;

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

        $existingPractice = WebsocketPracticeScoreboard::where('user_id', $coachGroupId)
            ->where('game_id', $request->game_id)
            ->first();

        $shouldRefreshTime = !$existingPractice
            || ($existingPractice->quarter != $request->quarter);

        $hMarkPosition = $this->resolveHMarkForBroadcast($request, $coachGroupId, $request->game_id);

        $practiceValues = [
            'left_score' => self::$scores['left']['total'],
            'right_score' => self::$scores['right']['total'],
            'action' => $action,
            'quarter' => $request->quarter,
            'is_start' => $request->isStartTime,
            'down' => $request->down,
            'team_position' => $request->teamPosition,
            'expected_yard_gain' => $request->expectedyardgain,
            'position_number' => $request->positionNumber,
            'pkg' => $request->pkg,
            'strategies' => $request->strategies,
            'possession' => $request->possession,
            'weather' => $request->weather,
            'league_id' => $request->league_id,
            'coverage_category' => $request->coverageCategory,
            'h_mark_position' => $hMarkPosition,
            'session_id' => $request->session_id ?: null,
            'timer_remaining' => is_numeric($request->time) ? (int) $request->time : null,
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
            'isStart'=>$request->isStartTime,
            'time'=>$request->time,
            'sync_time' => $request->sync_time,
            'sys_time' => now()->toDateTimeString(),
            'quarter' => $request->quarter,
            'down' => $request->down,
            'strategies' => $request->strategies,
            'teamPosition' => $request->teamPosition,
            'expectedyardgain' => $request->expectedyardgain,
            'positionNumber' => $request->positionNumber,
            'pkg' => $request->pkg,
            'possession' => $request->possession,
            'weather' => $request->weather,
            'coverageCategory' => $request->coverageCategory,
            'session_id' => $request->session_id,
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

        $existingScoreboard = WebsocketScoreboard::where('user_id', $coachGroupId)
            ->where('game_id', $request->game_id)
            ->first();

        $shouldRefreshTime = !$existingScoreboard
            || ($existingScoreboard->quarter != $request->quarter);

        $hMarkPosition = $this->resolveHMarkForBroadcast($request, $coachGroupId, $request->game_id);

        $scoreboardValues = [
            'left_score' => self::$scores['left']['total'],
            'right_score' => self::$scores['right']['total'],
            'action' => $action,
            'sync_time' => $request->sync_time,
            'quarter' => $request->quarter,
            'is_start' => $request->isStartTime,
            'down' => $request->down,
            'team_position' => $request->teamPosition,
            'expected_yard_gain' => $request->expectedyardgain,
            'position_number' => $request->positionNumber,
            'pkg' => $request->pkg,
            'strategies' => $request->strategies,
            'possession' => $request->possession,
            'weather' => $request->weather,
            'coverage_category' => $request->coverageCategory,
            'h_mark_position' => $hMarkPosition,
            'league_id' => $request->league_id,
            'session_id' => $request->session_id ?: null,
            'timer_remaining' => is_numeric($request->time) ? (int) $request->time : null,
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
            'sync_time' => $request->sync_time,
            'isStart'=>$request->isStartTime,
            'time'=>$request->time,
            'sys_time' => now()->toDateTimeString(),
            'quarter' => $request->quarter,
            'down' => $request->down,
            'strategies' => $request->strategies,
            'teamPosition' => $request->teamPosition,
            'expectedyardgain' => $request->expectedyardgain,
            'positionNumber' => $request->positionNumber,
            'pkg' => $request->pkg,
            'possession' => $request->possession,
            'weather' => $request->weather,
            'coverageCategory' => $request->coverageCategory,
            'session_id' => $request->session_id,
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