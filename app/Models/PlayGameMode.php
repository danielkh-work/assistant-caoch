<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayGameMode extends Model
{
    use HasFactory;

    public function logs()
    {
        return $this->hasMany(PlayGameLog::class, 'game_id');
    }

    public function myTeam()
    {
        return $this->belongsTo(LeagueTeam::class, 'my_team_id');
    }

    public function opponentTeam()
    {
        return $this->belongsTo(LeagueTeam::class, 'oponent_team_id');
    }
}
