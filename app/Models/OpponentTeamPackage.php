<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpponentTeamPackage extends Model
{
    use HasFactory;

      protected $fillable = [
        'game_id',
        'opponent_team_id',
        'name',
        'grouping_count',
    ];

   
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    
    public function opponentTeam()
    {
        return $this->belongsTo(LeagueTeam::class, 'opponent_team_id');
    }

  
    public function players()
    {
        return $this->belongsToMany(Player::class, 'opponent_package_player')
                    ->withTimestamps();
    }

   
    public static function createPackage(array $data)
    {
        return self::create($data);
    }

    /**
     * Get all packages for an opponent team in a game.
     */
    public static function getPackagesForOpponent($gameId,$teamId)
    {
        \Log::info(['game_id'=>$gameId]);
        \Log::info(['teamId'=>$teamId]);
        return self::where('game_id',$teamId )
                   ->where('opponent_team_id', $gameId)
                   ->get();
                   
    }

    /**
     * Check if a package with the same name already exists.
     */
    public static function packageExists($gameId, $teamId, $name)
    {
        return self::where('game_id', $gameId)
                   ->where('opponent_team_id', $teamId)
                   ->where('name', $name)
                   ->exists();
    }
}
