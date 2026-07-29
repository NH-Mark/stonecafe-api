<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDiscount extends Model
{
    protected $fillable = ['order_id','discount_id','amount'];

     public function discount()
    {
        return $this->belongsTo(Discount::class);
    }
}
