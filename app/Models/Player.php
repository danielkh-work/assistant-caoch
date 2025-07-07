<?php

namespace App\Models;
use Spatie\Permission\Models\Role;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;
    public function teams()
    {
          return $this->belongsToMany(Team::class, 'team_players', 'player_id', 'team_id');
    }
    public function roles()
    {
        return $this->morphToMany(Role::class, 'roleable');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
