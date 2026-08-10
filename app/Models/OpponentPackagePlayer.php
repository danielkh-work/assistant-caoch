<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpponentPackagePlayer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'opponent_package_player';


     public function package()
    {
        return $this->belongsTo(OpponentTeamPackage::class, 'opponent_team_package_id');
    }

    public function player()
    {
        return $this->belongsTo(TeamPlayer::class);
    }
}
