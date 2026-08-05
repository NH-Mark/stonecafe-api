<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModifierGroup extends Model
{
    protected $fillable = ['name','required','min_selection','max_selection','active','selection_type','name_ar'];

    protected $casts = [
        'active' => 'boolean',
        'required' => 'boolean',
    ];
    
    public function modifiers()
    {
        return $this->hasMany(
            Modifier::class
        );
    }

    public function menuItems()
    {
        return $this->belongsToMany(
            MenuItem::class
        );
    }
}
