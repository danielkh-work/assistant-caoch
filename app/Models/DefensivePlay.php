<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefensivePlay extends Model
{
    protected $guarded = [];

    public function personals()
    {
    return $this->hasMany(DefensivePlayPersonal::class,'defensive_play_id');
    }
    public function strategyBlitz()
    {
       return $this->belongsTo(DefensivePlayParameter::class,'strategy_blitz');
    }
    public function formation()
    {
       return $this->belongsTo(DefensivePlayParameter::class,'formation');
    }

   

    
}