<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlayerPosition extends Model
{
    use HasFactory, SoftDeletes;
     protected $table = 'player_positions';
    protected $fillable = ['player_id', 'position_name', 'meta', 'sort'];
    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id', 'id');
    }
}
