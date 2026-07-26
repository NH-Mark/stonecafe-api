<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image',
        'sort_order',
        'active',
        'parent_id',
    ];

    public function parent()
    {
        return $this->belongsTo(MenuCategory::class);
    }

    public function children()
    {
        return $this->hasMany(MenuCategory::class, 'parent_id');
    }

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class);
    }
    
}
