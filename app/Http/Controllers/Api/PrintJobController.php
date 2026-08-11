<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\PrintJob;
use Illuminate\Support\Facades\Log;

class PrintJobController extends Controller
{
    public function pending()
    {
        $jobs = PrintJob::query()
            ->where(
                'status',
                'pending'
            )
            ->with([
                'order.orderType',
                'order.orderSource',
                'order.customer',
                'order.table',
                'order.cashier',
                'order.location',
                'order.items.menuItem',
                'order.items.modifiers.modifier',
                'order.payments.paymentMethod',
                'order.payments.receivedBy',
                'order.discounts.discount',

                'orders.orderType',
                'orders.orderSource',
                'orders.customer',
                'orders.table',
                'orders.cashier',
                'orders.location',
                'orders.items.menuItem',
                'orders.items.modifiers.modifier',
                'orders.payments.paymentMethod',
                'orders.payments.receivedBy',
                'orders.discounts.discount',
            ])
            ->get();

        return response()->json(
            $jobs->map(
                function ($job) {

                    /*
                    |--------------------------------------------------------------------------
                    | Normal order receipt
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $job->type ===
                        'RECEIPT'
                    ) {

                        return [
                            'id' =>
                                $job->id,

                            'printer' =>
                                $job->printer,

                            'type' =>
                                $job->type,

                            'status' =>
                                $job->status,

                            'order' =>
                                (
                                    new OrderResource(
                                        $job->order
                                    )
                                )->resolve(),

                            'orders' =>
                                [],
                        ];
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Table receipt
                    |--------------------------------------------------------------------------
                    */

                    return [
                        'id' =>
                            $job->id,

                        'printer' =>
                            $job->printer,

                        'type' =>
                            $job->type,

                        'status' =>
                            $job->status,

                        'payment_batch_id' =>
                            $job->payment_batch_id,

                        'dining_session_id' =>
                            $job->dining_session_id,

                        'order' =>
                            null,

                        'orders' =>
                            $job->orders
                                ->map(
                                    fn ($order) =>
                                        (
                                            new OrderResource(
                                                $order
                                            )
                                        )->resolve()
                                )
                                ->values(),
                    ];
                }
            )
        );
    }

    public function done($id)
    {
        Log::info(
            "Print job completed",
            [
                'job_id' => $id,
            ]
        );

        $job =
            PrintJob::findOrFail($id);

        $job->update([
            'status' =>
                'printed',

            'printed_at' =>
                now(),
        ]);

        return response()->json([
            'success' => true,
        ]);
    }
}