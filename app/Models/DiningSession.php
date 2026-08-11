<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiningSession extends Model
{
    protected $fillable = [
        'table_id',
        'guest_count',
        'status',
        'subtotal',
        'discount_amount',
        'total',
        'opened_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'guest_count' => 'integer',

            'subtotal' => 'decimal:2',

            'discount_amount' => 'decimal:2',

            'total' => 'decimal:2',

            'opened_at' => 'datetime',

            'closed_at' => 'datetime',
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(
            RestaurantTable::class,
            'table_id'
        );
    }

    public function orders(): HasMany
    {
        return $this->hasMany(
            Order::class
        );
    }
}
