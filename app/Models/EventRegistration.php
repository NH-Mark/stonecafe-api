<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EventRegistration extends Model
{
    protected $fillable = [
        'event_id',
        'full_name',
        'email',
        'phone',
        'gender',
        'company',
        'status',
        'payment_status',
        'payment_amount',
        'payment_currency',
        'payment_reference',
        'payment_transaction_id',
        'paid_at',
        'event_time_slot_id',
        'seat_count',
        'metadata'
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'paid_at' => 'datetime',
         'metadata' => 'array',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
    
    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(EventTimeSlot::class);
    }
}