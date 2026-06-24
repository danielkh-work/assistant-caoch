<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\Player;
use App\Models\PracticeTeamPlayer;
use App\Models\PracticeTeamPlayerPosition;
use App\Models\PersionalGrouping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\TeamPlayer;

class PlayerController extends Controller
{
    //  comment By noor
    // public  function store(Request $request)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $player = new Player();
    //         $player->name = $request->name;
    //         $player->user_id = auth()->user()->id;
    //         $player->number=  $request->number;
    //         $player->position = $request->position;
    //         $player->size= $request->size;
    //         $player->speed= $request->speed;
    //         $player->strength =  $request->strength;
    //         if($request->hasFile('image'))
    //         {
    //             $path =  uploadImage($request->image,'player');
    //             $player->image =$path;
    //         }
    //         $player->save();
    //         DB::commit();
    //         return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player Added SuccessFully ", $player);
    //     } catch (\Throwable $th) {
    //         DB::rollBack();
    //         return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
    //     }
    // }
    public  function store(Request $request)
    {



        DB::beginTransaction();
        try {

          $existingPlayer = Player::where('name', $request->name)
                        ->where('number', $request->number)
                        ->first();
        if ($existingPlayer) {
              return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "A player with this name and number already exists.",$existingPlayer);
           }
           $player = new Player();
           $type= $request->type;

            if ($type === 'player') {
                 $player->user_id = auth()->id();

            } elseif ($type === 'league') {

                $player->league_id = $request->league_id;

            } elseif ($type === 'team') {

                $player->user_id = auth()->id();
            }else{

            }

            $player->name = $request->name;
            $player->number=  $request->number;
            $player->position = $request->position;
            $player->size= $request->size;
            $player->speed= 4;
            $player->weight= $request->weight;
            $player->height= $request->height;
            $player->dob= $request->dob;
            $player->rpp= $request->ofp;

            $player->strength =  $request->strength;
            $player->position_value =  null;
            if($request->hasFile('image'))
            {
                $path =  uploadImage($request->image,'player');
                $player->image =$path;
            }
            $player->save();

            $resolvedPositionNames = [];
            if ($request->has('positionValue') && is_array($request->positionValue)) {
                foreach ($request->positionValue as $pos) {
                    $name = is_array($pos)
                        ? ($pos['text'] ?? $pos['value'] ?? null)
                        : $pos;
                    if ($name === null || $name === '') {
                        continue;
                    }
                    $resolvedPositionNames[] = $name;
                }
            }

            foreach ($resolvedPositionNames as $index => $name) {
                DB::table('player_positions')->insert([
                    'player_id' => $player->id,
                    'position_name' => $name,
                    'meta' => null,
                    'sort' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $player->load('playerPosition');
            //    if($type === 'team'){
                    $teamPlayerPositionValue = $resolvedPositionNames[0] ?? null;

                    $teamPlayerId = DB::table('team_players')->insertGetId([
                        'player_id' => $player->id,
                        'team_id' => $request->team_id,
                        'name' => $player->name,
                        'number' => $player->number,
                        'position' => $player->position,
                        'size' => $player->size,
                        'speed' => $player->speed,
                        'strength' => $player->strength,
                        'weight' => $player->weight,
                        'height' => $player->height,
                        'dob' => $player->dob,
                        'image' => $player->image,
                        'position_value' => $teamPlayerPositionValue,
                        'rpp' => $player->rpp,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

            foreach ($resolvedPositionNames as $index => $name) {
                DB::table('team_player_positions')->insert([
                    'teamplayer_id' => $teamPlayerId,
                    'position_name' => $name,
                    'meta' => null,
                    'sort' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            // }
            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player Added SuccessFully ", $player);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
        }
    }
    public  function addOpenPlayer(Request $request)
    {

        \Log::info(['team'=>$request->all()]);
        DB::beginTransaction();
        try {

          $existingPlayer = Player::where('name', $request->name)
                        ->where('number', $request->number)
                        ->first();
        if ($existingPlayer) {
              return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "A player with this name and number already exists.",$existingPlayer);
           }
           $player = new Player();
           $type= $request->type;

            if ($type === 'player') {
                 $player->user_id = auth()->id();

            } elseif ($type === 'league') {

                $player->league_id = $request->league_id;

            } elseif ($type === 'team') {

                $player->user_id = auth()->id();
            }else{

            }

            $player->name = $request->name;
            $player->number=  $request->number;
            $player->position = $request->position;
            $player->size= $request->size;
            $player->speed= 3;
            $player->weight= $request->weight;
            $player->height= $request->height;
            $player->dob= $request->dob;
            $player->rpp= $request->ofp;

            $player->strength =  $request->strength;
            $player->position_value =  '';
            if($request->hasFile('image'))
            {
                $path =  uploadImage($request->image,'player');
                $player->image =$path;
            }
            $player->save();
            if($request->has('positionValue') && is_array($request->positionValue)) {
                foreach($request->positionValue as $index => $pos) {
                    DB::table('player_positions')->insert([
                        'player_id' => $player->id,
                        'position_name' => $pos['text'],
                        'meta' => null,
                        'sort' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
           if($type === 'team'){
                    DB::table('team_players')->insert([
                        'player_id' => $player->id,
                        'team_id' => $request->team_id,
                        'name' => $player->name,
                        'number' => $player->number,
                        'position' => $player->position,
                        'size' => $player->size,
                        'speed' => $player->speed,
                        'strength' => $player->strength,
                        'weight' => $player->weight,
                        'height' => $player->height,
                        'dob' => $player->dob,
                        'image' => $player->image,
                        'position_value' => $player->position_value,
                        'rpp' => $player->rpp,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player Added SuccessFully ", $player);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
        }
    }
    public function list(Request $request)
    {
        $userRoleIds = auth()->user()->roles->pluck('id');

        $query = Player::with(['roles' => function ($query) use ($userRoleIds) {
            $query->whereIn('roleables.role_id', $userRoleIds);
        }, 'playerPosition'])->orderBy('name');

        $searchTerm = trim((string) $request->input('search', ''));
        if ($searchTerm !== '') {
            $needle = '%'.addcslashes($searchTerm, '%_\\').'%';
            $query->where('name', 'like', $needle);
        }

        $paginateRequested = $request->has('page')
            || $request->has('per_page')
            || $request->filled('search');

        if ($paginateRequested) {
            $page = max(1, (int) $request->input('page', 1));
            $perPage = max(1, min(500, (int) $request->input('per_page', 20)));

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            $pagination = [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ];

            return new BaseResponse(
                STATUS_CODE_OK,
                STATUS_CODE_OK,
                'Player List  ',
                $paginator->items(),
                null,
                null,
                $pagination,
            );
        }

        $players = $query->get();

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, 'Player List  ', $players);
    }
    public function update(Request $request,$id)
    {

        DB::beginTransaction();
        try {


           $type = $request->type;
           if ($type == 'team_player') {

            $player = DB::table('team_players')->where('player_id', $request->player_id)->where('team_id', $id)->first();


            if (!$player) {
                return new BaseResponse(404, 404, "Team Player not found.");
            }


            $updateData = [
                'name' => $request->name,
                'number' => $request->number,
                'position' => $request->position,
                'size' => $request->size,
                'speed' => $request->speed,
                'strength' => $request->strength,
                'weight' => $request->weight,
                'height' => $request->height,
                'rpp' => $request->ofp,

                'updated_at' => now()
            ];

            try {
                $dob = Carbon::parse($request->dob);
                $updateData['dob'] = $dob;
            } catch (\Exception $e) {
                $updateData['dob'] = null;
            }

            if ($request->hasFile('image')) {
                $path = uploadImage($request->image, 'player');
                $updateData['image'] = $path;
            }

             DB::table('team_players')
            ->where('player_id', $request->player_id)
            ->where('team_id', $id)
            ->update($updateData);

            if ($request->has('positionValue') && is_array($request->positionValue)) {
                DB::table('team_player_positions')
                    ->where('teamplayer_id', $player->id)
                    ->delete();
                foreach ($request->positionValue as $index => $pos) {
                    DB::table('team_player_positions')->insert([
                        'teamplayer_id' => $player->id,
                        'position_name' => $pos['text'],
                        'meta' => null,
                        'sort' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            DB::commit();

        } else {



            $player =  Player::find($id);
            $player->name = $request->name;
            $player->number=  $request->number;
            $player->position = $request->position;
            $player->size= $request->size;
            $player->speed= $request->speed;
            $player->strength =  $request->strength;
             $player->weight= $request->weight;
            $player->height= $request->height;
            try {
            $dob = Carbon::parse($request->dob);
            } catch (\Exception $e) {
            $dob = null;
            }
             $player->dob=  $dob;
             $player->rpp= $request->ofp;
             $player->position_value =  null;
            if($request->hasFile('image'))
            {

                $path =  uploadImage($request->image,'player');
                $player->image =$path ;
            }
            $player->save();
            $player->playerPosition()->delete();

            if ($request->has('positionValue') && is_array($request->positionValue)) {
                foreach ($request->positionValue as $index => $pos) {
                    $player->playerPosition()->create([
                        'position_name' => $pos['text'],
                        'meta' => null,
                        'sort' => $index + 1
                    ]);
                }
            }

              $player->load('playerPosition');
          }
            DB::commit();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player Updated SuccessFully ", $player);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
        }
    }

       public function updatePracticePlayer(Request $request,$id)
    {

        //  \Log::info(['id'=>$id]);
        // \Log::info(['data'=>$request->all()]);

        // return;

        DB::beginTransaction();
        try {


           $type = $request->type;
           if ($type == 'team_player') {

            $player = DB::table('practice_team_players')->where('player_id', $id)->where('team_id', $request->team_id)->first();

            if (!$player) {
                return new BaseResponse(404, 404, "Team Player not found.");
            }

            // Resolve position names from multiselect array [{text,value}] or plain strings
            $positionNames = [];
            if ($request->has('positionValue') && is_array($request->positionValue)) {
                foreach ($request->positionValue as $pos) {
                    $name = is_array($pos) ? ($pos['text'] ?? $pos['value'] ?? null) : $pos;
                    if ($name) $positionNames[] = $name;
                }
            } elseif ($request->positionValue) {
                $positionNames[] = $request->positionValue;
            }

            $updateData = [
                'name' => $request->name,
                'number' => $request->number,
                'position' => $request->position,
                'size' => $request->size,
                'speed' => $request->speed,
                'strength' => $request->strength,
                'weight' => $request->weight,
                'height' => $request->height,
                'rpp' => $request->ofp,
                'position_value' => $positionNames[0] ?? null,
                'updated_at' => now()
            ];

            try {
                $dob = Carbon::parse($request->dob);
                $updateData['dob'] = $dob;
            } catch (\Exception $e) {
                $updateData['dob'] = null;
            }

           DB::table('practice_team_players')
            ->where('player_id', $id)
            ->where('team_id', $request->team_id)
            ->update($updateData);

            // Sync team_player_positions for the corresponding My Team TeamPlayer
            $league = DB::table('league_teams')->where('id', $request->team_id)->first();
            if ($league) {
                $myTeam = DB::table('league_teams')
                    ->where('league_id', $league->league_id)
                    ->where('type', 1)
                    ->where(function ($q) { $q->where('is_practice', 0)->orWhereNull('is_practice'); })
                    ->first();
                if ($myTeam) {
                    $myTP = DB::table('team_players')
                        ->where('team_id', $myTeam->id)
                        ->where(function ($q) use ($id) {
                            $q->where('player_id', $id)->orWhere('id', $id);
                        })
                        ->first();
                    if ($myTP) {
                        DB::table('team_player_positions')->where('teamplayer_id', $myTP->id)->delete();
                        foreach ($positionNames as $idx => $name) {
                            DB::table('team_player_positions')->insert([
                                'teamplayer_id' => $myTP->id,
                                'position_name' => $name,
                                'meta' => null,
                                'sort' => $idx + 1,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }

            $updatedPracticePlayer = DB::table('practice_team_players')
                ->where('player_id', $id)
                ->where('team_id', $request->team_id)
             ->first();

            DB::commit();
           return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player Updated SuccessFully ", $updatedPracticePlayer );
        }


        } catch (\Throwable $th) {
            DB::rollBack();
            return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
        }
    }


    public function updateOFP(Request $request, $id)
    {

        $request->validate([
            'rpp' => 'required|integer|min:0|max:100',
        ]);

        $teamPlayer = TeamPlayer::findOrFail($id);
        $teamPlayer->rpp = $request->input('rpp');
        $teamPlayer->save();

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player RPP Updated SuccessFully ", $teamPlayer);
    }
    /**
     * Remove a player from one team: pivot (team_players), roster rows (TeamPlayer),
     * and practice roster (practice_team_players + positions). The UI practice-team
     * table reads practice_team_players — detach() alone was not enough.
     */
    private function removePlayerFromTeamRoster(int|string $playerId, int|string $teamId): void
    {
        $playerId = (int) $playerId;
        $teamId = (int) $teamId;

        $teamPlayerIds = TeamPlayer::query()
            ->where('team_id', $teamId)
            ->where('player_id', $playerId)
            ->pluck('id');

        $practiceRowIds = PracticeTeamPlayer::query()
            ->where('team_id', $teamId)
            ->where(function ($q) use ($playerId, $teamPlayerIds) {
                $q->where('player_id', $playerId);
                if ($teamPlayerIds->isNotEmpty()) {
                    $q->orWhereIn('player_id', $teamPlayerIds);
                }
            })
            ->pluck('id');

        PersionalGrouping::removeMemberIdsFromAllTeamGroups(
            $teamId,
            $practiceRowIds->all(),
            $teamPlayerIds->all(),
        );

        if ($practiceRowIds->isNotEmpty()) {
            PracticeTeamPlayerPosition::query()
                ->whereIn('practice_team_player_id', $practiceRowIds)
                ->delete();
            PracticeTeamPlayer::query()
                ->whereIn('id', $practiceRowIds)
                ->delete();
        }

        TeamPlayer::query()
            ->where('team_id', $teamId)
            ->where('player_id', $playerId)
            ->delete();
    }

    public function delete($id, $team_id)
    {
        $player = Player::find($id);
        if (! $player) {
            return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, 'Player not found.');
        }

        DB::beginTransaction();
        try {
            $this->removePlayerFromTeamRoster($id, $team_id);
            $player->teams()->detach($team_id);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $e->getMessage());
        }

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player is  Deleted from the team SuccessFully ");
    }

    public function deletePlayer($id, $team_id)
    {
        $player = Player::find($id);
        if (! $player) {
            return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, 'Player not found.');
        }

        DB::beginTransaction();
        try {
            $this->removePlayerFromTeamRoster($id, $team_id);
            $player->teams()->detach($team_id);

            $remainingPracticeIds = PracticeTeamPlayer::query()
                ->where('player_id', (int) $id)
                ->pluck('id');
            if ($remainingPracticeIds->isNotEmpty()) {
                PracticeTeamPlayerPosition::query()
                    ->whereIn('practice_team_player_id', $remainingPracticeIds)
                    ->delete();
                PracticeTeamPlayer::query()
                    ->whereIn('id', $remainingPracticeIds)
                    ->delete();
            }

            TeamPlayer::query()->where('player_id', (int) $id)->delete();
            $player->teams()->detach();
            $player->delete();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $e->getMessage());
        }

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player is  Deleted from the SuccessFully ");
    }
    public function view($id)
    {
        $player =  Player::find($id);
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Player Details", $player);
    }
}
