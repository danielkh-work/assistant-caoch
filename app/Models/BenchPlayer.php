<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BenchPlayer extends Model
{
    use HasFactory;
     protected $table = 'offense_defense_players';
     protected $fillable = [
        'team_id',
        'type',
        'player_type',
        'league_id',
        'game_id',
        'player_id',
    ];

    // Relationships (optional but useful)
    public function league()
    {
        return $this->belongsTo(League::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
    public function team()
    {
        return $this->belongsTo(LeagueTeam::class);
    }
    
    public function player()
    {
        return $this->belongsTo(TeamPlayer::class,'player_id');
    }

}
