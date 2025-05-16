<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class League extends Model
{
    use HasFactory;

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
}
