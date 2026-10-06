<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'date',
        'start_time',
        'end_time',
        'location',
        'fee',
        'currency',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'fee' => 'decimal:2',
        'is_active' => 'boolean',
        'capacity' => 'integer',
    ];

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }
}