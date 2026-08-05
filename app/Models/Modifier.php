<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modifier extends Model
{
    protected $fillable = ['modifier_group_id','name','price','active','name_ar'];
    
    protected $casts = [
        'price' => 'decimal:2',
        'active' => 'boolean',
    ];



    public function group()
    {
        return $this->belongsTo(
            ModifierGroup::class,
            'modifier_group_id'
        );
    }
}
