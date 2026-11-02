<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MenuCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image',
        'sort_order',
        'active',
        'parent_id',
        'name_ar',
        'description_ar'
    ];

    protected $casts = [
        'active' => 'boolean',
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

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(
            Location::class,
            'menu_category_location_statuses'
        )
            ->withPivot('active')
            ->withTimestamps();
    }
    
}
