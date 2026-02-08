<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OffensiveTargetStrength extends Model
{
    use HasFactory;

       // The table name (optional if it matches Laravel convention)
    protected $table = 'offensive_target_strengths';

    // Fillable fields for mass assignment
    protected $fillable = [
        'play_id',
        'code',
        'strength',
        'target_offensive_id',
        'target_defensive_id',
        'total_strength',
    ];

    // Optional: if you want to define relationships

    public function play()
    {
        return $this->belongsTo(Play::class, 'play_id');
    }

    public function offensivePosition()
    {
        return $this->belongsTo(OffensivePosition::class, 'target_offensive_id');
    }

    public function defensivePosition()
    {
        return $this->belongsTo(DefensivePosition::class, 'target_defensive_id');
    }
}
