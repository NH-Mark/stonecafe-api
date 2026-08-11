<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiningSession;
use App\Models\RestaurantTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiningSessionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Create / Open Dining Session
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_id' => [
                'required',
                'integer',
                'exists:restaurant_tables,id',
            ],
        ]);


        $session = DB::transaction(function () use ($validated) {

            $table = RestaurantTable::query()
                ->where(
                    'id',
                    $validated['table_id']
                )
                ->where(
                    'status',
                    true
                )
                ->lockForUpdate()
                ->firstOrFail();


            /*
             * Don't create another session if
             * this table already has an active one.
             */

            $existingSession =
                DiningSession::query()
                ->where(
                    'table_id',
                    $table->id
                )
                ->whereIn(
                    'status',
                    [
                        'open',
                        'billing',
                    ]
                )
                ->first();


            if ($existingSession) {
                return $existingSession;
            }


            return DiningSession::create([
                'table_id' =>
                $table->id,
                'guest_count' => 1,

                'status' =>
                'open',

                'subtotal' =>
                0,

                'discount_amount' =>
                0,

                'total' =>
                0,

                'opened_at' =>
                now(),
            ]);
        });


        $session->load('table');


        return response()->json([
            'data' =>
            $this->transformSession(
                $session
            ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Show Dining Session
    |--------------------------------------------------------------------------
    */

    public function show(
        DiningSession $diningSession
    ) {
        $diningSession->load([
            'table',

            'orders' => function ($query) {
                $query->with([
                    'items.menuItem',
                    'items.modifiers.modifier',
                    'items.discounts.discount',
                    'discounts.discount',
                ]);
            },
        ]);

        return response()->json([
            'data' => $this->transformSession(
                $diningSession
            ),
        ]);
    }


    /*
|--------------------------------------------------------------------------
| Transform Session
|--------------------------------------------------------------------------
*/

    private function transformSession(
        DiningSession $session
    ): array {

        return [

            'id' =>
            $session->id,



            'table' => [

                'id' =>
                $session->table?->id,

                'name' =>
                $session->table?->name,

            ],

            'status' =>
            $session->status,

            'subtotal' =>
            (float) $session->subtotal,

            'discountAmount' =>
            (float) $session->discount_amount,

            'total' =>
            (float) $session->total,

            'openedAt' =>
            $session->opened_at?->toISOString(),

            'closedAt' =>
            $session->closed_at?->toISOString(),

            'orders' =>
            $session->orders
                ->map(function ($order) {

                    return [

                        'id' =>
                        $order->id,

                        'order_no' =>
                        $order->order_no,

                        'status' =>
                        $order->status,

                        'kitchenStatus' =>
                        $order->kitchen_status,

                        'total' =>
                        (float) $order->total_amount,

                        'subtotal' =>
                        (float) $order->subtotal,

                        'discountAmount' =>
                        (float) $order->discount_amount,

                        'taxAmount' =>
                        (float) $order->tax_amount,

                        'serviceCharge' =>
                        (float) $order->service_charge,

                        'notes' =>
                        $order->notes,

                        'createdAt' =>
                        $order->created_at?->toISOString(),

                        /*
                        |--------------------------------------------------------------------------
                        | Items
                        |--------------------------------------------------------------------------
                        */

                        'items' =>
                        $order->items
                            ->map(function ($item) {

                                return [

                                    'id' =>
                                    $item->id,

                                    'menuItemId' =>
                                    $item->menu_item_id,

                                    'quantity' =>
                                    (int) $item->quantity,

                                    'unitPrice' =>
                                    (float) $item->unit_price,

                                    'totalPrice' =>
                                    (float) $item->total_price,

                                    'notes' =>
                                    $item->notes,

                                    'menuItem' =>
                                    $item->menuItem,

                                    /*
                                        |--------------------------------------------------------------------------
                                        | Modifiers
                                        |--------------------------------------------------------------------------
                                        */

                                    'modifiers' =>
                                    $item->modifiers
                                        ->map(function ($modifier) {

                                            return [

                                                'id' =>
                                                $modifier->modifier_id,

                                                'quantity' =>
                                                (int) $modifier->quantity,

                                                'price' =>
                                                (float) $modifier->price,

                                                'modifier' =>
                                                $modifier->modifier,

                                            ];
                                        })
                                        ->values(),

                                    /*
                                        |--------------------------------------------------------------------------
                                        | ITEM DISCOUNTS
                                        |--------------------------------------------------------------------------
                                        */

                                    'discount' =>
                                    $item->discounts
                                        ->first()?->discount,


                                ];
                            })
                            ->values(),

                        /*
                        |--------------------------------------------------------------------------
                        | ORDER DISCOUNTS
                        |--------------------------------------------------------------------------
                        */

                        'discounts' =>
                        $order->discounts
                            ->map(function ($orderDiscount) {

                                return [

                                    'id' =>
                                    $orderDiscount->id,

                                    'amount' =>
                                    (float) $orderDiscount->amount,

                                    'discount' =>
                                    $orderDiscount->discount,

                                ];
                            })
                            ->values(),

                    ];
                })
                ->values(),

        ];
    }

    public function transferTable(
        Request $request,
        DiningSession $diningSession
    ): JsonResponse {
        $validated = $request->validate([
            'table_id' => [
                'required',
                'integer',
                'exists:restaurant_tables,id',
            ],
        ]);

        $newTableId =
            (int) $validated['table_id'];

        /*
        |--------------------------------------------------------------------------
        | Prevent transferring to the same table
        |--------------------------------------------------------------------------
        */

        if (
            (int) $diningSession->table_id ===
            $newTableId
        ) {
            return response()->json([
                'message' =>
                'The dining session is already assigned to this table.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Transaction
        |--------------------------------------------------------------------------
        */

        $updatedSession =
            DB::transaction(
                function () use (
                    $diningSession,
                    $newTableId
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | Lock destination table
                    |--------------------------------------------------------------------------
                    */

                    $newTable =
                        RestaurantTable::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $newTableId
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | Make sure destination table isn't occupied
                    |--------------------------------------------------------------------------
                    |
                    | We determine occupancy from active dining sessions rather
                    | than trusting a potentially stale `status` column.
                    |
                    */

                    $destinationOccupied =
                        DiningSession::query()
                        ->where(
                            'table_id',
                            $newTable->id
                        )
                        ->whereIn(
                            'status',
                            [
                                'open',
                                'active',
                            ]
                        )
                        ->exists();

                    if (
                        $destinationOccupied
                    ) {
                        abort(
                            422,
                            'This table is already occupied.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Optional protection for billing tables
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $newTable->status ===
                        'billing'
                    ) {
                        abort(
                            422,
                            'This table is currently being billed.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Transfer
                    |--------------------------------------------------------------------------
                    */

                    $diningSession->update([
                        'table_id' =>
                        $newTable->id,
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Reload relationships
                    |--------------------------------------------------------------------------
                    */

                    return $diningSession
                        ->fresh([
                            'table',
                            'orders',
                        ]);
                }
            );

        return response()->json([
            'message' =>
            'Dining session transferred successfully.',
            'data' =>
            $updatedSession,
        ]);
    }
}
