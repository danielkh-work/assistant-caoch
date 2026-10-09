<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaguePlayOverride extends Model
{
    protected $fillable = [
        'league_id',
        'global_play_id',
        'status',
        'customized_play_id',
        'created_by_user_id',
    ];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function globalPlay(): BelongsTo
    {
        return $this->belongsTo(Play::class, 'global_play_id');
    }

    public function customizedPlay(): BelongsTo
    {
        return $this->belongsTo(Play::class, 'customized_play_id');
    }
}
