<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventRegistrationResource;
use App\Mail\EventRegistrationConfirmed;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventTimeSlot;
use App\Services\SkipCashService;
use App\Exports\EventRegistrationsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class EventRegistrationController extends Controller
{

    public function index(Request $request)
    {
        $perPage = min(
            $request->integer('per_page', 10),
            100
        );

        $query = EventRegistration::query()
            ->with([
                'event',
                'timeSlot',
                'emailLogs',
            ]);

        /*
         * Global search.
         */
        $query->when(
            $request->filled('search'),
            function ($query) use ($request) {

                $search = $request->search;

                $query->where(function ($query) use ($search) {

                    $query->where(
                        'full_name',
                        'like',
                        "%{$search}%"
                    )

                        ->orWhere(
                            'email',
                            'like',
                            "%{$search}%"
                        )

                        ->orWhere(
                            'phone',
                            'like',
                            "%{$search}%"
                        )

                        ->orWhere(
                            'company',
                            'like',
                            "%{$search}%"
                        )

                        ->orWhereHas(
                            'event',
                            function ($query) use ($search) {

                                $query->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                );
                            }
                        )

                        ->orWhereHas(
                            'timeSlot',
                            function ($query) use ($search) {

                                $query->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                );
                            }
                        );
                });
            }
        );

        /*
         * DataTable column filters.
         */
        $filters = $request->input(
            'filters',
            []
        );

        foreach ($filters as $filter) {

            $column = $filter['id'] ?? null;
            $value = $filter['value'] ?? null;

            if (
                !$column ||
                $value === null ||
                $value === ''
            ) {
                continue;
            }

            switch ($column) {

                case 'full_name':

                    $query->where(
                        'full_name',
                        'like',
                        "%{$value}%"
                    );

                    break;

                case 'email':

                    $query->where(
                        'email',
                        'like',
                        "%{$value}%"
                    );

                    break;

                case 'phone':

                    $query->where(
                        'phone',
                        'like',
                        "%{$value}%"
                    );

                    break;

                case 'company':

                    $query->where(
                        'company',
                        'like',
                        "%{$value}%"
                    );

                    break;

                case 'event_id':

                    $query->whereHas(
                        'event',
                        function ($query) use ($value) {
                            $query->where(
                                'name',
                                'like',
                                "%{$value}%"
                            );
                        }
                    );

                    break;

                case 'event_time_slot_id':

                    $query->where(
                        'event_time_slot_id',
                        $value
                    );

                    break;

                case 'status':

                    $query->where(
                        'status',
                        $value
                    );

                    break;

                case 'payment_status':

                    $query->where(
                        'payment_status',
                        $value
                    );

                    break;
            }
        }

        $registrations = $query
            ->latest()
            ->paginate($perPage);

        return EventRegistrationResource::collection(
            $registrations
        );
    }


    /**
     * Display a single registration.
     */
    public function show(
        EventRegistration $eventRegistration
    ) {
        $eventRegistration->load([
            'event',
            'timeSlot',
        ]);

        return response()->json([
            'data' => $eventRegistration,
        ]);
    }


    /**
     * Update registration status.
     */
    public function updateStatus(
        Request $request,
        EventRegistration $eventRegistration
    ) {
        $validated = $request->validate([
            'status' => [
                'required',
                'in:pending,confirmed,cancelled',
            ],
        ]);

        $eventRegistration->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Registration status updated successfully.',
            'data' => $eventRegistration->fresh([
                'event',
                'timeSlot',
            ]),
        ]);
    }


    /**
     * Delete a registration.
     */
    public function destroy(
        EventRegistration $eventRegistration
    ) {
        $eventRegistration->delete();

        return response()->json([
            'message' => 'Registration deleted successfully.',
        ]);
    }


    public function store(
        Request $request,
        SkipCashService $skipcash
    )
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

        $isPaid = (float) $event->fee > 0;

        /*
         * Omakase requires a time slot and seat count.
         */
        $requiresTimeSlot = $event->registration_type === 'omakase_booking';

        /*
         * Prevent duplicate registration.
         *
         * For paid Omakase bookings, allow retry when the previous
         * registration payment has failed.
         */
        $existingRegistration = EventRegistration::query()
            ->where('event_id', $event->id)
            ->where('email', $validated['email'])
            ->first();

        if ($existingRegistration) {
            $canRetryFailedOmakase =
                $requiresTimeSlot &&
                $isPaid &&
                $existingRegistration->payment_status === 'failed';

            if (!$canRetryFailedOmakase) {
                throw ValidationException::withMessages([
                    'email' => 'You are already registered for this event.',
                ]);
            }

            $existingRegistration->delete();
        }

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
                    ->where(function ($query) {
                        $query->whereNull('payment_status')
                            ->orWhere('payment_status', '!=', 'failed');
                    })
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
                    ->where(function ($query) {
                        $query->whereNull('payment_status')
                            ->orWhere('payment_status', '!=', 'failed');
                    })
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

                // 'selection_status' => $event->registration_type ===
                //     'throwdown_application'
                //     ? 'pending'
                //     : 'selected',

                'metadata' => array_merge(
                    $validated['metadata'] ?? [],
                    [
                        'dietary_needs' => $validated['dietary_needs'] ?? null,
                    ]
                ),

                'status' => 'pending',

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

        if (!($requiresTimeSlot && $isPaid)) {
            Mail::to($registration->email)
                ->send(
                    new EventRegistrationConfirmed($registration)
                );
        }


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
         * PAID Omakase registration:
         * create a SkipCash session and return payment URL.
         */
        $paymentUrl = null;

        if ($requiresTimeSlot && $isPaid) {
            $transactionId = 'OMREG-' . $registration->id;

            try {
                $result = $skipcash->createPayment(
                    (float) $registration->payment_amount,
                    $transactionId,
                    (object) [
                        'name' => $registration->full_name,
                        'phone' => $registration->phone,
                        'email' => $registration->email,
                    ]
                );

                $paymentUrl = $result['payUrl'] ?? null;

                $registration->update([
                    'payment_reference' => $result['id'] ?? $transactionId,
                    'payment_transaction_id' => $transactionId,
                    'payment_status' => $paymentUrl
                        ? 'pending'
                        : 'failed',
                    'status' => $paymentUrl
                        ? 'pending'
                        : 'cancelled',
                ]);

                if (!$paymentUrl) {
                    Log::error('SkipCash payment URL missing for event registration', [
                        'registration_id' => $registration->id,
                        'result' => $result,
                    ]);
                }
            } catch (\Throwable $exception) {
                Log::error('SkipCash payment creation failed for event registration', [
                    'registration_id' => $registration->id,
                    'error' => $exception->getMessage(),
                ]);

                $registration->update([
                    'payment_status' => 'failed',
                    'status' => 'cancelled',
                ]);
            }
        }

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

    public function updateSelectionStatus(
        Request $request,
        EventRegistration $eventRegistration
    ) {
        $validated = $request->validate([
            'selection_status' => [
                'required',
                Rule::in([
                    'pending',
                    'selected',
                    'not_selected',
                    'waitlisted',
                ]),
            ],
        ]);

        $eventRegistration->update([
            'selection_status' => $validated['selection_status'],
        ]);

        return response()->json([
            'message' => 'Selection status updated successfully.',
            'data' => $eventRegistration->fresh()->load([
                'event',
                'timeSlot',
            ]),
        ]);
    }

    public function skipcashWebhook(Request $request)
    {
        Log::info('SkipCash Event Registration Webhook Received', [
            'payload' => $request->all(),
        ]);

        $paymentId = $request->input('PaymentId');
        $transactionId = $request->input('TransactionId');
        $statusId = (int) $request->input('StatusId');

        if (!$transactionId) {
            return response()->json([
                'message' => 'Missing TransactionId',
            ], 400);
        }

        $registration = EventRegistration::query()
            ->where('payment_transaction_id', $transactionId)
            ->first();

        if (!$registration) {
            Log::error('Event registration not found for SkipCash webhook', [
                'transaction_id' => $transactionId,
            ]);

            return response()->json([
                'message' => 'Registration not found',
            ], 404);
        }

        if ($statusId === 2) {
            $wasNotPaid = $registration->payment_status !== 'paid';

            if ($wasNotPaid) {
                $registration->update([
                    'payment_status' => 'paid',
                    'payment_reference' => $paymentId ?: $registration->payment_reference,
                    'paid_at' => now(),
                ]);

                $registration->loadMissing([
                    'event',
                    'timeSlot',
                ]);

                if (
                    $registration->event?->registration_type === 'omakase_booking'
                ) {
                    Mail::to($registration->email)
                        ->send(
                            new EventRegistrationConfirmed($registration)
                        );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment marked as paid.',
            ]);
        }

        $registration->update([
            'payment_status' => 'failed',
            'status' => 'cancelled',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment marked as failed.',
        ]);
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'filters' => ['nullable', 'string'],
        ]);

        $filters = [];

        if (!empty($validated['filters'])) {
            $decoded = json_decode($validated['filters'], true);

            if (!is_array($decoded)) {
                return response()->json([
                    'message' => 'Invalid filters format.',
                ], 422);
            }

            $filters = $decoded;
        }

        $export = new EventRegistrationsExport(
            (int) ($validated['event_id'] ?? 0),
            $validated['search'] ?? '',
            $filters,
        );

        return Excel::download(
            $export,
            'event-registrations-' . now()->format('Y-m-d-His') . '.xlsx'
        );
    }
}
