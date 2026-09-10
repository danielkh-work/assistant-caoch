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
        // Use the already-loaded relation when available - this accessor runs
        // on every serialization (it's in $appends), so a bare Player::find()
        // here means one extra query per TeamPlayer row, every time a list of
        // them gets serialized (e.g. player-list, ~2000 extra queries for
        // ~1200 players). Only fall back to a fresh query if it's genuinely
        // not loaded.
        if ($this->relationLoaded('player')) {
            return $this->player?->name;
        }

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
