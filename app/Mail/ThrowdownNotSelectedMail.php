<?php

namespace App\Mail;

use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ThrowdownNotSelectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EventRegistration $registration
    ) {}

    public function envelope(): \Illuminate\Mail\Mailables\Envelope
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: 'Thank You for Applying – Brewers Throwdown by Stone & Fuel'
        );
    }

    public function content(): \Illuminate\Mail\Mailables\Content
    {
        return new \Illuminate\Mail\Mailables\Content(
            view: 'emails.events.throwdown-not-selected',
            with: [
                'registration' => $this->registration,
                'firstName' => preg_split(
                    '/\s+/',
                    trim($this->registration->full_name)
                )[0] ?? $this->registration->full_name,
                'amount' => $this->registration->payment_amount ?? 'TBC',
                'paymentDeadline' => config(
                    'events.throwdown_payment_deadline',
                    'To be announced'
                ),
                'arrivalTime' => config(
                    'events.throwdown_arrival_time',
                    '6:00 PM'
                ),
                'paymentUrl' => null,
            ]
        );
    }
}