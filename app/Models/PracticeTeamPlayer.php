<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PracticeTeamPlayer extends Model
{
    use HasFactory, SoftDeletes;
   
    protected $guarded = [];
    public function TeamPlayer()
    {
        return $this->belongsTo(TeamPlayer::class, 'player_id');
    }
    public function positions()
    {
        return $this->hasMany(PracticeTeamPlayerPosition::class, 'practice_team_player_id');
    }
   
}
