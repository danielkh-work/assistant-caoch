<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\LeagueTeam;
use App\Models\PracticeTeamPlayer;
use App\Models\Team;
use App\Models\TeamGroup;
use App\Models\TeamPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class TeamController extends Controller
{
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $team =  new Team();
            $team->name =  $request->team_name;
            if ($request->hasFile('image')) {
                $path =  uploadImage($request->image, 'uploads');
                $team->image = $path;
            }
            $team->save();
           

            
            foreach ($request->playerid as  $key=> $id) {
                
                $t_player =  new TeamPlayer();
                $t_player->team_id = $team->id;
                $t_player->player_id = $id;
                $t_player->size = 0;
                $t_player->position = 0;
                $t_player->strength = 0;
                $t_player->type = $request->playertype[$key];
                $t_player->save();
            }
            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Team save successFully", $team);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $th->getMessage());
        }
    }

    public function index()
    {
        $team =  Team::with('teamplayer.player')->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Team list", $team);
    }
    public function view(Request $request, $id)
    {
        if (filter_var($request->query('without_players', false), FILTER_VALIDATE_BOOLEAN)) {
            $team = LeagueTeam::find($id);
            if (!$team) {
                return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, 'Team not found.');
            }

            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, 'Team view', $team);
        }

        $team = LeagueTeam::with(['teamplayer.teamPlayerPosition','teamplayer.player.playerPosition','practiceTeamplayer.TeamPlayer.player','practiceTeamplayer.TeamPlayer.teamPlayerPosition','practiceTeamplayer.positions'])->find($id);
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, 'Team view', $team);
    }

    /**
     * Paginated roster for team edit UI (matches TeamEdit.vue bottom table payload shape).
     */
    public function paginatedTeamPlayers(Request $request, $id)
    {
        if (!LeagueTeam::whereKey($id)->exists()) {
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, 'Team not found.');
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(500, (int) $request->input('per_page', 10)));

        $query = TeamPlayer::query()
            ->where('team_id', $id)
            ->with(['teamPlayerPosition', 'player.playerPosition'])
            ->orderBy('player_id'); // deterministic paging

        $searchTerm = trim((string) $request->input('search', ''));
        if ($searchTerm !== '') {
            $needle = '%'.addcslashes($searchTerm, '%_\\').'%';
            $query->where(function ($q) use ($needle) {
                $q->where('name', 'like', $needle)
                    ->orWhereHas('player', fn ($p) => $p->where('name', 'like', $needle));
            });
        }

        $playingPlayerIds = $this->configuredPlayingPlayerIdSetForTeam((int) $id);
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $rows = $paginator->getCollection()
            ->map(fn (TeamPlayer $tp) => $this->teamPlayerEditRowFormat($tp, $playingPlayerIds))
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
            'Team roster',
            $rows,
            null,
            null,
            $pagination,
        );
    }

    /**
     * Paginated practice-team roster (practice_team_players) for PracticeTeam.vue bottom table.
     */
    public function paginatedPracticeTeamPlayers(Request $request, $id)
    {
        if (! LeagueTeam::whereKey($id)->exists()) {
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, 'Team not found.');
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(500, (int) $request->input('per_page', 10)));

        $query = PracticeTeamPlayer::query()
            ->where('team_id', $id)
            ->with(['positions', 'TeamPlayer.player.playerPosition', 'TeamPlayer.teamPlayerPosition'])
            ->orderBy('id');

        $searchTerm = trim((string) $request->input('search', ''));
        if ($searchTerm !== '') {
            $needle = '%'.addcslashes($searchTerm, '%_\\').'%';
            $query->where(function ($q) use ($needle) {
                $q->where('practice_team_players.name', 'like', $needle)
                    ->orWhereHas('TeamPlayer.player', fn ($p) => $p->where('name', 'like', $needle));
            });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $rows = $paginator->getCollection()
            ->map(fn (PracticeTeamPlayer $ptp) => $this->practiceTeamPlayerEditRowFormat($ptp))
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
            'Practice team roster',
            $rows,
            null,
            null,
            $pagination,
        );
    }

    /**
     * Full roster payload for the team-group editor.
     */
    public function players(Request $request, $id)
    {
        if (! LeagueTeam::whereKey($id)->exists()) {
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, 'Team not found.');
        }

        $searchTerm = trim((string) $request->query('search', ''));

        $query = TeamPlayer::query()
            ->where('team_id', $id)
            ->with(['teamPlayerPosition', 'player.playerPosition'])
            ->orderBy('player_id');

        if ($searchTerm !== '') {
            $needle = '%' . addcslashes($searchTerm, '%_\\') . '%';
            $query->where(function ($q) use ($needle) {
                $q->where('name', 'like', $needle)
                    ->orWhereHas('player', fn ($player) => $player->where('name', 'like', $needle));
            });
        }

        $playingPlayerIds = $this->configuredPlayingPlayerIdSetForTeam((int) $id);
        $players = $query->get()
            ->map(fn (TeamPlayer $tp) => $this->teamPlayerEditRowFormat($tp, $playingPlayerIds))
            ->values();

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'Team players fetched successfully',
            $players
        );
    }

    /**
     * @return array<int,true>
     */
    private function configuredPlayingPlayerIdSetForTeam(int $teamId): array
    {
        if (! Schema::hasTable('team_group_configurations')) {
            return [];
        }

        $team = LeagueTeam::query()->find($teamId);

        if (! $team) {
            return [];
        }

        return array_fill_keys($team->configuredPlayerIds(), true);
    }

    private function practiceTeamPlayerEditRowFormat(PracticeTeamPlayer $ptp): array
    {
        $name = ($ptp->name !== null && $ptp->name !== '')
            ? $ptp->name
            : ($ptp->TeamPlayer?->player?->name ?: 'N/A');

        $positions = [];
        if ($ptp->relationLoaded('positions') && $ptp->positions->isNotEmpty()) {
            $positions = $ptp->positions->map(fn ($pp) => [
                'id' => $pp->id,
                'position_name' => $pp->position_name,
            ])->values()->all();
        } elseif ($ptp->relationLoaded('TeamPlayer') && $ptp->TeamPlayer
            && $ptp->TeamPlayer->relationLoaded('teamPlayerPosition')
            && $ptp->TeamPlayer->teamPlayerPosition->isNotEmpty()) {
            $positions = $ptp->TeamPlayer->teamPlayerPosition->map(fn ($pp) => [
                'id' => $pp->id,
                'position_name' => $pp->position_name,
            ])->values()->all();
        } elseif ($ptp->TeamPlayer?->player?->relationLoaded('playerPosition')) {
            $positions = optional($ptp->TeamPlayer->player->playerPosition)?->map(fn ($pp) => [
                'id' => $pp->id,
                'position_name' => $pp->position_name,
            ])->values()->all() ?? [];
        }

        $dob = $ptp->dob;
        if ($dob) {
            try {
                $dob = Carbon::parse($dob)->format('Y-m-d');
            } catch (\Throwable $e) {
                $dob = null;
            }
        }

        $rpp = $ptp->rpp;

        return [
            'player_id' => $ptp->player_id,
            'name' => $name,
            'player' => $name,
            'target' => $ptp->position && $ptp->position !== '' ? $ptp->position : 'N/A',
            'number' => $ptp->number !== null && $ptp->number !== '' ? $ptp->number : 'N/A',
            'strength' => $ptp->strength,
            'height' => $ptp->height !== null && $ptp->height !== '' ? $ptp->height : 'N/A',
            'weight' => $ptp->weight !== null && $ptp->weight !== '' ? $ptp->weight : 'N/A',
            'dob_raw' => $dob,
            'speed' => $ptp->speed,
            'position' => $positions,
            'ofp' => $rpp !== null && $rpp !== '' ? $rpp : 'N/A',
            'size' => $ptp->size,
            'fromDatabase' => true,
        ];
    }

    private function teamPlayerEditRowFormat(TeamPlayer $tp, array $playingPlayerIds = []): array
    {
        $name = ($tp->name !== null && $tp->name !== '') ? $tp->name : ($tp->player?->name ?: 'N/A');

        $teamPlayerPositions = $tp->teamPlayerPosition;
        $positions = [];
        if ($teamPlayerPositions && $teamPlayerPositions->isNotEmpty()) {
            $positions = $teamPlayerPositions->map(function ($pp) {
                return [
                    'id' => $pp->id,
                    'position_name' => $pp->position_name,
                ];
            })->values()->all();
        } elseif ($tp->player && $tp->relationLoaded('player')) {
            $positions = optional($tp->player->playerPosition)?->map(function ($pp) {
                return [
                    'id' => $pp->id,
                    'position_name' => $pp->position_name,
                ];
            })->values()->all() ?? [];
        }

        $dob = $tp->dob;
        if ($dob) {
            try {
                $dob = Carbon::parse($dob)->format('Y-m-d');
            } catch (\Throwable $e) {
                $dob = null;
            }
        }

        $rpp = $tp->rpp;

        return [
            'id' => $tp->id,
            'player_id' => $tp->player_id,
            'name' => $name,
            'player' => $name,
            'target' => $tp->position && $tp->position !== '' ? $tp->position : 'N/A',
            'number' => $tp->number !== null && $tp->number !== '' ? $tp->number : 'N/A',
            'strength' => $tp->strength,
            'height' => $tp->height !== null && $tp->height !== '' ? $tp->height : 'N/A',
            'weight' => $tp->weight !== null && $tp->weight !== '' ? $tp->weight : 'N/A',
            'dob_raw' => $dob,
            'speed' => $tp->speed,
            'positions' => $positions,
            'ofp' => $rpp !== null && $rpp !== '' ? $rpp : 'N/A',
            'size' => $tp->size,
            'is_playing' => isset($playingPlayerIds[(int) $tp->id]),
            'fromDatabase' => true,
            'position' => $tp->position_value !== null && $tp->position_value !== '' ? $tp->position_value : 'N/A',
        ];
    }
     public function practiceTeamList($id)
    {
        $team = LeagueTeam::with(
            'teamplayer.player.playerPosition',
            'teamplayer.teamPlayerPosition',
        )->where('type', 1)->where('league_id', $id)->first();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Team view", $team);
    }


    public function update(Request $request, $id)
    {
        
      $players = json_decode($request->players, true) ?? [];
     

        DB::beginTransaction();
        try {

            $team = LeagueTeam::findOrFail($id);
            $team->team_name = $request->team_name;

            if ($request->hasFile('image')) {
                $path = uploadImage($request->image, 'uploads');
                $team->image = $path;
            }

            $team->save();

            $players = json_decode($request->players, true) ?? [];

            

            $existingPlayerIds = [];

            foreach ($players as $player) {

                $sanitize = function ($value) {
                    return in_array($value, ['N/A', '', null, 'null'], true) ? null : $value;
                };

                $data = [
                    'league_id' => $request->league_id ?? null,
                    'type' => $player['playertype'] ?? $player['target'] ?? null,
                    'name' => $sanitize($player['name'] ?? null),
                    'position_value' => $sanitize($player['position'] ?? null),
                    'position' => $sanitize($player['target'] ?? null),
                    'number' => $sanitize($player['number'] ?? null),
                    'size' => $sanitize($player['size'] ?? null),
                    'speed' => $sanitize($player['speed'] ?? 0),
                    'strength' => $sanitize($player['strength'] ?? null),
                    'weight' => $sanitize($player['weight'] ?? null),
                    'height' => $sanitize($player['height'] ?? null),
                    'rpp' => $sanitize($player['ofp'] ?? null),
                ];

                // DOB handling
                if (!empty($player['dob']) && $player['dob'] !== 'N/A') {
                    try {
                        $data['dob'] = \Carbon\Carbon::parse($player['dob'])->format('Y-m-d');
                    } catch (\Exception $e) {
                        $data['dob'] = null;
                    }
                } else {
                    $data['dob'] = null;
                }

                $record = TeamPlayer::updateOrCreate(
                    [
                        'team_id' => $team->id,
                        'player_id' => $player['player_id'] ?? null,
                    ],
                    $data
                );
                if (!empty($player['positions']) && is_array($player['positions'])) {

                // Delete old positions
                \DB::table('team_player_positions')
                    ->where('teamplayer_id', $record->id)
                    ->delete();

                // Insert new positions
                foreach ($player['positions'] as $index => $pos) {
                    \DB::table('team_player_positions')->insert([
                        'teamplayer_id' => $record->id,
                        'position_name' => $pos['position_name'] , // handle string or {text,value} format
                        'meta' => null,
                        'sort' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
                

                $existingPlayerIds[] = $record->player_id;
            }

            // Optional: Remove players not in request anymore
            TeamPlayer::where('team_id', $team->id)
                ->whereNotIn('player_id', $existingPlayerIds)
                ->delete();

            DB::commit();

            return new BaseResponse(
                STATUS_CODE_OK,
                STATUS_CODE_OK,
                "Team updated successfully",
                $team
            );

        } catch (\Throwable $th) {
            DB::rollBack();

            return new BaseResponse(
                STATUS_CODE_UNPROCESSABLE,
                STATUS_CODE_UNPROCESSABLE,
                $th->getMessage()
            );
        }
    }




    

    // public function update(Request $request, $id)
    // {
    //         DB::beginTransaction();
    //         try {
    //             // Update team info
    //             $team = LeagueTeam::find($id);
    //             $team->team_name = $request->team_name;

    //             if ($request->hasFile('image')) {
    //                 $path = uploadImage($request->image, 'uploads');
    //                 $team->image = $path;
    //             }

    //             $team->save();

                
    //             TeamPlayer::where('team_id', $id)->delete();

                
    //             $players = json_decode($request->players, true) ?? [];

    //             foreach ($players as $player) {
    //                 $t_player = new TeamPlayer();
    //                 $t_player->team_id = $team->id;
    //                 $t_player->player_id = $player['player_id'] ?? null;
    //                 $t_player->league_id = $request->league_id ?? null;
    //                 $t_player->type = $player['playertype'] ?? $player['target'] ?? null;

    //                 // Sanitize helper
    //                 $sanitize = function ($value) {
    //                     return in_array($value, ['N/A', '', null, 'null'], true) ? null : $value;
    //                 };

    //                 $t_player->name = $sanitize($player['name'] ?? null);
    //                 $t_player->position_value = $sanitize($player['position'] ?? null);
    //                 $t_player->position = $sanitize($player['target'] ?? null);
    //                 $t_player->number = $sanitize($player['number'] ?? null);
    //                 $t_player->size = $sanitize($player['size'] ?? null);
    //                 $t_player->speed = $sanitize($player['speed'] ?? 0);
    //                 $t_player->strength = $sanitize($player['strength'] ?? null);
    //                 $t_player->weight = $sanitize($player['weight'] ?? null);
    //                 $t_player->height = $sanitize($player['height'] ?? null);
    //                 $t_player->rpp = $sanitize($player['ofp'] ?? null);

    //                 // DOB parsing
    //                 if (!empty($player['dob']) && $player['dob'] !== 'N/A') {
    //                     try {
    //                         $t_player->dob = \Carbon\Carbon::parse($player['dob'])->format('Y-m-d');
    //                     } catch (\Exception $e) {
    //                         $t_player->dob = null;
    //                     }
    //                 } else {
    //                     $t_player->dob = null;
    //                 }

    //                 $t_player->save();
    //             }

    //             DB::commit();

    //             return new BaseResponse(
    //                 STATUS_CODE_OK,
    //                 STATUS_CODE_OK,
    //                 "Team updated successfully",
    //                 $team
    //             );

    //         } catch (\Throwable $th) {
    //             DB::rollBack();

    //             return new BaseResponse(
    //                 STATUS_CODE_UNPROCESSABLE,
    //                 STATUS_CODE_UNPROCESSABLE,
    //                 $th->getMessage()
    //             );
    //         }
    //     }


    // public function update(Request $request ,$id)
    // {

           
   
    //     DB::beginTransaction();
    //     try {

    //         $team = LeagueTeam::find($id);
    //         $team->team_name =  $request->team_name;
    //         if ($request->hasFile('image')) {
    //             $path =  uploadImage($request->image, 'uploads');
    //             $team->image = $path;
    //         }
    //         $team->save();

    //         TeamPlayer::where('team_id',$id)->delete();
    //         foreach ($request->playerid as $key => $id) {
                
    //             $t_player = new TeamPlayer();
    //             $t_player->team_id = $team->id;
    //             $t_player->player_id = $id;
    //             $t_player->league_id = $request->league_id;
              
    //             $t_player->type = $request->playertype[$key] ?? null;
              
    //             $sanitize = function ($value) {
    //                 return (in_array($value, ['N/A','',"",null,NULL,'null'], true)) ? null : $value;
    //              };

            
    //             $t_player->name = $sanitize($request->name[$key]);
    //             $t_player->position_value = $sanitize($request->position_value[$key]);
    //             $t_player->position = $sanitize($request->position[$key]);
    //             $t_player->number = $sanitize($request->number[$key]);
    //             $t_player->size = $sanitize($request->size[$key]);
             
    //             $t_player->speed = $sanitize($request->speed[$key]) ?? 0;

    //             $t_player->strength = $sanitize($request->strength[$key]);
    //             $t_player->weight = $sanitize($request->weight[$key]);
    //             $t_player->height = $sanitize($request->height[$key]);


               
    //             // $t_player->name = $sanitize($request->name[$key] ?? null);
    //             // $t_player->position_value = $sanitize($request->position_value[$key] ?? null);
    //             // $t_player->position = $sanitize($request->position[$key] ?? null);
    //             // $t_player->number = $sanitize($request->number[$key] ?? null);
    //             // $t_player->size = $sanitize($request->size[$key] ?? null);
    //             // $t_player->speed = $sanitize($request->speed[$key] ?? null);
    //             // $t_player->strength = $sanitize($request->strength[$key] ?? null);
    //             // $t_player->weight = $sanitize($request->weight[$key] ?? null);
    //             // $t_player->height = $sanitize($request->height[$key] ?? null);


                

    //             if (!empty($request->dob[$key]) && $request->dob[$key] !== 'N/A') {
    //                 try {
    //                     $t_player->dob = Carbon::parse($request->dob[$key])->format('Y-m-d');
    //                 } catch (\Exception $e) {
    //                     $t_player->dob = null;
    //                 }
    //             } else {
    //                 $t_player->dob = null;
    //             }
               
    //             $t_player->rpp = $sanitize($request->ofp[$key]?? null);

    //           try {
    //                 $t_player->save();
    //             } catch (\Exception $e) {
    //                 dd([
    //                 'message' => $e->getMessage(),
    //                 'line' => $e->getLine(),
    //                 'file' => $e->getFile(),
    //                 ]);
    //             }

                       
     
    //         }
              


    //         // foreach ($request->playerid as $key=>$id) {
    //         //     $t_player =  new TeamPlayer();
    //         //     $t_player->team_id = $team->id;
    //         //     $t_player->player_id = $id;
               
    //         //     $t_player->speed = 0;
    //         //     $t_player->league_id = $request->league_id;
    //         //     $t_player->type = $request->playertype[$key];
    //         //     $t_player->name = $request->name[$key];
    //         //     $t_player->position_value = $request->position_value[$key];
    //         //     $t_player->position = $request->position[$key];
    //         //     $t_player->number = $request->number[$key];
    //         //     $t_player->size = $request->size[$key];
    //         //     $t_player->speed = $request->speed[$key];
    //         //     $t_player->strength = $request->strength[$key];
    //         //     $t_player->weight = $request->weight[$key];
    //         //     $t_player->height = $request->height[$key];
    //         //     if (!empty($request->dob[$key])) {
    //         //         try {
    //         //         $t_player->dob = Carbon::parse($request->dob[$key])->format('Y-m-d');
    //         //         } catch (\Exception $e) {
    //         //         $t_player->dob = null;
    //         //         }
    //         //     }
    //         //     $t_player->ofp = $request->ofp[$key];
    //         //     $t_player->save();
    //         // }
    //         DB::commit();
    //         return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Team Update successFully", $team);
    //     } catch (\Throwable $th) {
    //         DB::rollBack();
    //         return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $th->getMessage());
    //     }
    // }

    public  function teamListByLeague(Request $request)
    {
        $team = LeagueTeam::where('league_id',$request->id)->get();
     
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Team List", $team);
    }

    public  function teamListForPlayMode(Request $request)
    {
        $team = LeagueTeam::where('league_id',$request->id)->where('is_practice',0)->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Team List", $team);
    }

    
}
