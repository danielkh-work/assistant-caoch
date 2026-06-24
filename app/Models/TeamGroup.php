<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamGroup extends Model
{
    protected $table = 'team_groups';

    protected $fillable = [
        'team_id',
        'league_id',
        'group_name',
        'description',
        'type',
        'players',
        'practice_players',
        'group_level',
        'status',
    ];

    protected $casts = [
        'players'          => 'array',
        'practice_players' => 'array',
        'group_level'      => 'integer',
    ];
}
