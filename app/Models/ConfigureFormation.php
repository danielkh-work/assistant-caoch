<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigureFormation extends Model
{
    use HasFactory;

    public function league()
    {
        return $this->belongsTo(League::class,'league_id');
    }
    public function team()
    {
        return $this->belongsTo(LeagueTeam::class,'team_id');
    }
}
