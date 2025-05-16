<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayGameMode extends Model
{
    use HasFactory;

    public function logs()
    {
        return $this->hasMany(PlayGameLog::class, 'game_id');
    }
}
