<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistrationEmail extends Model
{
    protected $fillable = [
        'event_registration_id',
        'email_type',
        'recipient_email',
        'status',
        'queued_at',
        'sent_at',
        'failed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'event_registration_id');
    }
}