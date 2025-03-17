<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class League extends Model
{
    use HasFactory;

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
}
