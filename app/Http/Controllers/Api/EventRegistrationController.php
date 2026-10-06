<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Mail\EventRegistrationConfirmed;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class EventRegistrationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => [
                'required',
                'integer',
                'exists:events,id',
            ],

            'full_name' => [
                'required',
                'string',
                'min:2',
                'max:100',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'phone' => [
                'required',
                'string',
                'min:7',
                'max:30',
            ],

            'gender' => [
                'required',
                'in:male,female',
            ],

            'company' => [
                'nullable',
                'string',
                'max:150',
            ],
        ]);

        $event = Event::query()
            ->where('id', $validated['event_id'])
            ->where('is_active', true)
            ->first();

        if (!$event) {
            throw ValidationException::withMessages([
                'event_id' => 'This event is no longer available.',
            ]);
        }

        // Check for an existing registration.
        $existingRegistration = EventRegistration::query()
            ->where('event_id', $event->id)
            ->where('email', $validated['email'])
            ->first();

        if ($existingRegistration) {
            throw ValidationException::withMessages([
                'email' => 'You are already registered for this event.',
            ]);
        }

        // Check event capacity if one is configured.
        if ($event->capacity !== null) {
            $registrationCount = EventRegistration::query()
                ->where('event_id', $event->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->count();

            if ($registrationCount >= $event->capacity) {
                throw ValidationException::withMessages([
                    'event_id' => 'This event is currently full.',
                ]);
            }
        }

        $isPaid = (float) $event->fee > 0;

        $registration = DB::transaction(function () use (
            $validated,
            $event,
            $isPaid
        ) {
            return EventRegistration::create([
                'event_id' => $event->id,

                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'gender' => $validated['gender'],
                'company' => $validated['company'] ?? null,

                'status' => $isPaid
                    ? 'pending'
                    : 'confirmed',

                'payment_status' => $isPaid
                    ? 'pending'
                    : 'not_required',

                // IMPORTANT:
                // These values come from Laravel's event record,
                // not from the frontend.
                'payment_amount' => $isPaid
                    ? $event->fee
                    : null,

                'payment_currency' => $isPaid
                    ? $event->currency
                    : null,
            ]);
        });

        /*
         * FREE EVENT
         */
        if (!$isPaid) {

           $registration->load('event');

            Mail::to($registration->email)
                ->send(new EventRegistrationConfirmed($registration));
                
            return response()->json([
                'message' => 'Registration completed successfully.',
                'data' => [
                    'id' => $registration->id,
                    'event_id' => $registration->event_id,
                    'status' => $registration->status,
                    'payment_status' => $registration->payment_status,
                    'payment_amount' => null,
                    'payment_currency' => null,
                    'payment_url' => null,
                ],
            ], 201);
        }

        /*
         * PAID EVENT
         *
         * Payment gateway integration will be added here.
         *
         * Example:
         *
         * $paymentUrl = $this->createPaymentSession($registration, $event);
         */

        $paymentUrl = null;

        return response()->json([
            'message' => 'Registration created. Payment is required.',
            'data' => [
                'id' => $registration->id,
                'event_id' => $registration->event_id,
                'status' => $registration->status,
                'payment_status' => $registration->payment_status,
                'payment_amount' => $registration->payment_amount,
                'payment_currency' => $registration->payment_currency,
                'payment_url' => $paymentUrl,
            ],
        ], 201);
    }
}