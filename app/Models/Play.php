<?php

namespace App\Models;
use Spatie\Permission\Models\Role;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Play extends Model
{
    use HasFactory;
    public function configuredLeagues()
    {
        return $this->belongsToMany(League::class, 'configure_plays', 'play_id', 'league_id');
    }

    public function offensivePositions()
    {
        return $this->belongsToMany(OffensivePosition::class, 'play_target_offensive_players', 'play_id', 'offensive_position_id')->withPivot('strength');
    }
     public function deffensivePositions()
    {
        return $this->belongsToMany(DefensivePosition::class, 'play_target_defensive_players', 'play_id', 'defensive_position_id')->withPivot('strength');
    }
    public function roles()
    {
        return $this->morphToMany(Role::class, 'roleable');
    }
}
 