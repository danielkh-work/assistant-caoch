<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefensivePosition extends Model
{
    use HasFactory;

    protected $table = 'defensive_positions';

    protected $fillable = [
        'name', 
    ];

    public $timestamps = false; // If your 

     public function playTargetDefensivePlayers()
    {
        return $this->hasMany(PlayTargetDefensivePlayer::class, 'defensive_position_id');
    }
}
