<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BenchPlayer;
use App\Models\ConfiguredPlayingTeamPlayer;
use Illuminate\Http\Request;
use App\Http\Responses\BaseResponse;
use Illuminate\Support\Facades\DB;
use App\Models\OpponentTeamPackage;
use App\Models\PersionalGrouping;



class BenchPlayerController extends Controller
{

    // public function index(Request $request,$teamId, $gameId)
    // {
    //        $isPractice = (bool) $request->get('isPractice', false);


    //         $configure = BenchPlayer::with('player.player')
    //             ->where('game_id', $gameId)
    //             ->where('team_id', $teamId)
    //             ->where('type', 'myteam')
    //             ->get()

    //             ->filter(function ($benchPlayer) {
    //                 return $benchPlayer->player && $benchPlayer->player->player;
    //             })
    //             ->map(function ($benchPlayer) {
    //                 return [
    //                     'bench_id'=>$benchPlayer->id,
    //                     'id' => $benchPlayer->player->id,
    //                     'player' => $benchPlayer->player,
    //                     'name' => $benchPlayer->player->player->name,
    //                     'number' => $benchPlayer->player->number,
    //                     'size' => $benchPlayer->player->size,
    //                     'position_value' => $benchPlayer->player->position_value,
    //                     'squad' => 3,
    //                     'position' => $benchPlayer->player->position,
    //                     'speed' => $benchPlayer->player->speed,
    //                     'strength' => $benchPlayer->player->strength,
    //                     'ofp' => $benchPlayer->player->ofp,
    //                     'rpp' => ($benchPlayer->rpp == 0)
    //                         ? ($benchPlayer->player?->rpp ?? 0)
    //                      : $benchPlayer->rpp,

    //                     'weight' => $benchPlayer->player->weight,
    //                     'height' => $benchPlayer->player->height,
    //                     'dob' => $benchPlayer->player->player->dob,
    //                 ];
    //             })
    //             ->values();


    //     return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "bench Player List",$configure);

