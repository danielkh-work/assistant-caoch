<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefensivePlayPersonal extends Model
{
    protected $fillable = ['defensive_play_id', 'name'];

    public function defensivePlay()
    {
        return $this->belongsTo(DefensivePlay::class);
    }
}