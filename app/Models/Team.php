<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    public function teamplayer()
    {
        return $this->hasMany(TeamPlayer::class,'team_id');
    }
}
