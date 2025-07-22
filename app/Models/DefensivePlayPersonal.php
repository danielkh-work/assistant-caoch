<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefensivePlayPersonal extends Model
{
  
    protected $fillable = ['defensive_play_id', 'name','teamplayer_id'];

    public function defensivePlay()
    {
        return $this->belongsTo(DefensivePlay::class);
    }
    public function player()
    {
        return $this->belongsTo(DefensivePlay::class);
    }

     public function teamPlayer()
    {
      return $this->belongsTo(TeamPlayer::class,'teamplayer_id');
    }
}