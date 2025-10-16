<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\League;
use App\Models\LeagueRule;
use App\Models\LeagueTeam;
use App\Models\Player;
use App\Models\PlayGameMode;
use App\Models\Sport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Game;

class SportController extends Controller
{
    public function sport(Request $request){
        $sport = Sport::all();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "sport", $sport);
    }

    public function league(Request $request)
    {
        // "role":"assistant_coach","head_coach_id":30
        \Log::info('Authenticated user:', ['user' => auth()->user()->assistant_coach]);
        $id =  auth()->user()->id;
        $user=auth()->user();
        $userRoleIds = auth()->user()->roles->pluck('id');
        $league = League::with([
            'teams',  // Selecting only 'id' and 'name' from teams
            'league_rule:id,title', // Selecting only 'id' and 'title' from leaque_rule
            'sport:id,title',
            'roles' 
        ])
        ->when($user->role == 'assistant_coach', function ($query) use ($user) {
                return $query->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                    ->orWhere('user_id', $user->head_coach_id);
                });
             
            }, function ($query) use ($user) {
                return $query->orWhere('user_id', $user->id);
        })
        ->where('sport_id',$request->sport_id)
        ->orWhereHas('roles', function ($query) use ($userRoleIds) {
            $query->where(function ($q) use ($userRoleIds) {
                $q->whereIn('roleables.role_id', $userRoleIds);
            });
       })
       ->get();
        
        
       
        
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "leauqe List  ", $league);
    }
    public function leagueRule(Request $request)
    {
        $League = LeagueRule::all();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "League Rule ", $League);
    }
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
 
           $League =  new League;
           $League->user_id=  auth('api')->user()->id;
           $League->sport_id=$request->sport_id;
           $League->location=$request->location;
           $League->league_rule_id=$request->league_rule_id;
           $League->number_of_team=$request->number_of_team;
           $League->title=$request->title;
           $League->number_of_downs=$request->number_of_downs;
           $League->length_of_field=$request->length_of_field;
           $League->number_of_timeouts=$request->number_of_timeouts;
           $League->clock_time=$request->clock_time;
           $League->number_of_quarters=$request->number_of_quarters;
           $League->length_of_quarters=$request->length_of_quarters;
           $League->stop_time_reason=$request->stop_time_reason;
           $League->overtime_rules=$request->overtime_rules;
           $League->number_of_players=$request->number_of_players;
           $League->flag_tbd =$request->flag_tbd;
           $League->save();
           $practiceTeams = [];
           foreach($request->team_name as $index => $value)
           {
             $team =  new LeagueTeam;
             $team->league_id =  $League->id;
             $team->team_name = $value['name'];
             if($index==0){
                 $team->type=1;
             }else if( $value['is_practice']==1){
                $team->type=1;
             }else{
                 $team->type=null;
             }
            
             $team->is_practice = $value['is_practice'];
             $team->save();
              if ($team->is_practice == 1) {
                $practiceTeams[] = $team->id;
            }


           }

            if (count($practiceTeams) == 2) {
                    $game = new Game; 
                    $game->league_id = $League->id;
                    $game->creator_id = auth('api')->user()->id;
                    $game->my_team_id = $practiceTeams[0];
                    $game->oponent_team_id	 = $practiceTeams[1];
                    $game->type	 = 2;
                   
                    $game->save();
            }
           DB::commit();
           return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "League Created SuccessFully",$League);
        } catch (\Throwable $th) {
          DB::rollBack();
          return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
        }
    }
    public function leagueUpdate(Request $request)
    {
        DB::beginTransaction();
        try {

           $League =  League::find($request->id);
           $League->sport_id=$request->sport_id;
           $League->league_rule_id=$request->league_rule_id;
           $League->number_of_team=$request->number_of_team;
           $League->title=$request->title;
           $League->number_of_downs=$request->number_of_downs;
           $League->length_of_field=$request->length_of_field;
           $League->number_of_timeouts=$request->number_of_timeouts;
           $League->clock_time=$request->clock_time;
           $League->number_of_quarters=$request->number_of_quarters;
           $League->length_of_quarters=$request->length_of_quarters;
           $League->stop_time_reason=$request->stop_time_reason;
           $League->overtime_rules=$request->overtime_rules;
           $League->number_of_players=$request->number_of_players;
           $League->flag_tbd =$request->flag_tbd;
           $League->save();
        
           foreach ($request->team_name as $index => $teamData) {
                // Skip if name is empty (optional)
                if (empty($teamData['name'])) {
                    continue;
                }

                // Find existing team or create new instance
                $team = isset($teamData['id']) 
                    ? LeagueTeam::find($teamData['id']) ?? new LeagueTeam
                    : new LeagueTeam;

                $team->league_id = $League->id;
                $team->team_name = $teamData['name'];
                $team->type = $index === 0 ? 1 : null;

                $team->save();
            }
           
           DB::commit();
           return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "League Updated  SuccessFully ", $League);
        } catch (\Throwable $th) {
          DB::rollBack();
          return new BaseResponse(STATUS_CODE_BADREQUEST, STATUS_CODE_BADREQUEST, $th->getMessage());
        }
    }

    public function leagueUpdatePoints(Request $request)
    {
        $team = LeagueTeam::where('id', $request->team_id)
                   
                    ->first();
        if (!$team) {
            return response()->json([
                'status' => 'error',
                'message' => 'Team not found for this league'
            ], 404);
        }

        $team->won = $request->won;
        $team->drawn = $request->drawn;
        $team->lost = $request->lost;
        $team->points = $request->points;
        $team->save();
   
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Team Points Update ", $team);
    }

    
    public function leagueView(Request $request)
    {
      $leauqe = League::with('teams','league_rule','sport')->find($request->id); 
      $teams = LeagueTeam::where('league_id', $leauqe->id)->where(function ($q) {
        $q->where('type', 1)
          ->orWhereNull('type');
      })->where('is_practice',0)->get();
      $matches = PlayGameMode::where('league_id', $leauqe->id)->where('status', 4)->get();
 
      $pointsTable = [];
 
      foreach ($teams as $team) {
          $pointsTable[$team->id] = [
              'leauqe_id'=>$leauqe->id,
              'team_id'=>$team->id,
              'team_name' => $team->team_name,
              'type' => $team->type,
              'played' => 0,
              'won' => $team->won,
              'lost' => $team->lost,
              'drawn' => $team->drawn,
              'points' => $team->points,
          ];
      }
 
      foreach ($matches as $match) {
          $teamA = $match->my_team_id;
          $teamB = $match->oponent_team_id;
          $scoreA = $match->my_team_score;
          $scoreB = $match->oponent_team_score;
 
          // Increment played
          $pointsTable[$teamA]['played']++;
          $pointsTable[$teamB]['played']++;
 
          if ($scoreA > $scoreB) {
              $pointsTable[$teamA]['won']++;
              $pointsTable[$teamA]['points'] += 2;
              $pointsTable[$teamB]['lost']++;
          } elseif ($scoreA < $scoreB) {
              $pointsTable[$teamB]['won']++;
              $pointsTable[$teamB]['points'] += 2;
              $pointsTable[$teamA]['lost']++;
          } else {
              $pointsTable[$teamA]['drawn']++;
              $pointsTable[$teamB]['drawn']++;
              $pointsTable[$teamA]['points'] += 1;
              $pointsTable[$teamB]['points'] += 1;
          }
      }

      foreach ($pointsTable as $entry) {
   
        if (!isset($entry['team_id'], $entry['leauqe_id'])) {
            continue;
        }

        LeagueTeam::where('id', $entry['team_id'])
            ->where('league_id', $entry['leauqe_id'])
            ->update([
                'won'    => $entry['won'],
                'drawn'  => $entry['drawn'],
                'lost'   => $entry['lost'],
                'points' => $entry['points'],
            ]);
      }  

      $updatedPoints = LeagueTeam::where('league_id', $leauqe->id)
        ->where(function ($q) {
            $q->where('type', 1)->orWhereNull('type');
        })
        ->where('is_practice', 0)
        ->get(['id', 'team_name', 'type', 'won', 'drawn', 'lost', 'points']);
        \Log::info(['updatedPoints',$updatedPoints]);

      return response()->json([
          'status' => STATUS_CODE_OK,
          'league' => $leauqe,
          'pointsTable' => $updatedPoints
      ]);
    }

    public function dashboard(Request $request)
    {
        
        $leauqe =  League::with('teams','league_rule','sport')->get();
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "leauqe List  ", $leauqe);
    }

 
}
