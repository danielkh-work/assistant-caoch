<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DefensivePlayPersonal extends Model
{
    use SoftDeletes;
    protected $table = 'defensive_play_personals'; 
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