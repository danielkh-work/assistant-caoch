<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguredPlayingTeamPlayer extends Model
{
    use HasFactory;
    protected $fillable= ['team_id','match_id','player_id','type','team_type'];
    public function player()
    {
        return $this->belongsTo(Player::class,'player_id');
    }

}
