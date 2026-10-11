<?php

namespace App\Mail;

use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ThrowdownSelectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EventRegistration $registration
    ) {}

    public function envelope(): \Illuminate\Mail\Mailables\Envelope
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: 'Congratulations, you are in! Brewers Throwdown by Stone & Fuel, 16 November'
        );
    }

    public function content(): \Illuminate\Mail\Mailables\Content
    {
        return new \Illuminate\Mail\Mailables\Content(
            view: 'emails.events.throwdown-selected',
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