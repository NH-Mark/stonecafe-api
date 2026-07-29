<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemDiscount extends Model
{
    protected $fillable = ['order_id ','discount_id ','amount'];
}
