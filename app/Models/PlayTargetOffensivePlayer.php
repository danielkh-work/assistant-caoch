<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayTargetOffensivePlayer extends Model
{
    use HasFactory;
    protected $table = 'play_target_offensive_players';

    protected $fillable = [
        'play_id',
        'offensive_position_id',
        'player_id',
        'strength',
      
    ];

    public function play()
    {
        return $this->belongsTo(Play::class);
    }

    public function position()
    {
        return $this->belongsTo(OffensivePosition::class, 'offensive_position_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
