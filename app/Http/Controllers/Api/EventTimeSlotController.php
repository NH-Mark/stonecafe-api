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
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        $data = $slots->map(function ($slot) {
            $reservedSeats = $slot->registrations()
                ->whereIn('status', [
                    'pending',
                    'confirmed',
                ])
                ->sum('seat_count');

            $availableSeats = max(
                0,
                $slot->capacity - $reservedSeats
            );

            return [
                'id' => $slot->id,
                'name' => $slot->name,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'capacity' => $slot->capacity,
                'reserved_seats' => $reservedSeats,
                'available_seats' => $availableSeats,
                'is_full' => $availableSeats <= 0,
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }
}