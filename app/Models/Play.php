<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Play extends Model
{
    use HasFactory;
     public function configuredLeagues()
    {
        return $this->belongsToMany(League::class, 'configure_plays', 'play_id', 'league_id');
    }
}
