<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodSymbol extends Model
{
    protected $fillable = [

        'name',
        'icon',
        'active',

    ];


    protected $casts = [

        'active'=>'boolean',

    ];
}
