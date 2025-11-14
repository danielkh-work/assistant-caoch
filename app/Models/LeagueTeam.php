<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeagueTeam extends Model
{
    use HasFactory;
    public function teamplayer()
    {
        return $this->hasMany(TeamPlayer::class,'team_id');
    }
    public function league()
    {
        return $this->belongTo(League::class,'league_id');
    }
     public function practiceTeamplayer()
    {
        return $this->hasMany(PracticeTeamPlayer::class,'team_id');
    }


    
    
}
