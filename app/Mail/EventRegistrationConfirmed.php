<?php

namespace App\Mail;

use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EventRegistrationConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public EventRegistration $registration;

    public function __construct(EventRegistration $registration)
    {
        $this->registration = $registration;
    }

    public function build()
    {
        $registration = $this->registration;
        $event = $registration->event;

        if ($event->registration_type === 'omakase_booking') {
            return $this
                ->subject(
                    'Your seat is confirmed: Omakase Coffee Experience with Mariam Erin'
                )
                ->view('emails.events.omakase-confirmed')
                ->with([
                    'registration' => $registration,
                    'event' => $event,
                    'firstName' => $this->firstName(),
                    'reference' => $this->reference(),
                    'sessionTime' => $this->sessionTime(),
                    'seatCount' => $registration->seat_count,
                    'amount' => $registration->payment_amount,
                ]);
        }

        if ($event->registration_type === 'throwdown_application') {
            return $this
                ->subject(
                    'We have received your registration: Brewers Throwdown by Stone & Fuel'
                )
                ->view('emails.events.throwdown-application')
                ->with([
                    'registration' => $registration,
                    'event' => $event,
                    'firstName' => $this->firstName(),
                ]);
        }

        return $this
            ->subject('Your Stone Specialty Coffee registration')
            ->view('emails.events.default')
            ->with([
                'registration' => $registration,
                'event' => $event,
                'firstName' => $this->firstName(),
            ]);
    }

    private function firstName(): string
    {
        return explode(' ', trim($this->registration->full_name))[0];
    }

    private function reference(): string
    {
        return $this->registration->reference
            ?? 'REG-' . str_pad(
                (string) $this->registration->id,
                6,
                '0',
                STR_PAD_LEFT
            );
    }

    private function sessionTime(): string
    {
        $slot = $this->registration->timeSlot;

        if (!$slot) {
            return '—';
        }

        // Adjust these column names to your EventTimeSlot model.
        if ($slot->start_time && $slot->end_time) {
            return $slot->start_time . ' – ' . $slot->end_time;
        }

        return $slot->start_time ?? '—';
    }
}