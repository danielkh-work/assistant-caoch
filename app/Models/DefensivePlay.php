<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefensivePlay extends Model
{
    protected $guarded = [];
    public function configuredLeagues()
    {
        return $this->belongsToMany(League::class, 'configure_defensive_plays', 'play_id', 'league_id');
    }

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
      public function playResults()
    {
       return $this->hasMany(PlayResult::class, 'play_id', 'id');
    }


    public function personalGroupings()
   {
      return $this->belongsToMany(
         PersionalGrouping::class,                
         'defensive_play_personal_grouping',     
         'defensive_play_id',                    
         'personal_grouping_id'                  
      );
   }

   // 

    
}