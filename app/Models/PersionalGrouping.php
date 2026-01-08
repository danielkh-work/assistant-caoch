<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersionalGrouping extends Model
{
    use HasFactory;

    protected $table = 'persional_grouping';
    protected $appends = ['players_data'];
    protected $fillable = [
        'game_id',
        'league_id',
        'team_id',
        'group_name',
        'type',
        'players'
    ];

    protected $casts = [
        'players' => 'array', 
    ];



    public function getPlayersDataAttribute()
    {
        if (empty($this->players)) {
            return [];
        }

        return TeamPlayer::whereIn('id', $this->players)->get();
    }


    
}
