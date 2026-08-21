<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamPlayer extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = ['player_name'];
    protected $guarded = [];
    public function getPlayerNameAttribute()
    {
        return optional(Player::find($this->player_id))->name;
    }

    public function  player()
    {
        return $this->belongsTo(Player::class,'player_id');
    }
      public function teamPlayerPosition()
    {
        return $this->hasMany(TeamPlayerPosition::class, 'teamplayer_id');
    }

    public function leagueTeam()
    {
        return $this->belongsTo(LeagueTeam::class, 'team_id');
    }
}
