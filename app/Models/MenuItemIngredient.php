<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItemIngredient extends Model
{
    protected $table = 'menu_item_ingredients';

    protected $fillable = [
        'menu_item_id',
        'ingredient_id',
        'unit_name',
        'cost_per_unit',
        'quantity',
        'subtotal',
        'sort_order',
    ];

    protected $casts = [
        'cost_per_unit' => 'decimal:6',
        'quantity' => 'decimal:4',
        'subtotal' => 'decimal:4',
        'sort_order' => 'integer',
    ];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(InventoryIngredient::class, 'ingredient_id');
    }
}
