<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayTargetDefensivePlayer extends Model
{
    use HasFactory;
    

    protected $table = 'play_target_defensive_players';

    protected $fillable = [
        'play_id',
        'defensive_position_id',
        'player_id',
        'strength', // or any other fields your table has
    ];

    // Relationships

    public function play()
    {
        return $this->belongsTo(Play::class);
    }

    public function defensivePosition()
    {
        return $this->belongsTo(DefensivePosition::class, 'defensive_position_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
    
}
