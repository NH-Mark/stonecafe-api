<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RestaurantTable extends Model
{
    protected $fillable = [
        'name',
        'capacity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'status' => 'boolean',
        ];
    }

    public function diningSessions(): HasMany
    {
        return $this->hasMany(
            DiningSession::class,
            'table_id'
        );
    }

    public function openDiningSession(): HasOne
    {
        return $this->hasOne(
            DiningSession::class,
            'table_id'
        )->whereIn('status', [
            'open',
            'billing',
        ]);
    }
}