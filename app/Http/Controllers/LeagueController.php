<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeagueRule;
use App\Models\LeagueTeam;
use App\Models\Player;
use App\Models\PracticeTeamPlayer;
use App\Models\Sport;
use App\Models\Team;
use App\Models\TeamPlayer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\DataTables;
class LeagueController extends Controller
{
       /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $query = League::with('teams','roles')->orderBy('id', 'desc');
        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('roles.id', $request->role);
            });
        }
        $data = $query->get();
        if ($request->ajax()) {
            return DataTables::of($data)
                ->addIndexColumn()
                // ->addColumn('position',function ($row){
                //     return $row->is_verify==1 ? 'offence' : 'deffence';
                // })
                 ->addColumn('roles', function($row) {
                            return $row->roles->pluck('name')->implode(', ');
                    })
                  ->addColumn('action', function($row){
                    $editUrl = route('league.edit', ['id' => $row->id]);
                    $deleteUrl = route('play.destroy', ['id' => $row->id]);

                    return '
                        <a href="' . $editUrl . '" class="btn btn-warning btn-sm me-1">Edit</a>

                    ';
                    //  <form action="' . $deleteUrl . '" method="POST" style="display:inline;">
                    //         ' . csrf_field() . '
                    //         ' . method_field('DELETE') . '
                    //         <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure?\')">Delete</button>
                    //     </form>
                    })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('league.index',$data);
    }

    public function create(){
        $league_rule =  LeagueRule::all();
        $sports =  Sport::all();
        // $teams =  Team::all();
        $roles =  Role::all();
        return view('league.create',compact('league_rule','sports','roles'));
    }
     public function destroy($id)
    {
        $play = League::findOrFail($id);
        $play->delete();
        return redirect()->route('league.index')->with('success', 'Play deleted successfully');
    }
    public function edit($id)
    {
        $league = League::with('roles')->findOrFail($id);
        $roles = Role::all();
        $league_rule = LeagueRule::all();
        $sports = Sport::all();
        $headCoaches = User::where('role', 'head_coach')
            ->where('id', '!=', $league->user_id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        $currentOwner = User::find($league->user_id);

        return view('league.edit', compact('league', 'roles', 'league_rule', 'sports', 'headCoaches', 'currentOwner'));
    }

    /**
     * Transfer a league (and the players its owner created for it) to another head coach.
     * League settings, teams, plays, games, and history (configure_plays, logs, etc.) are
     * untouched — only ownership of the league row and the qualifying players moves.
     */
    public function transfer(Request $request, $id)
    {
        $request->validate([
            'to_user_id' => 'required|integer|exists:users,id',
        ]);

        $league = League::findOrFail($id);
        $fromUserId = $league->user_id;
        $toUserId = (int) $request->to_user_id;

        if ($fromUserId === $toUserId) {
            return redirect()->route('league.edit', $id)->with('error', 'League already belongs to that coach.');
        }

        $toUser = User::find($toUserId);
        if (!$toUser || $toUser->role !== 'head_coach') {
            return redirect()->route('league.edit', $id)->with('error', 'Target user must be a head coach.');
        }

        DB::beginTransaction();
        try {
            $teamIds = LeagueTeam::where('league_id', $league->id)->pluck('id');

            $playerIds = TeamPlayer::whereIn('team_id', $teamIds)->pluck('player_id')
                ->merge(PracticeTeamPlayer::whereIn('team_id', $teamIds)->pluck('player_id'))
                ->filter()
                ->unique();

            $transferredPlayers = Player::whereIn('id', $playerIds)
                ->where('user_id', $fromUserId)
                ->update(['user_id' => $toUserId]);

            $league->user_id = $toUserId;
            $league->save();

            DB::commit();

            return redirect()->route('league.index')->with(
                'success',
                "League \"{$league->title}\" transferred to {$toUser->name}. {$transferredPlayers} player(s) moved with it."
            );
        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()->route('league.edit', $id)->with('error', 'Transfer failed: ' . $th->getMessage());
        }
    }

    public function store(Request $request)
    {

        DB::beginTransaction();
        try {

           $League =  new League;
           $League->user_id=  auth('api')->user()->id;
           $League->sport_id = 1;
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
           foreach($request->team_name as $index => $value)
           {
             $team =  new LeagueTeam();
             $team->league_id =  $League->id;
             $team->team_name = $value;
             $team->type = $index == 0 ? 1 : null;
             $team->save();
           }

           DB::commit();

           $League->roles()->sync($request->role_id); // assign to multiple roles
           DB::commit();
             return  redirect()->route('league.index');
        } catch (\Throwable $th) {
          DB::rollBack();
          dd($th);
        }
    }

        public function update(Request $request,$id)
    {

        DB::beginTransaction();
        try {
            $League = League::findOrFail($id);
            $League->sport_id = 1;
            if ($request->has('league_rule_id') && $request->league_rule_id !== null) {
                $League->league_rule_id = $request->league_rule_id;
            }
            $League->number_of_team = $request->number_of_team;
            $League->title = $request->title;
            $League->number_of_downs = $request->number_of_downs;
            $League->length_of_field = $request->length_of_field;
            $League->number_of_timeouts = $request->number_of_timeouts;
            $League->clock_time = $request->clock_time;
            $League->number_of_quarters = $request->number_of_quarters;
            $League->length_of_quarters = $request->length_of_quarters;
            $League->stop_time_reason = $request->stop_time_reason;
            $League->overtime_rules = $request->overtime_rules;
            $League->number_of_players = $request->number_of_players;
            $League->flag_tbd = $request->flag_tbd;
            $League->save();

            $League->roles()->sync($request->role_id ?? []);

            // Update teams if provided
            if ($request->has('team_name') && is_array($request->team_name)) {
                $existingTeams = LeagueTeam::where('league_id', $League->id)->get()->values();

                foreach ($request->team_name as $index => $value) {
                    if (!empty($value)) {
                        if (isset($existingTeams[$index])) {
                            // Update existing team - only change name, preserve type and is_practice
                            $team = $existingTeams[$index];
                            $team->team_name = $value;
                            $team->save();
                        } else {
                            // Create new team
                            $team = new LeagueTeam;
                            $team->league_id = $League->id;
                            $team->type = $index == 0 ? 1 : null;
                            $team->team_name = $value;
                            $team->save();
                        }
                    }
                }
            }

            DB::commit();

            // Redirect to league index with success message
            return redirect()->route('league.index')
                ->with('success', 'League updated successfully!');
        } catch (\Throwable $th) {
            DB::rollBack();

            // Redirect back with error message
            return redirect()->back()
                ->with('error', 'Failed to update league: ' . $th->getMessage())
                ->withInput();
        }
    }



    public function show($id)
    {
        $league =    League::find($id);
        return view('league.show',compact('league'));
    }
}
