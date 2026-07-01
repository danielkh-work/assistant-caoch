<?php

namespace App\Support;

use App\Models\Device;
use App\Models\PlayGameMode;
use App\Models\WebsocketPracticeScoreboard;
use App\Models\WebsocketScoreboard;

class ScoreboardBroadcastPayload
{
    /**
     * Resolve the live scoreboard payload for a device, or null when no match is running.
     *
     * @return array<string, mixed>|null
     */
    public static function resolveForDevice(Device $device): ?array
    {
        $headCoachId = (int) ($device->user_id ?? 0);
        if ($headCoachId === 0) {
            return null;
        }

        $device->loadMissing('leagues');
        $leagueIds = $device->leagues->pluck('id')->all();

        foreach (['play', 'practice'] as $gameMode) {
            $model = $gameMode === 'play' ? WebsocketScoreboard::class : WebsocketPracticeScoreboard::class;
            $table = $gameMode === 'play' ? 'websocket_scoreboards' : 'websocket_practice_scoreboards';

            $query = $model::where('user_id', $headCoachId)->where('is_start', true);
            if ($leagueIds !== []) {
                $query->whereIn('league_id', $leagueIds);
            }

            $row = $query->latest('updated_at')->first();
            $reconciled = ActiveGameModeGuard::reconcileScoreboardRow($row, $headCoachId, $gameMode, $table);

            if ($reconciled) {
                return self::fromLiveRow($reconciled, $gameMode, $headCoachId);
            }
        }

        return null;
    }

    /**
     * Build a scoreboard broadcast payload from a persisted scoreboard row.
     *
     * @return array<string, mixed>
     */
    public static function fromLiveRow(object $row, string $gameMode, int $headCoachId): array
    {
        $leftScore = (int) ($row->left_score ?? 0);
        $rightScore = (int) ($row->right_score ?? 0);

        $playGameMode = self::resolvePlayGameMode($row);

        $payload = [
            'game_mode' => $gameMode,
            'scores' => [
                'left' => ['total' => $leftScore],
                'right' => ['total' => $rightScore],
            ],
            'team' => 'both',
            'game_id' => $row->game_id,
            'user_id' => $headCoachId,
            'points' => 0,
            'action' => $row->action,
            'sync_time' => $row->sync_time ?? null,
            'isStart' => (bool) ($row->is_start ?? false),
            'time' => $row->time ?? null,
            'sys_time' => $row->sys_time ?? null,
            'quarter' => $row->quarter ?? null,
            'down' => $row->down ?? null,
            'strategies' => $row->strategies ?? null,
            'teamPosition' => $row->team_position ?? null,
            'expectedyardgain' => $row->expected_yard_gain ?? null,
            'positionNumber' => $row->position_number ?? null,
            'pkg' => $row->pkg ?? null,
            'possession' => $row->possession ?? null,
            'weather' => $row->weather ?? null,
            'coverageCategory' => $row->coverage_category ?? null,
            'session_id' => $row->session_id ?? null,
            'h_mark_position' => $row->h_mark_position ?? null,
            'league_id' => $row->league_id ?? null,
            'myteamId' => $playGameMode?->my_team_id,
            'oppteamId' => $playGameMode?->oponent_team_id,
            'teamLeftScore' => $leftScore,
            'teamRightScore' => $rightScore,
        ];

        return self::enrichTeamNames($payload);
    }

    /**
     * Add team names inside scores structure if not already present.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function enrichTeamNames(array $data): array
    {
        if (! isset($data['scores']) || ! isset($data['game_id'])) {
            return $data;
        }

        if (isset($data['leftTeamName']) && ! isset($data['scores']['left']['name'])) {
            $data['scores']['left']['name'] = $data['leftTeamName'];
        }
        if (isset($data['rightTeamName']) && ! isset($data['scores']['right']['name'])) {
            $data['scores']['right']['name'] = $data['rightTeamName'];
        }

        if (! isset($data['scores']['left']['name']) || ! isset($data['scores']['right']['name'])) {
            $sessionId = $data['session_id'] ?? null;
            $game = $sessionId
                ? PlayGameMode::with(['myTeam', 'opponentTeam'])->find($sessionId)
                : PlayGameMode::with(['myTeam', 'opponentTeam'])->find($data['game_id']);

            if ($game) {
                if (! isset($data['scores']['left']['name']) && $game->myTeam) {
                    $data['scores']['left']['name'] = $game->myTeam->team_name;
                }
                if (! isset($data['scores']['right']['name']) && $game->opponentTeam) {
                    $data['scores']['right']['name'] = $game->opponentTeam->team_name;
                }
            }
        }

        return $data;
    }

    private static function resolvePlayGameMode(object $row): ?PlayGameMode
    {
        if (! empty($row->session_id)) {
            $session = PlayGameMode::find($row->session_id);
            if ($session) {
                return $session;
            }
        }

        if (! empty($row->game_id)) {
            return PlayGameMode::find($row->game_id);
        }

        return null;
    }
}
