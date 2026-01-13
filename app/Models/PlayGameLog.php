<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayGameLog extends Model
{
    use HasFactory;

    protected $casts = [
        'players' => 'array',    
       
    ];

    public function myTeam()
    {
        return $this->belongsTo(LeagueTeam::class, 'my_team_id');
    }

    public function opponentTeam()
    {
        return $this->belongsTo(LeagueTeam::class, 'oponent_team_id');
    }

    

    public function getTargetTeamAttribute()
{
    if (!$this->play_id) {
        return null;
    }

    // Try Play
    $play = Play::find($this->play_id);
    if ($play) {
        return [
            'id' => $play->id,
            'name' => $play->play_name,   // normalize
            'type' => 'offense',
        ];
    }

    // Try DefensivePlay
    $defensive = DefensivePlay::find($this->play_id);
    if ($defensive) {
        return [
            'id' => $defensive->id,
            'name' => $defensive->name,   // normalize
            'type' => 'defense',
        ];
    }

    return null;
}


    
}
