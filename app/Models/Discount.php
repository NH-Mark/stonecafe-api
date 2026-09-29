<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{

  protected $casts = [
        'value' => 'float',
    ];
    protected $fillable = [
        'name',
        'type',
        'value',
        'status',
    ];
}
