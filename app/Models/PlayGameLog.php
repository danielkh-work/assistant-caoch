<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayGameLog extends Model
{
    use HasFactory;

    public function myTeam()
    {
        return $this->belongsTo(Team::class, 'my_team_id');
    }

    public function opponentTeam()
    {
        return $this->belongsTo(Team::class, 'oponent_team_id');
    }
}
