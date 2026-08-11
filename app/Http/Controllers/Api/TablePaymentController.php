<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiningSession;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PrintJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TablePaymentController extends Controller
{
    /**
     * Pay multiple orders belonging to the same dining session.
     *
     * Supports multiple payment methods.
     *
     * Example:
     *
     * Order 101 = 30 QAR
     * Order 102 = 7 QAR
     *
     * Payments:
     *
     * Cash = 30
     * Card = 7
     *
     * The backend allocates the payment splits across
     * the selected orders.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sessionId' => [
                'required',
                'integer',
            ],

            'orderIds' => [
                'required',
                'array',
                'min:1',
            ],

            'orderIds.*' => [
                'required',
                'integer',
                'exists:orders,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Multiple payments
            |--------------------------------------------------------------------------
            */

            'payments' => [
                'required',
                'array',
                'min:1',
            ],

            'payments.*.payment_method_id' => [
                'required',
                'integer',
                'exists:payment_methods,id',
            ],

            'payments.*.amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'payments.*.reference' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Normalize order IDs
        |--------------------------------------------------------------------------
        */

        $orderIds = collect(
            $validated['orderIds']
        )
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Normalize payments
        |--------------------------------------------------------------------------
        */

        $paymentsInput = collect(
            $validated['payments']
        )
            ->map(function ($payment) {
                return [
                    'payment_method_id' =>
                        (int) $payment['payment_method_id'],

                    'amount' =>
                        round(
                            (float) $payment['amount'],
                            2
                        ),

                    'reference' =>
                        $payment['reference'] ?? null,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Requested payment total
        |--------------------------------------------------------------------------
        */

        $requestedPaymentTotal = round(
            $paymentsInput->sum(
                fn ($payment) =>
                    $payment['amount']
            ),
            2
        );

        if ($requestedPaymentTotal <= 0) {
            return response()->json([
                'message' =>
                    'Payment amount must be greater than zero.',
            ], 422);
        }

        return DB::transaction(function () use (
            $validated,
            $orderIds,
            $paymentsInput,
            $requestedPaymentTotal
        ) {

            /*
            |--------------------------------------------------------------------------
            | Lock dining session
            |--------------------------------------------------------------------------
            */

            $diningSession = DiningSession::query()
                ->where(
                    'id',
                    $validated['sessionId']
                )
                ->lockForUpdate()
                ->first();

            if (!$diningSession) {
                return response()->json([
                    'message' =>
                        'Dining session not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Validate session
            |--------------------------------------------------------------------------
            */

            if (
                in_array(
                    $diningSession->status,
                    [
                        'closed',
                        'cancelled',
                    ],
                    true
                )
            ) {
                return response()->json([
                    'message' =>
                        'This dining session is already closed or cancelled.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Get selected orders
            |--------------------------------------------------------------------------
            */

            $orders = Order::query()
                ->whereIn(
                    'id',
                    $orderIds
                )
                ->where(
                    'dining_session_id',
                    $validated['sessionId']
                )
                ->where(
                    'status',
                    '!=',
                    'cancelled'
                )
                ->lockForUpdate()
                ->get();

            /*
            |--------------------------------------------------------------------------
            | Make sure every selected order belongs to session
            |--------------------------------------------------------------------------
            */

            if (
                $orders->count() !==
                $orderIds->count()
            ) {
                return response()->json([
                    'message' =>
                        'One or more selected orders do not belong to this dining session.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Prevent paying completed orders
            |--------------------------------------------------------------------------
            */

            $completedOrders =
                $orders->filter(
                    fn ($order) =>
                        $order->status === 'completed'
                );

            if (
                $completedOrders->isNotEmpty()
            ) {
                $ids =
                    $completedOrders
                        ->pluck('id')
                        ->implode(', ');

                return response()->json([
                    'message' =>
                        "Order(s) {$ids} have already been completed.",
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate server total
            |--------------------------------------------------------------------------
            */

            $serverTotal = round(
                $orders->sum(
                    fn ($order) =>
                        (float) $order->total_amount
                ),
                2
            );

            /*
            |--------------------------------------------------------------------------
            | Verify payment split total
            |--------------------------------------------------------------------------
            */

            if (
                round($serverTotal, 2) !==
                round($requestedPaymentTotal, 2)
            ) {
                return response()->json([
                    'message' =>
                        'Payment amount does not match the selected orders.',

                    'server_amount' =>
                        $serverTotal,

                    'requested_amount' =>
                        $requestedPaymentTotal,

                    'remaining_amount' =>
                        round(
                            $serverTotal -
                            $requestedPaymentTotal,
                            2
                        ),
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Create payment records
            |--------------------------------------------------------------------------
            |
            | Payments are allocated against orders sequentially.
            |
            | Example:
            |
            | Order 1 = 30
            | Order 2 = 7
            |
            | Cash = 20
            | Card = 17
            |
            | Result:
            |
            | Order 1:
            |   Cash 20
            |   Card 10
            |
            | Order 2:
            |   Card 7
            |
            */

            $createdPayments = [];

            $orderIndex = 0;

            $currentOrder =
                $orders->get(
                    $orderIndex
                );

            $remainingOrderAmount =
                round(
                    (float) $currentOrder->total_amount,
                    2
                );

            foreach (
                $paymentsInput as $paymentInput
            ) {

                $remainingPaymentAmount =
                    round(
                        (float) $paymentInput['amount'],
                        2
                    );

                while (
                    $remainingPaymentAmount > 0.001 &&
                    $currentOrder
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Amount allocated to current order
                    |--------------------------------------------------------------------------
                    */

                    $allocation = round(
                        min(
                            $remainingPaymentAmount,
                            $remainingOrderAmount
                        ),
                        2
                    );

                    if ($allocation <= 0) {
                        break;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Create payment
                    |--------------------------------------------------------------------------
                    */

                    $payment =
                        Payment::create([
                            'order_id' =>
                                $currentOrder->id,

                            'payment_method_id' =>
                                $paymentInput[
                                    'payment_method_id'
                                ],

                            'amount' =>
                                $allocation,

                            'reference' =>
                                $paymentInput[
                                    'reference'
                                ],

                            'received_by' =>
                                auth()->id(),

                            'paid_at' =>
                                now(),
                        ]);

                    $createdPayments[] =
                        $payment;

                    /*
                    |--------------------------------------------------------------------------
                    | Reduce payment remaining
                    |--------------------------------------------------------------------------
                    */

                    $remainingPaymentAmount =
                        round(
                            $remainingPaymentAmount -
                            $allocation,
                            2
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | Reduce order remaining
                    |--------------------------------------------------------------------------
                    */

                    $remainingOrderAmount =
                        round(
                            $remainingOrderAmount -
                            $allocation,
                            2
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | Move to next order
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $remainingOrderAmount <=
                        0.001
                    ) {

                        $currentOrder =
                            $orders->get(
                                ++$orderIndex
                            );

                        if ($currentOrder) {

                            $remainingOrderAmount =
                                round(
                                    (float)
                                    $currentOrder->total_amount,
                                    2
                                );

                        }
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Safety check
                |--------------------------------------------------------------------------
                */

                if (
                    $remainingPaymentAmount >
                    0.001
                ) {
                    return response()->json([
                        'message' =>
                            'Unable to allocate payment amount to selected orders.',
                    ], 422);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Complete selected orders
            |--------------------------------------------------------------------------
            */

            foreach ($orders as $order) {

                $order->update([
                    'status' =>
                        'completed',

                    'payment_status'=>'paid',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Create ONE print job
            |--------------------------------------------------------------------------
            */

            $paymentBatchId =
                (string) Str::uuid();

            $printJob = PrintJob::create([
                'order_id' =>
                    null,

                'dining_session_id' =>
                    $diningSession->id,

                'payment_batch_id' =>
                    $paymentBatchId,

                'printer' =>
                    'EPSON TM-T20III Receipt',

                'type' =>
                    'TABLE_RECEIPT',

                'status' =>
                    'pending',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Attach all selected orders
            |--------------------------------------------------------------------------
            */

            $printJob->orders()->attach(
                $orders->pluck('id')
            );

            /*
            |--------------------------------------------------------------------------
            | Check entire dining session
            |--------------------------------------------------------------------------
            */

            $sessionOrders =
                Order::query()
                    ->where(
                        'dining_session_id',
                        $diningSession->id
                    )
                    ->where(
                        'status',
                        '!=',
                        'cancelled'
                    )
                    ->get();

            $hasOrders =
                $sessionOrders->isNotEmpty();

            $allOrdersCompleted =
                $hasOrders &&
                $sessionOrders->every(
                    fn ($order) =>
                        $order->status ===
                        'completed'
                );

            /*
            |--------------------------------------------------------------------------
            | Close dining session
            |--------------------------------------------------------------------------
            */

            if ($allOrdersCompleted) {

                $diningSession->update([
                    'status' =>
                        'closed',

                    'closed_at' =>
                        now(),
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'message' =>
                    $allOrdersCompleted
                        ? 'Table payment completed and dining session closed.'
                        : 'Table payment completed successfully.',

                'sessionId' =>
                    (int) $diningSession->id,

                'sessionStatus' =>
                    $diningSession->status,

                'sessionClosed' =>
                    $allOrdersCompleted,

                'orderIds' =>
                    $orders
                        ->pluck('id')
                        ->map(
                            fn ($id) =>
                                (int) $id
                        )
                        ->values(),

                'amount' =>
                    $serverTotal,

                'payments' =>
                    $createdPayments,

                'paymentCount' =>
                    count($createdPayments),

                'printJobId' =>
                    $printJob->id,

                'paymentBatchId' =>
                    $paymentBatchId,
            ], 201);
        });
    }
}