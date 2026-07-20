<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Game extends Model
{
    use HasFactory,SoftDeletes;


    protected $guarded = [];

    protected $fillable = [
        'league_id',
        'creator_id',
        'name',
        'descrptions',
        'my_team_id',
        'oponent_team_id',
        'status',
        'match_start_date',
        'match_end_date',
    ];

    protected $casts = [
        'match_start_date' => 'datetime',
        'match_end_date' => 'datetime',
    ];

    public function myTeam()
    {
        return $this->belongsTo(LeagueTeam::class, 'my_team_id');
    }

    public function opponentTeam()
    {
        return $this->belongsTo(LeagueTeam::class, 'oponent_team_id');
    }

    public function league()
    {
        return $this->belongsTo(League::class, 'league_id');
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

      public function configuredPlays()
    {
        return $this->hasMany(ConfigurePlay::class, 'match_id');
    }
}
