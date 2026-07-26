<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{

    protected $fillable = [

        'menu_category_id',
        'name',
        'description',
        'image',
        'price',
        'cost_price',
        'active',
        'available',
        'sort_order',
        'sku',
        'barcode',
        'food_symbols',
        'menu_item_tags',
        'modifier_groups'
    ];


    protected $casts = [
        
        'active' => 'boolean',
        'available' => 'boolean',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
    ];



    public function menu_category()
    {
        return $this->belongsTo(MenuCategory::class);
    }



    public function modifierGroups()
    {
        return $this->belongsToMany(ModifierGroup::class)
            ->withPivot([
                'selection_type',
                'required',
                'min_selection',
                'max_selection',
            ]);
    }

    public function foodSymbols()
    {
        return $this->belongsToMany(FoodSymbol::class);
    }

    public function menuItemTags()
    {
        return $this->belongsToMany(MenuItemTag::class);
    }
}
