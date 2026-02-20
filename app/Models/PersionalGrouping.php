<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersionalGrouping extends Model
{
    use HasFactory;

    protected $table = 'personal_groupings';
    protected $appends = ['players_data','practice_players_data'];
    protected $fillable = [
        'game_id',
        'league_id',
        'team_id',
        'group_name',
        'type',
        'players',
        'practice_players'
    ];

   protected $casts = [
    'players' => 'array',
    'practice_players' => 'array',
];




    public function getPlayersDataAttribute()
    {
        if (empty($this->players)) {
            return [];
        }

        return TeamPlayer::whereIn('id', $this->players)->get();
    }
   

   public function getPracticePlayersDataAttribute()
    {
        if (empty($this->practice_players)) {
            return [];
        }

        return PracticeTeamPlayer::whereIn('id', $this->practice_players)->get();
    }
   public function plays()
    {
        return $this->belongsToMany(
            Play::class,               // Related model
            'personal_grouping_play', // Pivot table name
            'personal_grouping_id',   // Foreign key on pivot table for this model
            'play_id'                 // Foreign key on pivot table for related model
        );
    }

    public function defensivePlays()
    {
            return $this->belongsToMany(
                DefensivePlay::class,           // Related model
                'defensive_play_personal_grouping', // Pivot table
                'personal_grouping_id',         // Foreign key on pivot table pointing to this model (PersonalGrouping)
                'defensive_play_id'             // Foreign key on pivot table pointing to related model (DefensivePlay)
            );

    }
    
}
