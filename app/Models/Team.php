<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    public function teamplayer()
    {
        return $this->hasMany(TeamPlayer::class, 'team_id');
    }

    public function logsAsMyTeam()
    {
        return $this->hasMany(PlayGameLog::class, 'my_team_id');
    }

    public function logsAsOpponent()
    {
        return $this->hasMany(PlayGameLog::class, 'oponent_team_id');
    }
}
