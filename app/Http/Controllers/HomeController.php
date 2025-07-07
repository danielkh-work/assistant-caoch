<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\Play;
use App\Models\Player;
use App\Models\LeagueTeam;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {

       
      $teams =  LeagueTeam::count();
      $leage =  League::count();
      $play =  Play::count();
      $player =  Player::count();
      $stats = DB::select("
        SELECT
            roles.id AS role_id,
            roles.name AS role_name,
            SUM(CASE WHEN roleables.roleable_type = 'App\\\\Models\\\\Player' THEN 1 ELSE 0 END) AS total_players,
            SUM(CASE WHEN roleables.roleable_type = 'App\\\\Models\\\\Play' THEN 1 ELSE 0 END) AS total_plays,
            SUM(CASE WHEN roleables.roleable_type = 'App\\\\Models\\\\League' THEN 1 ELSE 0 END) AS total_leagues
        FROM roles
        LEFT JOIN roleables ON roleables.role_id = roles.id
        GROUP BY roles.id, roles.name
        ORDER BY roles.name
    ");

        $data = [
            'customers' => $player,
            'guards' => $play,
            'rides' =>$leage,
            'teams'=>$teams,
            'stats'=>$stats
        ];
        
     
        return view('layouts.dashboard',$data);
    }
}
