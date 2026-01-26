<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id', 'mobile_user_id', 'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'mobile_user_id');
    }
}
