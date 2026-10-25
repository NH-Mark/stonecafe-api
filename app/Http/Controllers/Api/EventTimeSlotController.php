<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;

class EventTimeSlotController extends Controller
{
    public function index(Event $event): JsonResponse
    {
        $slots = $event->timeSlots()
            ->orderBy('start_time')
            ->get();

        $data = $slots->map(function ($slot) {
            $reservedSeats = $slot->registrations()
                ->whereIn('status', [
                    'pending',
                    'confirmed',
                ])
                ->where(function ($query) {
                    $query->whereNull('payment_status')
                        ->orWhere('payment_status', '!=', 'failed');
                })
                ->sum('seat_count');

            $availableSeats = $slot->is_active
                ? max(
                    0,
                    $slot->capacity - $reservedSeats
                )
                : 0;

            $canSelectSeats =
                (bool) $slot->is_active &&
                $availableSeats > 0;

            return [
                'id' => $slot->id,
                'name' => $slot->name,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'capacity' => $slot->capacity,
                'reserved_seats' => $reservedSeats,
                'available_seats' => $availableSeats,
                'is_full' => !$canSelectSeats,
                'is_active' => (bool) $slot->is_active,
                'can_select_seats' => $canSelectSeats,
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }
}