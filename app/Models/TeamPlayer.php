<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamPlayer extends Model
{
    use HasFactory;

    protected $appends = ['player_name'];
    protected $guarded = [];
    public function getPlayerNameAttribute()
    {
        return optional(Player::find($this->player_id))->name;
    }

    public function  player()
    {
        return $this->belongsTo(Player::class,'player_id');
    }
}
