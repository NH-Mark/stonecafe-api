<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Printer extends Model
{
    protected $fillable = [
        'name',
        'code',
        'system_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
