<?php

namespace App\Services;

use App\Models\BenchPlayer;
use App\Models\Game;
use App\Models\Play;
use Illuminate\Support\Collection;

class PlayRppScoreCalculator
{
    /**
     * @return array{0: Collection, 1: Collection} [offenseByPosition, defenseByPosition]
     */
    public function buildBenchPlayersByPosition(int $matchId, bool $isPractice = false): array
    {
        $gameData = Game::find($matchId);

        if ($gameData === null) {
            return [collect(), collect()];
        }

        $offenseByPosition = $this->fetchBenchBySide($matchId, $gameData, $isPractice, 'offence', 'myteam', $gameData->my_team_id);
        $defenseByPosition = $this->fetchBenchBySide($matchId, $gameData, $isPractice, 'deffence', 'opponent', $gameData->oponent_team_id);

        return [$offenseByPosition, $defenseByPosition];
    }

    public function calculateOffensiveTotalScore(
        Play $play,
        Collection $offenseByPosition,
        Collection $defenseByPosition
    ): float {
        $details = $this->buildOffensiveScoreDetails($play, $offenseByPosition, $defenseByPosition);

        return $details['total_score'];
    }

    /**
     * @return array{matchups: Collection, rpp_percentage_sum_by_offense: Collection, total_score: float}
     */
    public function buildOffensiveScoreDetails(
        Play $play,
        Collection $offenseByPosition,
        Collection $defenseByPosition
    ): array {
        $matchups = $play->offensiveTargets->map(function ($target) use ($offenseByPosition, $defenseByPosition) {
            $offPosName = $target->offensivePosition->name ?? null;
            $defPosName = $target->defensivePosition->name ?? null;
            $strength = $target->strength;

            if ($offPosName === null || $defPosName === null) {
                return [
                    'offensive_position' => $offPosName,
                    'strength' => $strength,
                    'defensive_position' => $defPosName,
                    'offensive_players' => collect(),
                    'defensive_players' => collect(),
                    'offensive_rpp' => 0,
                    'defensive_rpp' => 0,
                    'rpp_difference' => 0,
                    'strength_percentage' => ($strength ?? 100) / 100,
                    'rpp_difference_percentage' => 0,
                ];
            }

            $offPlayers = $offenseByPosition->get($offPosName, collect());
            $defPlayers = $defenseByPosition->get($defPosName, collect());

            $offRpp = $offPlayers->sum('rpp');
            $defRpp = $defPlayers->sum('rpp');
            $rppDifference = $offRpp - $defRpp;

            if ($defRpp > 0) {
                $ratio = $rppDifference / $defRpp;
            } else {
                $ratio = 0;
            }

            $rppDifferencePercentage = $rppDifference * $ratio;
            $strengthPercentage = $strength / 100;

            return [
                'offensive_position' => $offPosName,
                'strength' => $strength,
                'defensive_position' => $defPosName,
                'offensive_players' => $offPlayers,
                'defensive_players' => $defPlayers,
                'offensive_rpp' => $offRpp,
                'defensive_rpp' => $defRpp,
                'rpp_difference' => $rppDifference,
                'strength_percentage' => $strengthPercentage,
                'rpp_difference_percentage' => $rppDifferencePercentage,
            ];
        });

        $sumRppPercentageByOffense = $matchups->groupBy('offensive_position')->map(function ($group) {
            $sum = $group->sum('rpp_difference_percentage');
            $strength = $group->first()['strength'] ?? 100;
            $strengthPercentage = $strength / 100;

            return $sum * $strengthPercentage;
        });

        return [
            'matchups' => $matchups,
            'rpp_percentage_sum_by_offense' => $sumRppPercentageByOffense,
            'total_score' => round((float) $sumRppPercentageByOffense->sum(), 2),
        ];
    }

    public function enrichPlayWithRppScore(
        Play $play,
        Collection $offenseByPosition,
        Collection $defenseByPosition
    ): Play {
        $details = $this->buildOffensiveScoreDetails($play, $offenseByPosition, $defenseByPosition);
        $play->matchups = $details['matchups'];
        $play->rpp_percentage_sum_by_offense = $details['rpp_percentage_sum_by_offense'];
        $play->total_score = $details['total_score'];

        return $play;
    }

    private function fetchBenchBySide(
        int $matchId,
        Game $gameData,
        bool $isPractice,
        string $playerType,
        string $benchType,
        ?int $teamId
    ): Collection {
        return BenchPlayer::with(['player.player', 'practice_player'])
            ->where('game_id', $matchId)
            ->when(!$isPractice, function ($query) use ($teamId, $benchType) {
                $query->where('team_id', $teamId)
                    ->where('type', $benchType);
            })
            ->where('player_type', $playerType)
            ->get()
            ->filter(function ($benchPlayer) use ($isPractice) {
                if ($isPractice) {
                    return (bool) $benchPlayer->practice_player;
                }

                return $benchPlayer->player && $benchPlayer->player->player;
            })
            ->map(function ($benchPlayer) use ($isPractice) {
                $source = $isPractice ? $benchPlayer->practice_player : $benchPlayer->player;
                $basePlayer = $source->player ?? $source->practice_player ?? null;

                return [
                    'id' => $source->id ?? null,
                    'name' => $basePlayer->name ?? null,
                    'number' => $source->number ?? null,
                    'position_value' => $benchPlayer->position ?? null,
                    'rpp' => $benchPlayer->rpp,
                ];
            })
            ->groupBy('position_value');
    }
}
