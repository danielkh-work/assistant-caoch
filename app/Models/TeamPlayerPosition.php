<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamPlayerPosition extends Model
{
    use HasFactory;
    protected $fillable = [
        'teamplayer_id',
        'position_name',
        'meta',
        'sort'
    ];

    public function teamPlayer()
    {
        return $this->belongsTo(TeamPlayer::class, 'teamplayer_id');
    }
}
