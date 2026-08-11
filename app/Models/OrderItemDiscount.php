<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemDiscount extends Model
{
    protected $fillable = [
        'order_item_id',
        'discount_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(
            OrderItem::class,
            'order_item_id'
        );
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(
            Discount::class,
            'discount_id'
        );
    }
}