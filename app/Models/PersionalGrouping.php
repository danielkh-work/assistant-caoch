<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersionalGrouping extends Model
{
    use HasFactory;

    protected $table = 'personal_groupings';
    protected $appends = ['players_data','practice_players_data'];
    protected $fillable = [
        'game_id',
        'league_id',
        'team_id',
        'group_name',
        'type',
        'players',
        'practice_players',
        'group_level',
        'status',
        'roster_repair_player_ids',
    ];

   protected $casts = [
    'players' => 'array',
    'practice_players' => 'array',
    'roster_repair_player_ids' => 'array',
];




    // public function getPlayersDataAttribute()
    // {
    //     if (empty($this->players)) {
    //         return [];
    //     }

    //     return TeamPlayer::whereIn('id', $this->players)->get();
    // }

    public function getPlayersDataAttribute()
    {
        if (!$this->players) {
            return collect();
        }

        $players = is_array($this->players) ? $this->players : json_decode($this->players, true);

        $ids = collect($players)->pluck('id');

        $teamPlayers = TeamPlayer::whereIn('id', $ids)->get()->keyBy('id');



        // return collect($players)->map(function ($player) use ($teamPlayers) {

        //     $teamPlayer = $teamPlayers->get($player['id']);

        //     if (!$teamPlayer) {
        //         return null;
        //     }

        //     return [
        //         'id' => $teamPlayer->id,
        //         'name' => $teamPlayer->name,
        //         'rpp' => $teamPlayer->rpp,

        //         'selected_position' => $player['positions'], // position from JSON
        //     ];
        // })->filter()->values();


        return collect($players)->map(function ($player) use ($teamPlayers) {

    // ✅ handle integer case
                    if (is_int($player)) {
                        $teamPlayer = $teamPlayers->get($player);

                          \Log::info('Integer Player:', [
            'input' => $player,
            'team_player' => $teamPlayer
        ]);

                        if (!$teamPlayer) return null;

                        return [
                            'id' => $teamPlayer->id,
                            'name' => $teamPlayer->name,
                            'rpp' => $teamPlayer->rpp ?? 0,
                            'type' => $teamPlayer->type ?? 0,
                            'selected_position' => null,
                        ];
                    }

                    // ✅ handle array case
                    if (is_array($player)) {
                        $teamPlayer = $teamPlayers->get($player['id'] ?? null);


                        if (!$teamPlayer) return null;


                        return [
                            'id' => $teamPlayer->id,
                            'name' => $teamPlayer->name,
                            'rpp' => $teamPlayer->rpp ?? 0,
                            'type' => $teamPlayer->type ?? 0,
                            'selected_position' => $player['positions'] ?? null,
                        ];
                    }

                    return null;

                })->filter()->values();
    }



    public function getPracticePlayersDataAttribute()
    {
        if (empty($this->practice_players)) {
            return collect();
        }
        $players = is_array($this->practice_players) ? $this->practice_players : json_decode($this->practice_players, true);
        $ids = collect($players)->pluck('id');
        $practicePlayers = PracticeTeamPlayer::whereIn('id', $ids)->get()->keyBy('id');
        return collect($players)->map(function ($player) use ($practicePlayers) {
            $practicePlayer = $practicePlayers->get($player['id']);

            if (!$practicePlayer) {
                return null;
            }
            return [
                'id' => $practicePlayer->id,
                'name' => $practicePlayer->name,
                'rpp' => $practicePlayer->rpp,
                'selected_position' => $player['positions'], // positions from JSON
            ];
        })->filter()->values();
    }


//    public function getPracticePlayersDataAttribute()
//     {
//         if (empty($this->practice_players)) {
//             return [];
//         }

//         return PracticeTeamPlayer::whereIn('id', $this->practice_players)->get();
//     }
   public function plays()
    {
        return $this->belongsToMany(
            Play::class,               // Related model
            'personal_grouping_play', // Pivot table name
            'personal_grouping_id',   // Foreign key on pivot table for this model
            'play_id'                 // Foreign key on pivot table for related model
        );
    }

    public function defensivePlays()
    {
            return $this->belongsToMany(
                DefensivePlay::class,           // Related model
                'defensive_play_personal_grouping', // Pivot table
                'personal_grouping_id',         // Foreign key on pivot table pointing to this model (PersonalGrouping)
                'defensive_play_id'             // Foreign key on pivot table pointing to related model (DefensivePlay)
            );

    }

    /**
     * Team / practice player IDs currently on the match roster (Configure Players).
     *
     * @param  int  $gameType  configure game_type: 1 = regular, 2 = practice
     * @return array<int,int>
     */
    public static function matchRosterPlayerIdsForConfigure(int $teamId, int $matchId, int $gameType): array
    {
        $isPracticeConfigure = (int) $gameType === 2;

        $rows = ConfiguredPlayingTeamPlayer::query()
            ->where('team_id', $teamId)
            ->where('match_id', $matchId)
            ->where('game_type', $gameType)
            ->get();

        $ids = [];
        foreach ($rows as $row) {
            if ($isPracticeConfigure && $row->practice_player_id) {
                $ids[] = (int) $row->practice_player_id;
            }
            if (! $isPracticeConfigure && $row->player_id) {
                $ids[] = (int) $row->player_id;
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * Keep only roster-repair IDs that still appear on the match roster.
     *
     * @param  array<int,mixed>  $repairIds
     * @return array<int,int>
     */
    public static function pruneRosterRepairIdsAgainstMatchRoster(array $repairIds, int $teamId, int $matchId, int $gameType): array
    {
        $repairIds = array_values(array_unique(array_map('intval', $repairIds)));
        if ($repairIds === []) {
            return [];
        }

        $allowed = self::matchRosterPlayerIdsForConfigure($teamId, $matchId, $gameType);
        if ($allowed === []) {
            return [];
        }

        $flip = array_flip($allowed);

        return array_values(array_filter($repairIds, fn ($id) => isset($flip[(int) $id])));
    }

    /**
     * After Configure Players save (or for self-heal on fetch), drop stale repair IDs for all groups on this match.
     */
    public static function pruneAllStaleRepairsAfterConfigureSave(int $teamId, int $matchId, int $gameType): void
    {
        $isPractice = (int) $gameType === 2;
        $expectedGroupLevel = $isPractice ? 2 : 1;

        $groups = self::query()
            ->where('team_id', $teamId)
            ->where('game_id', $matchId)
            ->where('group_level', $expectedGroupLevel)
            ->get();

        foreach ($groups as $group) {
            $repair = $group->roster_repair_player_ids ?? [];
            if (! is_array($repair) || $repair === []) {
                continue;
            }
            $repair = array_values(array_unique(array_map('intval', $repair)));
            $pruned = self::pruneRosterRepairIdsAgainstMatchRoster($repair, $teamId, $matchId, $gameType);
            if ($pruned !== $repair) {
                $group->roster_repair_player_ids = $pruned === [] ? null : $pruned;
                $group->save();
            }
        }
    }

}
