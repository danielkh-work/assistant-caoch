<?php

namespace App\Models;
use Spatie\Permission\Models\Role;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Player extends Model
{
    use HasFactory, SoftDeletes;
    public function teams()
    {
          return $this->belongsToMany(Team::class, 'team_players', 'player_id', 'team_id');
    }
    public function roles()
    {
        return $this->morphToMany(Role::class, 'roleable');
    }
   public function playerPosition()
    {
        return $this->hasMany(PlayerPosition::class, 'player_id', 'id');
    }
    public function user()
    {
        // withTrashed: creator may be a soft-deleted assistant/performance coach —
        // this relation should still resolve their name instead of silently going null.
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function teamPlayers()
    {
        return $this->hasMany(TeamPlayer::class, 'player_id');
    }

    public function league()
    {
        return $this->belongsTo(League::class, 'league_id');
    }
}
