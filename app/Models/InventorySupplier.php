<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventorySupplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'supplier_code',
        'name',
        'email',
        'phone',
        'alternate_phone',
        'tax_number',
        'address',
        'city',
        'country',
        'notes',
        'is_active',
    ];
}
