<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintJobItem extends Model
{
     protected $fillable = [
        'print_job_id',
        'order_item_id',
        'quantity',
    ];

    public function printJob()
    {
        return $this->belongsTo(
            PrintJob::class
        );
    }

    public function orderItem()
    {
        return $this->belongsTo(
            OrderItem::class
        );
    }
}
