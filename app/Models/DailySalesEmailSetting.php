<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailySalesEmailSetting extends Model
{
    protected $fillable = [
        'enabled',
        'recipients',
        'send_time',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'recipients' => 'array',
    ];
}   
