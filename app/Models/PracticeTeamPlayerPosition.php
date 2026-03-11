<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PracticeTeamPlayerPosition extends Model
{
    use HasFactory;
    protected $table = 'practice_team_player_positions';

    protected $fillable = [
        'practice_team_player_id',
        'position_name',
        'meta',
        'sort'
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function practiceTeamPlayer()
    {
        return $this->belongsTo(PracticeTeamPlayer::class, 'practice_team_player_id');
    }
}
