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
        \Log::info(['data requea players'=>$teamId]);

        if (!$teamId || !$gameId) {
            return new BaseResponse(
                STATUS_CODE_ERROR,
                STATUS_CODE_ERROR,
                "team_id and game_id are required"
            );
        }

        $groups = PersionalGrouping::where('team_id', $teamId)
                    ->where('game_id', $gameId)
                    ->orderBy('created_at', 'desc')
                    ->get();

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            "Groups fetched successfully",
            $groups
        );
    }


}





