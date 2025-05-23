<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formation extends Model
{
    use HasFactory;

    public function formation_data()
    {
      return  $this->hasMany(FormationData::class,'formation_id');
    }
}
