<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PREPARING = 'preparing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
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
        'order_sequence'
    ];

    protected $casts = [

        'ordered_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function orderType()
    {
        return $this->belongsTo(
            OrderType::class
        );
    }
    public function orderSource()
    {
        return $this->belongsTo(OrderSource::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function table()
    {
        return $this->belongsTo(
            RestaurantTable::class,
            'table_id'
        );
    }

    public function cashier()
    {
        return $this->belongsTo(
            User::class,
            'cashier_id'
        );
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function refund()
    {
        return $this->hasMany(
            Refund::class
        );
    }
    public function delivery()
    {
        return $this->hasOne(
            DeliveryOrder::class
        );
    }

    public function discounts()
    {
        return $this->hasMany(
            OrderDiscount::class
        );
    }
    
    public function printJobs()
    {
        return $this->hasMany(PrintJob::class);
    }
}
