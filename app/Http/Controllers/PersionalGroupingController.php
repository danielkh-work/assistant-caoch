<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PersionalGrouping;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\BaseResponse;
class PersionalGroupingController extends Controller
{
    public function storeAllGroups(Request $request)
{
    $groupsData = $request->all(); // array of groups

    DB::beginTransaction();

    try {
        $insertData = [];

        foreach ($groupsData as $groupData) {
            $insertData[] = [
                'game_id' => $groupData['game_id'],
                'league_id' => $groupData['league_id'],
                'team_id' => $groupData['team_id'],
                'group_name' => $groupData['group_name'],
                'type' => $groupData['type'] ?? 'Offense',
                'players' => json_encode($groupData['players']), // convert array to JSON
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        PersionalGrouping::insert($insertData); // bulk insert
        
        DB::commit();

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            "Groups saved successfully",
            $groupsData // you can return original data if needed
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


   public function getGroupsByTeamAndGame(Request $request)
    {
      
        $teamId = $request->query('team_id');
        $gameId = $request->query('game_id');
        $leagueId = $request->query('league_id');
        

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
        ->orderBy('created_at', 'desc')
        ->get();

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





