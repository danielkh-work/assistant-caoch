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

    public function offensiveTargets()
    {
        return $this->hasMany(OffensiveTargetStrength::class, 'play_id');
    }

    public function targetOffensivePlayers()
    {
        return $this->hasMany(PlayTargetOffensivePlayer::class, 'play_id');
    }

    public function offensivePositions()
    {
        return $this->belongsToMany(OffensivePosition::class, 'play_target_offensive_players', 'play_id', 'offensive_position_id')->withPivot('strength');
    }
     public function deffensivePositions()
    {
        return $this->belongsToMany(DefensivePosition::class, 'play_target_defensive_players', 'play_id', 'defensive_position_id')->withPivot('strength');
    }
      public function playResults()
    {
        return $this->hasMany(PlayResult::class);
    }
   
    public function roles()
    {
        return $this->morphToMany(Role::class, 'roleable');
    }

    public function personalGroupings()
    {
        return $this->belongsToMany(
            PersionalGrouping::class,
            'personal_grouping_play',
            'play_id',
            'personal_grouping_id'
        );
    }
}
 