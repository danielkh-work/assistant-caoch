<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PersionalGrouping;
use App\Models\League;

use Illuminate\Support\Facades\DB;
use App\Http\Responses\BaseResponse;
class PersionalGroupingController extends Controller
{


public function storeAllGroups(Request $request)
{
    $groupsData = $request->all();

    DB::beginTransaction();

    try {
        $insertData = [];

        foreach ($groupsData as $groupData) {

            $isPractice = filter_var($groupData['is_practice'] ?? false, FILTER_VALIDATE_BOOLEAN);
            \Log::info(['is_practice'=> $isPractice ]);

            $insertData[] = [
                'game_id' => $groupData['game_id'],
                'league_id' => $groupData['league_id'],
                'team_id' => $groupData['team_id'],
                'group_name' => $groupData['group_name'],
                'type' => $groupData['type'] ?? 'Offense',

                // ✅ If practice → store in practice_players
                'players' => $isPractice ? null : json_encode($groupData['players']),
                'practice_players' => $isPractice ? json_encode($groupData['players']) : null,

                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
      
        PersionalGrouping::insert($insertData);

        DB::commit();

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            "Groups saved successfully",
            $groupsData
        );

    } catch (\Exception $e) {

        DB::rollBack();

        return new BaseResponse(
            STATUS_CODE_ERROR,
            STATUS_CODE_ERROR,
            "Failed to save groups: " . $e->getMessage()
        );
    }
}

//     public function storeAllGroups(Request $request)
// {
//     $groupsData = $request->all(); // array of groups

//     DB::beginTransaction();

//     try {
//         $insertData = [];
       
//         foreach ($groupsData as $groupData) {
//             $insertData[] = [
//                 'game_id' => $groupData['game_id'],
//                 'league_id' => $groupData['league_id'],
//                 'team_id' => $groupData['team_id'],
//                 'group_name' => $groupData['group_name'],
//                 'type' => $groupData['type'] ?? 'Offense',
//                 'players' => json_encode($groupData['players']), // convert array to JSON
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ];
//         }

//         PersionalGrouping::insert($insertData); // bulk insert
        
//         DB::commit();

//         return new BaseResponse(
//             STATUS_CODE_OK,
//             STATUS_CODE_OK,
//             "Groups saved successfully",
//             $groupsData // you can return original data if needed
//         );

//     } catch (\Exception $e) {
//         DB::rollBack();

//         return new BaseResponse(
//             STATUS_CODE_ERROR,
//             STATUS_CODE_ERROR,
//             "Failed to save groups: " . $e->getMessage()
//         );
//     }
// }
   
// public function updateGroup(Request $request, $id)
// {
//     $request->validate([
//         'group_name' => 'required|string|max:255',
       
//         'players'    => 'required|array', 
//     ]);

//     try {
//         $group = PersionalGrouping::findOrFail($id);

        
//         $group->update([
//             'group_name' => $request->group_name,
          
//             'players'    => $request->players, 
//         ]);

//         return new BaseResponse(
//             STATUS_CODE_OK,
//             STATUS_CODE_OK,
//             "Group updated successfully",
//             $group
//         );

//     } catch (\Exception $e) {
//         return new BaseResponse(
//             STATUS_CODE_ERROR,
//             STATUS_CODE_ERROR,
//             "Failed to update group: " . $e->getMessage()
//         );
//     }
// }

        public function updateGroup(Request $request, $id)
        {
            $request->validate([
                'group_name'  => 'required|string|max:255',
                'players'     => 'required|array',
                'is_practice' => 'nullable'
            ]);

            try {
                $group = PersionalGrouping::findOrFail($id);

                $isPractice = filter_var($request->is_practice ?? false, FILTER_VALIDATE_BOOLEAN);

                $updateData = [
                    'group_name' => $request->group_name,
                ];

                if ($isPractice) {
                    $updateData['players'] = null;
                    $updateData['practice_players'] = $request->players;
                } else {
                    $updateData['players'] = $request->players;
                    $updateData['practice_players'] = null;
                }

                $group->update($updateData);

                return new BaseResponse(
                    STATUS_CODE_OK,
                    STATUS_CODE_OK,
                    "Group updated successfully",
                    $group
                );

            } catch (\Exception $e) {

                return new BaseResponse(
                    STATUS_CODE_ERROR,
                    STATUS_CODE_ERROR,
                    "Failed to update group: " . $e->getMessage()
                );
            }
        }

   public function getGroupsByTeamAndGame(Request $request)
    {
       $forPracticeMode = filter_var($request->query('for_practice_mode', false), FILTER_VALIDATE_BOOLEAN);
        $teamId = $request->query('team_id');
        $gameId = $request->query('game_id');
        $leagueId = $request->query('league_id');
        $isPractice = $request->query('is_practice', 0);
       
        $League = League::find($leagueId);
       
        $practice_length_players = $League->practice_number_players ?? 7;
         if ((!$teamId || !$gameId) && !$leagueId) {
        return new BaseResponse(
            STATUS_CODE_ERROR,
            STATUS_CODE_ERROR,
            "team_id & game_id OR league_id is required"
        );
    }

      $groups = PersionalGrouping::query()
        ->when($teamId && $gameId, function ($q) use ($teamId, $gameId) {
            $q->where('team_id', $teamId)
              ->where('game_id', $gameId);
        })
        ->when((!$teamId || !$gameId) && $leagueId, function ($q) use ($leagueId) {
            $q->where('league_id', $leagueId);
        })
        ->when($isPractice && $forPracticeMode, function ($q) use ($practice_length_players) {
            $q->whereRaw('JSON_LENGTH(practice_players) = ?', [$practice_length_players]);
        })
        
        ->orderBy('created_at', 'desc')
        ->get();

        \Log::info(['data'=>$groups]);

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            "Groups fetched successfully",
            $groups
        );
    }

   public function deleteGroup($id)
{
    // Find the group by ID
    $group = PersionalGrouping::find($id);
     if ($group)
        $group->delete();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "group Delete Successfully ");

    

  }

    public function getPlays(PersionalGrouping $group)
    {
      

        $attachedPlayIds = $group->plays()->pluck('play_id')->toArray();
         $attachedDefPlayIds = $group->defensivePlays()->pluck('defensive_play_id')->toArray();

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            "plays synced successfully",
                [
                        'offensive' => $attachedPlayIds,
                        'defensive' => $attachedDefPlayIds
                ]
        );
    }
    public function syncPlays(Request $request, PersionalGrouping $group)
    {
        // Validate incoming data: 'play_ids' must be an array of integers
         $validated = $request->validate([
           'type' => 'required|in:offense,defensive',
           'play_ids' => 'required|array',
        ]);
        
        if($request->type=='offense'){
           $group->plays()->sync($validated['play_ids']);
        }else if($request->type=='defensive'){
          $group->defensivePlays()->sync($validated['play_ids']);
        }
       $group->load(['plays', 'defensivePlays']);

       
        $responseData = [
            'offensive_play_ids' => $group->plays->pluck('id')->toArray(),
            'defensive_play_ids' => $group->defensivePlays->pluck('id')->toArray(),
        ];
        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            "plays synced successfully",
             $responseData
        );
    }

    


}





