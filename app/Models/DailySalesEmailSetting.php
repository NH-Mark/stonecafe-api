<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailySalesEmailSetting extends Model
{
    protected $fillable = [
        'enabled',
        'recipients',
        'send_time',
        'from_date',
        'to_date',
        'date_range'
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'recipients' => 'array',
    ];
}   
