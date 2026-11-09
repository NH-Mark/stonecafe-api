<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryIngredientSupplier extends Model
{
    protected $table = 'inventory_ingredient_suppliers';

    protected $fillable = [
        'ingredient_id',
        'supplier_id',
        'price',
        'buying_unit',
        'has_sub_unit',
        'sub_unit_name',
        'unit_to_sub_unit_quantity',
        'sub_unit_to_base_quantity',
        'net_quantity',
        'is_default',
    ];

    protected $casts = [
        'price' => 'decimal:4',
        'has_sub_unit' => 'boolean',
        'unit_to_sub_unit_quantity' => 'decimal:4',
        'sub_unit_to_base_quantity' => 'decimal:4',
        'net_quantity' => 'decimal:4',
        'is_default' => 'boolean',
    ];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(InventoryIngredient::class, 'ingredient_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(InventorySupplier::class, 'supplier_id');
    }
}
