<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
class League extends Model
{
    use HasFactory;

    protected $fillable = [
        'sport_id',
        'league_rule_id',
        'title',
        'location',
        'number_of_team',
        'number_of_downs',
        'length_of_field',
        'number_of_timeouts',
        'clock_time',
        'number_of_quarters',
        'length_of_quarters',
        'stop_time_reason',
        'overtime_rules',
        'number_of_players',
        'practice_number_players',
        'warning_time_minutes',
        'flag_tbd',
        'rpp_configuration',
    ];

    protected $appends = ['sport_name', 'rules_name']; // Correct spelling
    public function teams()
    {
        return $this->hasMany(LeagueTeam::class,'league_id');
    }
    public function league_rule()
    {
        return $this->belongsTo(LeagueRule::class,'league_rule_id');
    }
    public function sport()
    {
        return $this->belongsTo(Sport::class,'sport_id');
    }

    public function getSportNameAttribute()
    {
        return $this->sport ? $this->sport->title : '-';
    }

    public function getRulesNameAttribute()
    {
        return $this->league_rule ? $this->league_rule->title : '-';
    }

    public function matches()
    {
        return $this->hasMany(PlayGameMode::class,'league_id');
    }
    public function roles()
    {
        return $this->morphToMany(Role::class, 'roleable');
    }
}
