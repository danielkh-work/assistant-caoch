<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\Play;
use App\Models\Player;
use App\Models\LeagueTeam;
use App\Models\User;
use Illuminate\Http\Request;

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
        $data = [
            'customers' => $player,
            'guards' => $play,
            'rides' =>$leage,
            'teams'=>$teams
        ];
        return view('layouts.dashboard',$data);
    }
}
