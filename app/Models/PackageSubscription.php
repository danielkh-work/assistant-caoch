<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageSubscription extends Model
{
    use HasFactory;
    protected $fillable =[
        'subscription_plan_id',
        'user_id',
        'package_date',
        'end_date',
        'is_expire'
    ];
}
