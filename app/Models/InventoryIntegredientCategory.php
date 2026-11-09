<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryIntegredientCategory extends Model
{
    use SoftDeletes;

    protected $table = 'inventory_ingredient_categories';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];
}