    // }

//     public function index($teamId, $gameId, Request $request)
// {
//     $isPractice = (bool) $request->get('isPractice');


//     $benchPlayers = BenchPlayer::get()
//         ->where('game_id', $gameId)
//         ->where('team_id', $teamId)
//         ->where('type', 'myteam');

//     $configure = $benchPlayers
//         ->filter(function ($benchPlayer) use ($isPractice) {
//             if ($isPractice) {

//                 return !empty($benchPlayer->practice_player_id);
//             } else {

//                 return !empty($benchPlayer->player_id);
//             }
//         })
//         ->map(function ($benchPlayer) use ($isPractice) {


//             $id = $isPractice ? $benchPlayer->practice_player_id : $benchPlayer->player_id;
//             $name = $isPractice
//                 ? $benchPlayer->practice_player->name ?? 'Unknown'
//                 : $benchPlayer->player->player->name ?? 'Unknown';

//             $number = $isPractice
//                 ? $benchPlayer->practice_player->number ?? null
//                 : $benchPlayer->player->number ?? null;

//             $rpp = $benchPlayer->rpp ?: ($isPractice ? ($benchPlayer->practice_player->rpp ?? 0) : ($benchPlayer->player->rpp ?? 0));

//             return [
//                 'bench_id' => $benchPlayer->id,
//                 'id' => $id,
//                 'name' => $name,
//                 'number' => $number,
//                 'size' => $isPractice ? ($benchPlayer->practice_player->size ?? null) : $benchPlayer->player->size,
//                 'position_value' => $isPractice ? ($benchPlayer->practice_player->position_value ?? null) : $benchPlayer->player->position_value,
//                 'squad' => 3,
//                 'position' => $isPractice ? ($benchPlayer->practice_player->position ?? null) : $benchPlayer->player->position,
//                 'speed' => $isPractice ? ($benchPlayer->practice_player->speed ?? null) : $benchPlayer->player->speed,
//                 'strength' => $isPractice ? ($benchPlayer->practice_player->strength ?? null) : $benchPlayer->player->strength,
//                 'ofp' => $isPractice ? ($benchPlayer->practice_player->ofp ?? null) : $benchPlayer->player->ofp,
//                 'rpp' => $rpp,
//                 'weight' => $isPractice ? ($benchPlayer->practice_player->weight ?? null) : $benchPlayer->player->weight,
//                 'height' => $isPractice ? ($benchPlayer->practice_player->height ?? null) : $benchPlayer->player->height,
//                 'dob' => $isPractice ? ($benchPlayer->practice_player->dob ?? null) : $benchPlayer->player->player->dob,
//             ];
//         })
//         ->values();

//     return new BaseResponse(
//         STATUS_CODE_OK,
//         STATUS_CODE_OK,
//         "Bench Player List",
//         $configure
//     );
// }


public function index(Request $request, $gameId, $teamId)
{
    $isPractice = filter_var($request->get('isPractice', false), FILTER_VALIDATE_BOOLEAN);



    // Fetch all bench players for this team and game
    $benchPlayers = BenchPlayer::with('player.player','practice_player')->where('game_id', $gameId)
        ->where('team_id', $teamId)
        ->where('type', 'myteam')
        ->get();



    $configure = $benchPlayers
        ->filter(function ($benchPlayer) use ($isPractice) {
            // If practice mode, check practice_player exists
            if ($isPractice) {
                return !empty($benchPlayer->practice_player_id);
            }
            // Normal mode, check player exists
              return $benchPlayer->player && $benchPlayer->player->player;
        })
        ->map(function ($benchPlayer) use ($isPractice) {

          if ($isPractice) {
                        $practice = $benchPlayer->practice_player;

                        $id = $benchPlayer->practice_player_id;
                        $player = null;
                        $name = $practice?->name ?? 'Unknown';
                        $number = $practice?->number ?? null;
                        $size = $practice?->size ?? null;
                        $position_value = $benchPlayer?->position ?? null;
                        $position = $practice?->position ?? null;
                        $speed = $practice?->speed ?? null;
                        $strength = $practice?->strength ?? null;
                        $ofp = $practice?->ofp ?? null;
                        $weight = $practice?->weight ?? null;
                        $height = $practice?->height ?? null;
                        $dob = $practice?->dob ?? null;
                        $rpp = $benchPlayer->rpp ?: ($practice?->rpp ?? 0);
}
 else {
                // Normal players
                $id = $benchPlayer->player->id;
                $player = $benchPlayer->player;
                $name = $benchPlayer->player->player->name;
                $number = $benchPlayer->player->number;
                $size = $benchPlayer->player->size;
                $position_value = $benchPlayer->position;
                $position = $benchPlayer->player->position;
                $speed = $benchPlayer->player->speed;
                $strength = $benchPlayer->player->strength;
                $ofp = $benchPlayer->player->ofp;
                $weight = $benchPlayer->player->weight;
                $height = $benchPlayer->player->height;
                $dob = $benchPlayer->player->player->dob;
                $rpp = ($benchPlayer->rpp == 0) ? ($benchPlayer->player?->rpp ?? 0) : $benchPlayer->rpp;
            }


            return [
                'bench_id' => $benchPlayer->id,
                'id' => $id,
                'player' => $player,
                'name' => $name,
                'number' => $number,
                'size' => $size,
                'position_value' => $position_value,
                'squad' => 3,
                // Frontend Offense/Defense tabs filter by `position` === offence/deffence (side).
                'position' => $benchPlayer->player_type ?: $position,
                'speed' => $speed,
                'strength' => $strength,
                'ofp' => $ofp,
                'rpp' => $rpp,
                'weight' => $weight,
                'height' => $height,
                'dob' => $dob,
            ];
        })
        ->values(); // reindex


    return new BaseResponse(
        STATUS_CODE_OK,
        STATUS_CODE_OK,
        "Bench Player List",
        $configure
    );
}



