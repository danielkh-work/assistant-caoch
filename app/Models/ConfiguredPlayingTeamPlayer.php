<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguredPlayingTeamPlayer extends Model
{
    use HasFactory;
    protected $fillable= ['team_id','match_id','player_id','type','team_type','practice_player_id','game_type'];
    public function player()
    {
        return $this->belongsTo(TeamPlayer::class,'player_id');
    }
     public function practice_player()
    {
        return $this->belongsTo(PracticeTeamPlayer::class,'practice_player_id');
    }

}
