<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsocketPracticeScoreboard extends Model
{
   
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'session_id' => 'integer',
        'timer_remaining' => 'integer',
        'quarter' => 'integer',
    ];
}
