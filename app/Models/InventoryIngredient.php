<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryIngredient extends Model
{
    use SoftDeletes;

    protected $table = 'inventory_ingredients';

    protected $fillable = [
        'ingredient_type',
        'name',
        'sku',
        'description',
        'category_id',
        'measurement_unit_id',
        'buying_unit_name',
        'has_sub_unit',
        'sub_unit_name',
        'buying_unit_quantity',
        'unit_to_sub_unit_quantity',
        'sub_unit_to_base_quantity',
        'net_quantity',
        'last_buying_price',
        'cost_per_unit',
        'is_active',
    ];

    protected $casts = [
        'has_sub_unit' => 'boolean',
        'buying_unit_quantity' => 'decimal:4',
        'unit_to_sub_unit_quantity' => 'decimal:4',
        'sub_unit_to_base_quantity' => 'decimal:4',
        'net_quantity' => 'decimal:4',
        'last_buying_price' => 'decimal:4',
        'cost_per_unit' => 'decimal:6',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryIntegredientCategory::class, 'category_id');
    }

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(InventoryMeasurementUnit::class, 'measurement_unit_id');
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(InventoryIngredientSupplier::class, 'ingredient_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(InventoryLocationIngredient::class, 'ingredient_id');
    }

    public function menuItemIngredients(): HasMany
    {
        return $this->hasMany(MenuItemIngredient::class, 'ingredient_id');
    }

    public function calculateCostPerUnit(): float
    {
        $buyingPrice = (float) ($this->last_buying_price ?? 0);

        if ($buyingPrice <= 0) {
            return 0.0;
        }

        $baseQty = (float) ($this->net_quantity ?? 0);

        if ($this->has_sub_unit) {
            $unitToSub = (float) ($this->unit_to_sub_unit_quantity ?? 0);
            $subToBase = (float) ($this->sub_unit_to_base_quantity ?? 0);

            $baseQty = $unitToSub * $subToBase;
        }

        if ($baseQty <= 0) {
            $baseQty = (float) ($this->buying_unit_quantity ?? 0);
        }

        if ($baseQty <= 0) {
            return 0.0;
        }

        return $buyingPrice / $baseQty;
    }
}
