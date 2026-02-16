<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PracticeTeamPlayer extends Model
{
    use HasFactory;
   
    protected $guarded = [];

  
    public function TeamPlayer()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }
}
