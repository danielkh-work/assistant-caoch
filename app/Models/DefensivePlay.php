<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class DefensivePlay extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    /**
     * Same reasoning as Play::cascadeDeleteChildren() - these children had a
     * DB-level cascade FK that stops firing once defensive_plays soft-deletes
     * instead of hard-deleting, so this replicates it by hand.
     */
    public function cascadeDeleteChildren(): void
    {
        DefensivePlayPersonal::where('defensive_play_id', $this->id)->delete();
        ConfigureDefensivePlay::where('play_id', $this->id)->delete();
        DB::table('defensive_play_personal_grouping')->where('defensive_play_id', $this->id)->delete();
    }
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