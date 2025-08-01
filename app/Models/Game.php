<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function myTeam()
    {
        return $this->belongsTo(LeagueTeam::class, 'my_team_id');
    }

    public function opponentTeam()
    {
        return $this->belongsTo(LeagueTeam::class, 'oponent_team_id');
    }

  
    public function configuredPlays()
    {
        return $this->hasMany(ConfigurePlay::class, 'match_id');
    }
    public function configureMyTeams()
    {
        return $this->hasMany(ConfiguredPlayingTeamPlayer::class, 'match_id')
                    ->where('team_type', 1);
    }
    public function configureVisitingTeams()
    {
        return $this->hasMany(ConfiguredPlayingTeamPlayer::class, 'match_id')
                    ->where('team_type', 2);
    }
}
