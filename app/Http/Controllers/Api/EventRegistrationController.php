<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\EventRegistrationConfirmed;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventTimeSlot;
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

            // Omakase
            'event_time_slot_id' => [
                'nullable',
                'integer',
                'exists:event_time_slots,id',
            ],

            'seat_count' => [
                'nullable',
                'integer',
                'min:1',
                'max:5',
            ],

            'dietary_needs' => [
                'nullable',
                'string',
                'max:1000',
            ],

            // Throwdown / event-specific fields
            'metadata' => [
                'nullable',
                'array',
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

        /*
         * Prevent duplicate registration.
         */
        $existingRegistration = EventRegistration::query()
            ->where('event_id', $event->id)
            ->where('email', $validated['email'])
            ->first();

        if ($existingRegistration) {
            throw ValidationException::withMessages([
                'email' => 'You are already registered for this event.',
            ]);
        }

        $isPaid = (float) $event->fee > 0;

        /*
         * Omakase requires a time slot and seat count.
         */
        $requiresTimeSlot = $event->registration_type === 'omakase_booking';

        if ($requiresTimeSlot) {
            if (empty($validated['event_time_slot_id'])) {
                throw ValidationException::withMessages([
                    'event_time_slot_id' => 'Please select a session.',
                ]);
            }

            if (empty($validated['seat_count'])) {
                throw ValidationException::withMessages([
                    'seat_count' => 'Please select the number of seats.',
                ]);
            }
        }

        /*
         * Create registration.
         *
         * The time slot is locked inside the transaction so
         * availability is checked against the latest database state.
         */
        $registration = DB::transaction(function () use (
            $validated,
            $event,
            $isPaid,
            $requiresTimeSlot
        ) {
            $slot = null;

            if ($requiresTimeSlot) {
                $slot = EventTimeSlot::query()
                    ->where('id', $validated['event_time_slot_id'])
                    ->where('event_id', $event->id)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (!$slot) {
                    throw ValidationException::withMessages([
                        'event_time_slot_id' =>
                            'The selected session is no longer available.',
                    ]);
                }

                /*
                 * Recalculate reserved seats while the slot is locked.
                 */
                $reservedSeats = EventRegistration::query()
                    ->where('event_time_slot_id', $slot->id)
                    ->whereIn('status', [
                        'pending',
                        'confirmed',
                    ])
                    ->sum('seat_count');

                $availableSeats = max(
                    0,
                    $slot->capacity - $reservedSeats
                );

                $requestedSeats = (int) $validated['seat_count'];

                if ($requestedSeats > $availableSeats) {
                    throw ValidationException::withMessages([
                        'seat_count' =>
                            "Only {$availableSeats} seat" .
                            ($availableSeats === 1 ? '' : 's') .
                            " remaining in this session.",
                    ]);
                }
            }

            /*
             * Check event-level capacity if configured.
             */
            if ($event->capacity !== null) {
                $reservedEventSeats = EventRegistration::query()
                    ->where('event_id', $event->id)
                    ->whereIn('status', [
                        'pending',
                        'confirmed',
                    ])
                    ->sum('seat_count');

                $requestedSeats = $requiresTimeSlot
                    ? (int) $validated['seat_count']
                    : 1;

                if (
                    $reservedEventSeats + $requestedSeats >
                    $event->capacity
                ) {
                    throw ValidationException::withMessages([
                        'event_id' =>
                            'This event is currently full.',
                    ]);
                }
            }

            return EventRegistration::create([
                'event_id' => $event->id,

                'event_time_slot_id' => $slot?->id,

                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],

                'seat_count' => $requiresTimeSlot
                    ? (int) $validated['seat_count']
                    : 1,

                'selection_status' => $event->registration_type ===
                    'throwdown_application'
                    ? 'pending'
                    : 'selected',

                'metadata' => array_merge(
                    $validated['metadata'] ?? [],
                    [
                        'dietary_needs' => $validated['dietary_needs'] ?? null,
                    ]
                ),

                'status' => $isPaid
                    ? 'pending'
                    : 'confirmed',

                'payment_status' => $isPaid
                    ? 'pending'
                    : 'not_required',

                /*
                 * Always use the event price from Laravel.
                 */
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
        // if (!$isPaid) {
            $registration->load([
                'event',
                'timeSlot',
            ]);

            Mail::to($registration->email)
                ->send(
                    new EventRegistrationConfirmed($registration)
                );

            // return response()->json([
            //     'message' =>
            //         'Registration completed successfully.',

            //     'data' => [
            //         'id' => $registration->id,
            //         'event_id' => $registration->event_id,
            //         'status' => $registration->status,
            //         'payment_status' =>
            //             $registration->payment_status,
            //         'payment_amount' => null,
            //         'payment_currency' => null,
            //         'payment_url' => null,
            //     ],
            // ], 201);
        // }

        /*
         * PAID EVENT
         *
         * Create the payment session here.
         */
        $paymentUrl = null;

        /*
         * Example later:
         *
         * $paymentUrl = $this->createPaymentSession(
         *     $registration,
         *     $event
         * );
         *
         * $registration->update([
         *     'payment_reference' => $paymentReference,
         *     'payment_expires_at' => now()->addMinutes(10),
         * ]);
         */

        return response()->json([
            'message' =>
                'Registration created. Payment is required.',

            'data' => [
                'id' => $registration->id,
                'event_id' => $registration->event_id,
                'status' => $registration->status,
                'payment_status' =>
                    $registration->payment_status,

                'payment_amount' =>
                    $registration->payment_amount,

                'payment_currency' =>
                    $registration->payment_currency,

                'payment_url' => $paymentUrl,
            ],
        ], 201);
    }
}