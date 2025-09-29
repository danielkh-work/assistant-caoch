<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penality extends Model
{
    use HasFactory;
    protected $fillable = [
        'league_id',
        'game_id',
        'penalty_type_id',
        'category',
        'severity',
        'yardage_penalty',
        'automatic_first_down',
        'loss_down',
        'accept_reject',
        'replay_down',
        'new_down',
        'new_ball_sport',
        'play_time',
        'setuation',
        'referee',
        'notes_description',
    ];
}
