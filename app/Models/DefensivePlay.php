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
}