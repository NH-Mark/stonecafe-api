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
     * Creates:
     *
     * - One Payment record per order.
     * - One PrintJob for the complete table payment.
     *
     * If all non-cancelled orders are completed,
     * the dining session is automatically closed.
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

            'paymentMethodId' => [
                'required',
                'integer',
                'exists:payment_methods,id',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0',
            ],
        ]);

        $orderIds = collect(
            $validated['orderIds']
        )
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values();

        return DB::transaction(function () use (
            $validated,
            $orderIds
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

            $serverTotal =
                $orders->sum(
                    fn ($order) =>
                        (float) $order->total_amount
                );

            $requestedAmount =
                (float) $validated['amount'];

            /*
            |--------------------------------------------------------------------------
            | Verify amount
            |--------------------------------------------------------------------------
            */

            if (
                round($serverTotal, 2) !==
                round($requestedAmount, 2)
            ) {
                return response()->json([
                    'message' =>
                        'Payment amount does not match the selected orders.',

                    'server_amount' =>
                        round(
                            $serverTotal,
                            2
                        ),

                    'requested_amount' =>
                        round(
                            $requestedAmount,
                            2
                        ),
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Create one payment per order
            |--------------------------------------------------------------------------
            */

            $payments = [];

            foreach ($orders as $order) {

                $payment = Payment::create([
                    'order_id' =>
                        $order->id,

                    'payment_method_id' =>
                        $validated[
                            'paymentMethodId'
                        ],

                    'amount' =>
                        (float) $order->total_amount,

                    'reference' =>
                        null,

                    'received_by' =>
                        auth()->id(),

                    'paid_at' =>
                        now(),
                ]);

                $payments[] = $payment;

                /*
                |--------------------------------------------------------------------------
                | Complete order
                |--------------------------------------------------------------------------
                */

                $order->update([
                    'status' =>
                        'completed',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Create ONE print job for the entire table payment
            |--------------------------------------------------------------------------
            |
            | Do NOT create one PrintJob per order here.
            |
            | The print job represents the receipt/batch:
            |
            | Order 101
            | Order 102
            | Order 103
            |
            | All printed together.
            |
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
                    $orderIds,

                'paymentMethodId' =>
                    (int) $validated[
                        'paymentMethodId'
                    ],

                'amount' =>
                    round(
                        $serverTotal,
                        2
                    ),

                'payments' =>
                    $payments,

                'printJobId' =>
                    $printJob->id,

                'paymentBatchId' =>
                    $paymentBatchId,
            ], 201);
        });
    }
}