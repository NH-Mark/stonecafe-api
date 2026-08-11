<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintJob extends Model
{
    protected $fillable = [
        'order_id',
        'printer',
        'type',
        'status',
        'printed_at',
        'payment_batch_id',
        'dining_session_id',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(
            Order::class
        );
    }

    public function orders()
    {
        return $this->belongsToMany(
            Order::class,
            'print_job_orders'
        )->withTimestamps();
    }

    public function diningSession()
    {
        return $this->belongsTo(
            DiningSession::class
        );
    }
}