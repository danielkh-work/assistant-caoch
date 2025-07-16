<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigureDefensivePlay extends Model
{
    use HasFactory;
     public function league()
    {
        return $this->belongsTo(League::class,'league_id');
    }
    public function play()
    {
        return $this->belongsTo(Play::class,'play_id');
    }
}
