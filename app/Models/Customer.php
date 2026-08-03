<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Customer extends Model
{

    protected $fillable = [

        'name',
        'phone',
        'email',
        'address',
        'loyalty_points',
        'total_spent'

    ];


    protected $casts = [

        'loyalty_points'=>'integer',
        'total_spent'=>'decimal:2'

    ];

}