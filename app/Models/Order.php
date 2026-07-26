<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
     use HasFactory;
    protected $fillable = [
        'order_no',
        'location_id',
        'customer_id',
        'order_type_id',
        'order_source_id',
        'table_id',
        'cashier_id',
        'status',
        'payment_status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'service_charge',
        'total_amount',
        'notes',
        'ordered_at',
    ];

    public function orderType()
    {
        return $this->belongsTo(
            OrderType::class
        );
    }
}
