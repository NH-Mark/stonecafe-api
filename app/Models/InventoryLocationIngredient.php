<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLocationIngredient extends Model
{
    protected $table = 'inventory_location_ingredients';

    protected $fillable = [
        'ingredient_id',
        'location_id',
        'opening_balance',
        'reorder_level',
        'track_inventory',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:4',
        'reorder_level' => 'decimal:4',
        'track_inventory' => 'boolean',
    ];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(InventoryIngredient::class, 'ingredient_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
