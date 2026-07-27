<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_method_id',
        'amount',
        'reference',
        'received_by',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];


    public function order()
    {
        return $this->belongsTo(Order::class);
    }


    public function paymentMethod()
    {
        return $this->belongsTo(
            PaymentMethod::class
        );
    }


    public function receivedBy()
    {
        return $this->belongsTo(
            User::class,
            'received_by'
        );
    }
}