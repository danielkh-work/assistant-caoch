<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OffensivePosition extends Model
{
    use HasFactory;

    protected $table = 'offensive_positions';

    protected $fillable = [
        'name', // Assuming your table has a 'name' column
        // Add more columns as needed
    ];

    public $timestamps = false; // Disable if your table doesn't use created_at/updated_at

    public function playTargetOffensivePlayers()
    {
        return $this->hasMany(PlayTargetOffensivePlayer::class, 'offensive_position_id');
    }
}
