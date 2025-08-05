<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpponentPackagePlayer extends Model
{
    use HasFactory;


     public function package()
    {
        return $this->belongsTo(OpponentTeamPackage::class, 'opponent_team_package_id');
    }

    public function player()
    {
        return $this->belongsTo(TeamPlayer::class);
    }
}