    public function getCount($gameId)
    {
        $benchPlayerCount = BenchPlayer::where('game_id', $gameId)->count();

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            "Bench player count fetched successfully",
            ['count' => $benchPlayerCount] // wrap count in an array/object
        );
    }

     public function getOpponentBenchPlayers(Request $request, $gameId, $teamId)
{
    // Get isPractice flag from query string
    $isPractice = filter_var($request->get('isPractice', false), FILTER_VALIDATE_BOOLEAN);
    \Log::info(['isPractice' => $isPractice]);

    // Fetch all opponent bench players
    $benchPlayers = BenchPlayer::with('player.player', 'practice_player')
        ->where('game_id', $gameId)
        ->where('team_id', $teamId)
        ->where('type', 'opponent')
        ->get();

    $configure = $benchPlayers
        ->filter(function ($benchPlayer) use ($isPractice) {
            // If practice mode, check practice_player exists
            if ($isPractice) {
                return !empty($benchPlayer->practice_player_id);
            }
            // Normal mode, check player exists
            return $benchPlayer->player && $benchPlayer->player->player;
        })
        ->map(function ($benchPlayer) use ($isPractice) {
            if ($isPractice) {
                $practice = $benchPlayer->practice_player;

                $id = $benchPlayer->practice_player_id;
                $player = null;
                $name = $practice?->name ?? 'Unknown';
                $number = $practice?->number ?? null;
                $size = $practice?->size ?? null;
                $position_value = $benchPlayer?->position ?? null;
                $position = $practice?->position ?? null;
                $speed = $practice?->speed ?? null;
                $strength = $practice?->strength ?? null;
                $ofp = $practice?->ofp ?? null;
                $weight = $practice?->weight ?? null;
                $height = $practice?->height ?? null;
                $dob = $practice?->dob ?? null;
                $rpp = $benchPlayer->rpp ?: ($practice?->rpp ?? 0);
            } else {
                $id = $benchPlayer->player->id ?? null;
                $player = $benchPlayer->player;
                $name = $benchPlayer->player->player->name ?? null;
                $number = $benchPlayer->player->number ?? null;
                $size = $benchPlayer->player->size ?? null;
                $position_value = $benchPlayer->position ?? null;
                $position = $benchPlayer->player->position ?? null;
                $speed = $benchPlayer->player->speed ?? null;
                $strength = $benchPlayer->player->strength ?? null;
                $ofp = $benchPlayer->player->ofp ?? null;
                $weight = $benchPlayer->player->weight ?? null;
                $height = $benchPlayer->player->height ?? null;
                $dob = $benchPlayer->player->player->dob ?? null;
                $rpp = ($benchPlayer->rpp == 0) ? ($benchPlayer->player?->rpp ?? 0) : $benchPlayer->rpp;
            }

            return [
                'bench_id' => $benchPlayer->id,
                'id' => $id,
                'player' => $player,
                'name' => $name,
                'number' => $number,
                'size' => $size,
                'position_value' => $position_value,
                'squad' => 3,
                'position' => $benchPlayer->player_type ?: $position,
                'speed' => $speed,
                'strength' => $strength,
                'ofp' => $ofp,
                'rpp' => $rpp,
                'weight' => $weight,
                'height' => $height,
                'dob' => $dob,
            ];
        })
        ->values(); // reindex

    \Log::info(['opponent bench data' => $configure]);

    return new BaseResponse(
        STATUS_CODE_OK,
        STATUS_CODE_OK,
        "Opponent Bench Player List",
        $configure
    );
}


    //  public function getOpponentBenchPlayers($teamId, $gameId)
    // {


    //    $configure = BenchPlayer::with('player.player','practice_player')
    //     ->where('game_id', $gameId)
    //     ->where('team_id', $teamId)
    //     ->where('type', 'opponent')
    //     ->get()
    //     ->map(function ($benchPlayer) {
    //         return [
    //             'bench_id'=>$benchPlayer->id,
    //             'id' => optional($benchPlayer->player)->id ?? null,
    //             'player' => $benchPlayer->player,
    //             'name' => optional($benchPlayer->player)->player->name ?? null,
    //             'number' => optional($benchPlayer->player)->number ?? null,
    //             'size' => optional($benchPlayer->player)->size ?? null,
    //             'squad' => 3,
    //             'position_value' => optional($benchPlayer->player)->position_value ?? null,
    //             'position' => optional($benchPlayer->player)->position ?? null,
    //             'speed' => optional($benchPlayer->player)->speed ?? null,
    //             'strength' => optional($benchPlayer->player)->strength ?? null,
    //             'ofp' => optional($benchPlayer->player)->ofp ?? null,
    //             'rpp' => ($benchPlayer->rpp == 0)
    //                         ? ($benchPlayer->player?->rpp ?? 0)
    //                      : $benchPlayer->rpp,
    //             'weight' => optional($benchPlayer->player)->weight ?? null,
    //             'height' => optional($benchPlayer->player)->height ?? null,
    //             'dob' => optional($benchPlayer->player)->player->dob ?? null,

    //             // Add other fields as needed
    //         ];
    //     });

    //      \Log::info(['opponent becnh'=>  $configure]);
    //     return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "bench Player List",$configure);

    // }


    public function opponentBenchPlayerStore(Request $request)
    {
        $benchData   = $request->get('benchPlayers');
        $team_id     = $request->get('teamId');
        $league_id   = (int) $request->get('leagueId');
        $game_id     = (int) $request->get('gameId');
        $player_type = $request->get('playerType');
        $isPractice  = (bool) $request->get('isPractice');
        $playerColumn = $isPractice ? 'practice_player_id' : 'player_id';

        $benchData = is_array($benchData) ? $benchData : [];
        $incomingIds = array_values(array_filter(array_map(
            fn ($it) => (int) ($it['id'] ?? 0),
            $benchData
        )));

        $savedPlayers = [];
        $slotType = strtolower((string) $player_type) === 'offence' ? 'offensive' : 'defensive';
        $configureGameType = $isPractice ? 2 : 1;

        DB::transaction(function () use (
            $benchData,
            $team_id,
            $player_type,
            $league_id,
            $game_id,
            $isPractice,
            $playerColumn,
            $incomingIds,
            $slotType,
            $configureGameType,
            &$savedPlayers
        ) {
            if ($incomingIds !== []) {
                BenchPlayer::where('game_id', $game_id)
                    ->where('team_id', $team_id)
                    ->where('type', 'opponent')
                    ->where('player_type', $player_type)
                    ->whereIn($playerColumn, $incomingIds)
                    ->delete();
            }

            foreach ($benchData as $item) {
                $savedPlayers[] = [
                    'player_id'          => $isPractice ? null : $item['id'],
                    'practice_player_id' => $isPractice ? $item['id'] : null,
                    'game_id'            => $game_id,
                    'team_id'            => $team_id,
                    'position'           => $item['positionSelect'] ?? null,
                    'league_id'          => $league_id,
                    'type'               => 'opponent',
                    'player_type'        => $player_type,
                    'rpp'                => $item['rpp'] ?? 0,
                ];
            }

            if ($savedPlayers !== []) {
                BenchPlayer::insert($savedPlayers);
            }

            foreach ($incomingIds as $playerId) {
                $existing = ConfiguredPlayingTeamPlayer::query()
                    ->where('team_id', $team_id)
                    ->where('match_id', $game_id)
                    ->where($playerColumn, $playerId)
                    ->first();

                if ($existing) {
                    if (strtolower((string) $existing->type) !== 'special') {
                        $existing->update([
                            'type'      => $slotType,
                            'game_type' => $configureGameType,
                        ]);
                    }
                } else {
                    ConfiguredPlayingTeamPlayer::create([
                        'team_id'     => $team_id,
                        'match_id'    => $game_id,
                        $playerColumn => $playerId,
                        'team_type'   => 2,
                        'type'        => $slotType,
                        'game_type'   => $configureGameType,
                    ]);
                }
            }
        });

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Bench Player Add Successfully", $savedPlayers);
    }

 public function rppUpdate(Request $request, $id)
{
           $data=$request->all();
           $player = BenchPlayer::findOrFail($id);
            $player->update([
                'rpp' => $data['rpp']
            ]);

           return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "update rpp  Successfully", []);
    }
   public function shufflePlayers(Request $request)
    {



        $offensePlayers = $request->input('offensePlayers', []);

        $benchPlayers   = $request->input('benchPlayers', []);
        $teamId         = $request->input('teamId');
        $gameId         = $request->input('gameId');
        $playerType     = $request->input('playerType');
        $team_type     = $request->input('team_type');
        $leagueId     = $request->input('leagueId');
        $type     = $request->input('type');

        $isPractice = $request->input('is_practice', false);
        $playerColumn = $isPractice ? 'practice_player_id' : 'player_id';
        // $group_id     = $request->input('group_id');





        $offenseIds = array_values(array_filter(array_map(
            fn ($player) => (int) ($player['id'] ?? 0),
            is_array($offensePlayers) ? $offensePlayers : []
        )));
        $benchIds = array_values(array_filter(array_map(
            fn ($player) => (int) ($player['id'] ?? 0),
            is_array($benchPlayers) ? $benchPlayers : []
        )));

        // Configured-roster slot type for the players coming on-field. Without this, rows are
        // inserted with type=NULL and the match-end roster prune cannot tell which side they
        // belong to. Keep this in sync with the values written by ConfigureController::store.
        $slotType = strtolower((string) $playerType) === 'offence' ? 'offensive' : 'defensive';
        $configureGameType = $isPractice ? 2 : 1;

        $offensePlayerMap = collect(is_array($offensePlayers) ? $offensePlayers : [])
            ->keyBy(fn ($player) => (int) ($player['id'] ?? 0));

        DB::transaction(function () use (
            $offenseIds,
            $offensePlayerMap,
            $benchIds,
            $teamId,
            $leagueId,
            $type,
            $gameId,
            $playerType,
            $team_type,
            $playerColumn,
            $slotType,
            $configureGameType,
            $isPractice
        ) {
            // Players leaving the field (current lineup minus the incoming group): drop their
            // on-field bench rows and clear the configure slot. Do not re-insert bench rows
            // with the same player_type — that kept replaced players on the Defense/Offense tab.
            $leavingFieldIds = array_values(array_diff($benchIds, $offenseIds));

            if ($leavingFieldIds !== []) {
                BenchPlayer::where('team_id', $teamId)
                    ->where('game_id', $gameId)
                    ->where('player_type', $playerType)
                    ->whereIn($playerColumn, $leavingFieldIds)
                    ->delete();

                ConfiguredPlayingTeamPlayer::where('team_id', $teamId)
                    ->where('match_id', $gameId)
                    ->whereIn($playerColumn, $leavingFieldIds)
                    ->where(function ($q) {
                        $q->whereNull('type')->orWhere('type', '!=', 'special');
                    })
                    ->update(['type' => null]);
            }

            // Players coming/staying on the field: ensure on-field bench rows + configure slot.
            foreach ($offenseIds as $playerId) {
                $player = $offensePlayerMap->get($playerId, []);
                $selectedPosition = $player['selected_position'] ?? null;
                $rpp = $player['rpp'] ?? 0;

                $existingBench = BenchPlayer::where('team_id', $teamId)
                    ->where('game_id', $gameId)
                    ->where('type', $type)
                    ->where('player_type', $playerType)
                    ->where($playerColumn, $playerId)
                    ->first();

                if ($existingBench) {
                    $existingBench->update([
                        'position' => $selectedPosition,
                        'rpp'        => $rpp,
                    ]);
                } else {
                    BenchPlayer::create([
                        'team_id'            => $teamId,
                        'game_id'            => $gameId,
                        'player_id'          => $isPractice ? null : $playerId,
                        'practice_player_id' => $isPractice ? $playerId : null,
                        'league_id'          => $leagueId,
                        'type'               => $type,
                        'player_type'        => $playerType,
                        'position'           => $selectedPosition,
                        'rpp'                => $rpp,
                    ]);
                }

                $existing = ConfiguredPlayingTeamPlayer::query()
                    ->where('team_id', $teamId)
                    ->where('match_id', $gameId)
                    ->where($playerColumn, $playerId)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'type'      => $slotType,
                        'team_type' => $team_type,
                        'game_type' => $configureGameType,
                    ]);
                } else {
                    ConfiguredPlayingTeamPlayer::create([
                        'team_id'      => $teamId,
                        'match_id'     => $gameId,
                        $playerColumn  => $playerId,
                        'team_type'    => $team_type,
                        'type'         => $slotType,
                        'game_type'    => $configureGameType,
                    ]);
                }
            }
        });

        $user = auth()->user();
        $headCoachId = $user->head_coach_id ?? $user->id;
        broadcast(new \App\Events\PlayerSubstituted($headCoachId, $gameId, $teamId))->toOthers();

         return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player Shuffle  Successfully", []);

    }

    public function store(Request $request)
    {
        $benchData   = $request->get('benchPlayers');
        $team_id     = $request->get('teamId');
        $player_type = $request->get('playerType');
        $league_id   = (int) $request->get('leagueId');
        $game_id     = (int) $request->get('gameId');
        $isPractice  = (bool) $request->get('isPractice');
        $playerColumn = $isPractice ? 'practice_player_id' : 'player_id';

        $benchData = is_array($benchData) ? $benchData : [];
        $incomingIds = array_values(array_filter(array_map(
            fn ($it) => (int) ($it['id'] ?? 0),
            $benchData
        )));

        $savedPlayers = [];
        $slotType = strtolower((string) $player_type) === 'offence' ? 'offensive' : 'defensive';
        $configureGameType = $isPractice ? 2 : 1;

        DB::transaction(function () use (
            $benchData,
            $team_id,
            $player_type,
            $league_id,
            $game_id,
            $isPractice,
            $playerColumn,
            $incomingIds,
            $slotType,
            $configureGameType,
            &$savedPlayers
        ) {
            if ($incomingIds !== []) {
                // Avoid duplicate bench rows if the same player is re-assigned to the
                // same side (e.g. Assign Offense triggered twice in a row).
                BenchPlayer::where('game_id', $game_id)
                    ->where('team_id', $team_id)
                    ->where('type', 'myteam')
                    ->where('player_type', $player_type)
                    ->whereIn($playerColumn, $incomingIds)
                    ->delete();
            }

            foreach ($benchData as $item) {
                $savedPlayers[] = [
                    'player_id'          => $isPractice ? null : $item['id'],
                    'practice_player_id' => $isPractice ? $item['id'] : null,
                    'position'           => $item['positionSelect'] ?? null,
                    'game_id'            => $game_id,
                    'team_id'            => $team_id,
                    'league_id'          => $league_id,
                    'type'               => 'myteam',
                    'player_type'        => $player_type,
                    'rpp'                => $item['rpp'] ?? 0,
                ];
            }

            if ($savedPlayers !== []) {
                BenchPlayer::insert($savedPlayers);
            }

            // Mark these players on-field for this side in the configure roster.
            foreach ($incomingIds as $playerId) {
                $existing = ConfiguredPlayingTeamPlayer::query()
                    ->where('team_id', $team_id)
                    ->where('match_id', $game_id)
                    ->where($playerColumn, $playerId)
                    ->first();

                if ($existing) {
                    if (strtolower((string) $existing->type) !== 'special') {
                        $existing->update([
                            'type'      => $slotType,
                            'game_type' => $configureGameType,
                        ]);
                    }
                } else {
                    ConfiguredPlayingTeamPlayer::create([
                        'team_id'     => $team_id,
                        'match_id'    => $game_id,
                        $playerColumn => $playerId,
                        'team_type'   => 1,
                        'type'        => $slotType,
                        'game_type'   => $configureGameType,
                    ]);
                }
            }
        });

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Bench Player Add Successfully", $savedPlayers);
    }

    public function createMyTeamForPlayMode(Request $request)
    {
        DB::beginTransaction();

        try {
            // The frontend names are inverted relative to direction of travel.
            // After addSubstitute() locally swaps the two player objects:
            //   offenseData = player NOW shown in the Offense/Defense lineup tab
            //                 (was the on-field row before; goes to bench queue).
            //   benchData   = player NOW shown in the Bench tab
            //                 (was the bench queue row before; goes on the field).
            $offenseData = $request->get('offenseData'); // outgoing: field -> bench queue
            $benchData   = $request->get('benchData');   // incoming: bench queue -> field

            $team_id    = $request->get('teamId');
            $league_id  = (int) $request->get('leagueId');
            $game_id    = (int) $request->get('gameId');
            $position   = $request->get('position');
            $rpp        = $request->get('rpp');
            $isPractice = $request->get('is_practice');
            $playerColumn = $isPractice ? 'practice_player_id' : 'player_id';

            $outgoingId = (int) ($offenseData['id'] ?? 0);
            $incomingId = (int) ($benchData['id']   ?? 0);

            if ($outgoingId <= 0 || $incomingId <= 0) {
                DB::rollBack();
                return response()->json([
                    'status'  => false,
                    'message' => 'Substitution failed: missing player ids',
                ], 422);
            }

            /*
            |-----------------------------------------
            | Configure roster: preserve both rows.
            |-----------------------------------------
            | Old behaviour swapped the player id on the outgoing row, which
            | dropped one player out of CPT entirely and made personal groupings
            | containing them collapse to (0). Keep both rows and only flip the
            | on-field slot type (mirrors shufflePlayers).
            */

            $outgoingCpt = ConfiguredPlayingTeamPlayer::where('match_id', $game_id)
                ->where('team_id', $team_id)
                ->where($playerColumn, $outgoingId)
                ->first();

            $slotType      = $outgoingCpt?->type;
            $teamTypeValue = $outgoingCpt?->team_type ?? 1;
            $gameTypeValue = $outgoingCpt?->game_type ?? ($isPractice ? 2 : 1);

            // Outgoing player heads to the bench queue: keep in roster, clear slot.
            // 'special' slots (kickers/punters etc.) are not touched.
            if ($outgoingCpt && strtolower((string) $outgoingCpt->type) !== 'special') {
                $outgoingCpt->update(['type' => null]);
            }

            // Incoming player heads to the field: ensure a CPT row carries the slot.
            $incomingCpt = ConfiguredPlayingTeamPlayer::where('match_id', $game_id)
                ->where('team_id', $team_id)
                ->where($playerColumn, $incomingId)
                ->first();

            if ($incomingCpt) {
                $incomingCpt->update([
                    'type'      => $slotType,
                    'team_type' => $incomingCpt->team_type ?: $teamTypeValue,
                    'game_type' => $incomingCpt->game_type ?: $gameTypeValue,
                ]);
            } else {
                ConfiguredPlayingTeamPlayer::create([
                    'team_id'      => $team_id,
                    'match_id'     => $game_id,
                    $playerColumn  => $incomingId,
                    'type'         => $slotType,
                    'team_type'    => $teamTypeValue,
                    'game_type'    => $gameTypeValue,
                ]);
            }

            /*
            |-----------------------------------------
            | Bench (offense_defense_players) queue.
            |-----------------------------------------
            | Re-use the incoming player's queue row so position/rpp history flows
            | through to the outgoing player. Fall back to creating a row when the
            | incoming player did not already have one (defensive substitution
            | initiated from an unusual state).
            */

            $incomingBench = BenchPlayer::where('game_id', $game_id)
                ->where('team_id', $team_id)
                ->where($playerColumn, $incomingId)
                ->first();

            if ($incomingBench) {
                $incomingBench->update([
                    $playerColumn => $outgoingId,
                    'position'    => $position,
                    'rpp'         => $rpp,
                ]);
            } else {
                $playerTypeForBench = strtolower((string) $slotType) === 'defensive'
                    ? 'deffence'
                    : 'offence';

                BenchPlayer::create([
                    'game_id'     => $game_id,
                    'team_id'     => $team_id,
                    'league_id'   => $league_id,
                    $playerColumn => $outgoingId,
                    'type'        => 'myteam',
                    'player_type' => $playerTypeForBench,
                    'position'    => $position,
                    'rpp'         => $rpp,
                ]);
            }

            DB::commit();

            $user = auth()->user();
            $headCoachId = $user->head_coach_id ?? $user->id;
            broadcast(new \App\Events\PlayerSubstituted($headCoachId, $game_id, $team_id))->toOthers();

            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Bench Player Add Successfully", []);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => 'Substitution failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

        // public function createMyTeamForPlayMode(Request $request)
        // {

        // DB::beginTransaction();

        // try {

        //     $configurePlayers =$request->get('configurePlayers');
        //     $benchData=$request->get('benchPlayers');

        //     $team_id=$request->get('teamId');
        //     $league_id=(int) $request->get('leagueId');
        //     $game_id=(int) $request->get('gameId');
        //     $isPractice = $request->get('is_practice');
        //     $playerColumn = $isPractice ? 'practice_player_id' : 'player_id';

        //     $savedPlayers=[];
        //     $benchPlayers=[];
        //     foreach ($configurePlayers as $index => $item) {
        //         ConfiguredPlayingTeamPlayer::where('match_id', $game_id)
        //         ->where('team_id', $team_id)

        //         ->delete();
        //         $savedPlayers[] = [
        //             $playerColumn => $item['id'],
        //             'match_id' =>   $game_id,
        //             'team_id' => $team_id,
        //             'team_type' => 1,
        //             ];
        //     }
        //     ConfiguredPlayingTeamPlayer::insert($savedPlayers);
        //     foreach ($benchData as $index => $item) {
        //         BenchPlayer::where('game_id', $game_id)
        //         ->where('team_id', $team_id)

        //         ->delete();
        //         $benchPlayers[] = [
        //             $playerColumn => $item['id'],
        //             'game_id' =>   $game_id,
        //             'team_id' => $team_id,
        //             'league_id' =>  $league_id,
        //             'type' => 'myteam',
        //             ];
        //        }
        //        BenchPlayer::insert($benchPlayers);
        //        DB::commit();
        //        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Bench Player Add Successfully", [
        //                 'configured' => $savedPlayers,
        //                 'bench' => $benchPlayers,
        //         ]);
        //       } catch (\Exception $e) {
        //             DB::rollBack();
        //             return response()->json([
        //                 'message' => 'Substitution failed.',
        //                 'error' => $e->getMessage(),
        //             ], 500);
        //       }
        // }

        // public function createOpponentTeamForPlayMode(Request $request)
        // {

        // DB::beginTransaction();

        // try {

        //     $configurePlayers =$request->get('configurePlayers');
        //     $benchData=$request->get('benchPlayers');
        //     $team_id=$request->get('teamId');
        //     $league_id=(int) $request->get('leagueId');
        //     $game_id=(int) $request->get('gameId');
        //     $isPractice = $request->get('is_practice');
        //     $playerColumn = $isPractice ? 'practice_player_id' : 'player_id';
        //     $savedPlayers=[];
        //     $benchPlayers=[];
        //     foreach ($configurePlayers as $index => $item) {
        //         ConfiguredPlayingTeamPlayer::where('match_id', $game_id)
        //         ->where('team_id', $team_id)

        //         ->delete();
        //         $savedPlayers[] = [
        //              $playerColumn => $item['id'],
        //             'match_id' =>   $game_id,
        //             'team_id' => $team_id,
        //             'team_type' => 2,
        //             ];
        //     }
        //     ConfiguredPlayingTeamPlayer::insert($savedPlayers);
        //     foreach ($benchData as $index => $item) {
        //         BenchPlayer::where('game_id', $game_id)
        //         ->where('team_id', $team_id)

        //         ->delete();
        //         $benchPlayers[] = [
        //              $playerColumn => $item['id'],
        //             'game_id' =>   $game_id,
        //             'team_id' => $team_id,
        //             'league_id' =>  $league_id,
        //             'type' => 'opponent',
        //             ];
        //        }
        //        BenchPlayer::insert($benchPlayers);
        //        DB::commit();
        //        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Bench Player Add Successfully", [
        //                 'configured' => $savedPlayers,
        //                 'bench' => $benchPlayers,
        //         ]);
        //       } catch (\Exception $e) {
        //             DB::rollBack();
        //             return response()->json([
        //                 'message' => 'Substitution failed.',
        //                 'error' => $e->getMessage(),
        //             ], 500);
        //       }
        // }


    public function createOpponentTeamForPlayMode(Request $request)
    {
        DB::beginTransaction();

        try {
            // See createMyTeamForPlayMode for the offenseData / benchData semantics —
            // they're inverted relative to the direction each player moves.
            $offenseData = $request->get('offenseData'); // outgoing: field -> bench queue
            $benchData   = $request->get('benchData');   // incoming: bench queue -> field

            $team_id    = $request->get('teamId');
            $league_id  = (int) $request->get('leagueId');
            $game_id    = (int) $request->get('gameId');
            $position   = $request->get('position');
            $rpp        = $request->get('rpp');
            $isPractice = $request->get('is_practice');
            $playerColumn = $isPractice ? 'practice_player_id' : 'player_id';

            $outgoingId = (int) ($offenseData['id'] ?? 0);
            $incomingId = (int) ($benchData['id']   ?? 0);

            if ($outgoingId <= 0 || $incomingId <= 0) {
                DB::rollBack();
                return response()->json([
                    'status'  => false,
                    'message' => 'Substitution failed: missing player ids',
                ], 422);
            }

            $outgoingCpt = ConfiguredPlayingTeamPlayer::where('match_id', $game_id)
                ->where('team_id', $team_id)
                ->where($playerColumn, $outgoingId)
                ->first();

            $slotType      = $outgoingCpt?->type;
            $teamTypeValue = $outgoingCpt?->team_type ?? 2;
            $gameTypeValue = $outgoingCpt?->game_type ?? ($isPractice ? 2 : 1);

            if ($outgoingCpt && strtolower((string) $outgoingCpt->type) !== 'special') {
                $outgoingCpt->update(['type' => null]);
            }

            $incomingCpt = ConfiguredPlayingTeamPlayer::where('match_id', $game_id)
                ->where('team_id', $team_id)
                ->where($playerColumn, $incomingId)
                ->first();

            if ($incomingCpt) {
                $incomingCpt->update([
                    'type'      => $slotType,
                    'team_type' => $incomingCpt->team_type ?: $teamTypeValue,
                    'game_type' => $incomingCpt->game_type ?: $gameTypeValue,
                ]);
            } else {
                ConfiguredPlayingTeamPlayer::create([
                    'team_id'      => $team_id,
                    'match_id'     => $game_id,
                    $playerColumn  => $incomingId,
                    'type'         => $slotType,
                    'team_type'    => $teamTypeValue,
                    'game_type'    => $gameTypeValue,
                ]);
            }

            $incomingBench = BenchPlayer::where('game_id', $game_id)
                ->where('team_id', $team_id)
                ->where($playerColumn, $incomingId)
                ->first();

            if ($incomingBench) {
                $incomingBench->update([
                    $playerColumn => $outgoingId,
                    'position'    => $position,
                    'rpp'         => $rpp,
                ]);
            } else {
                $playerTypeForBench = strtolower((string) $slotType) === 'defensive'
                    ? 'deffence'
                    : 'offence';

                BenchPlayer::create([
                    'game_id'     => $game_id,
                    'team_id'     => $team_id,
                    'league_id'   => $league_id,
                    $playerColumn => $outgoingId,
                    'type'        => 'opponent',
                    'player_type' => $playerTypeForBench,
                    'position'    => $position,
                    'rpp'         => $rpp,
                ]);
            }

            DB::commit();

            $user = auth()->user();
            $headCoachId = $user->head_coach_id ?? $user->id;
            broadcast(new \App\Events\PlayerSubstituted($headCoachId, $game_id, $team_id))->toOthers();

            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Bench Player Add Successfully", []);

        } catch (\Exception $e) {
            \Log::info($e->getMessage());

            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => 'Substitution failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $benchPlayer = BenchPlayer::findOrFail($id);
        $benchPlayer->delete();

        return response()->json(['message' => 'Bench player removed.']);
    }

    public function addOpponentPackage(Request $request)
    {

         $package = OpponentTeamPackage::createPackage($request->all());
        if (isset($package['grouping_count'])) {
            $package['count'] = $package['grouping_count'];
            unset($package['grouping_count']);
        }

         return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Bench Player Add Successfully",  $package );
    }


      public function getOpponentTeamPackages($gameId ,$teamId)
     {


            $packages = OpponentTeamPackage::getPackagesForOpponent($gameId,$teamId);
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "package lists",  $packages );
        }




}
