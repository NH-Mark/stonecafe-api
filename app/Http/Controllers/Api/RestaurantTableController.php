<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestaurantTableController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = RestaurantTable::query()
            ->where('status', true)
            ->with([
                'diningSessions' => function ($query) {
                    $query
                        ->whereIn('status', [
                            'open',
                            'billing',
                        ])
                        ->withCount('orders')
                        ->orderByDesc('id');
                },
            ]);

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->integer('location_id'));
        }

        $tables = $query
            ->orderBy('id')
            ->get();

            $data = $tables->map(function ($table) {
            $session =
                $table->diningSessions->first();

            if (!$session) {
                return [
                    'id' => $table->id,
                    'name' => $table->name,
                    'capacity' => $table->capacity,
                    'status' => 'available',
                    'session' => null,
                ];
            }

            return [
                'id' => $table->id,
                'name' => $table->name,
                'capacity' => $table->capacity,

                'status' =>
                    $session->status === 'billing'
                        ? 'billing'
                        : 'occupied',

                'session' => [
                    'id' => $session->id,

                    'guestCount' =>
                        (int) $session->guest_count,

                    'orderCount' =>
                        (int) $session->orders_count,

                    'total' =>
                        (float) $session->total,
                ],
            ];
        })->values();

        return response()->json([
            'data' => $data,
        ]);
    }
}