<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\Play;
use App\Models\Player;
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
      $leage =  League::count();
      $play =  Play::count();
      $player =  Player::count();
        $data = [
            'customers' => $player,
            'guards' => $play,
            'rides' =>$leage
        ];
        return view('layouts.dashboard',$data);
    }
}
