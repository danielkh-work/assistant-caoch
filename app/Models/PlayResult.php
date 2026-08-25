<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlayResult extends Model
{
    protected $guarded = [];
    use HasFactory, SoftDeletes;
}
