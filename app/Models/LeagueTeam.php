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
    
}
