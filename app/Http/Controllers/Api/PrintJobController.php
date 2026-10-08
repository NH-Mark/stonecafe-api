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
            ->where('status', 'pending')
            ->with([
                'printerConfig',
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

                // KOT print-job items
                'items.orderItem.menuItem',
                'items.orderItem.modifiers.modifier',

                // Table receipts
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
            $jobs->map(function ($job) {

                /*
                |--------------------------------------------------------------------------
                | KOT
                |--------------------------------------------------------------------------
                */

                if ($job->type === 'KOT') {
                    $order = $job->order;

                    if (!$order) {
                        return null;
                    }

                    /*
                     * Only include the order items assigned
                     * to this particular printer.
                     */
                    $printItems = $job->items
                        ->map(function ($printJobItem) {
                            $item = $printJobItem->orderItem;

                            if (!$item) {
                                return null;
                            }

                            return [
                                'id' => $item->id,

                                'menu_item' =>
                                    $item->menuItem?->name,

                                'quantity' =>
                                    $printJobItem->quantity,

                                'unit_price' =>
                                    $item->unit_price,

                                'total_price' =>
                                    $item->total_price,

                                'notes' =>
                                    $item->notes,

                                'modifiers' =>
                                    $item->modifiers
                                        ->map(fn ($modifier) => [
                                            'modifier' =>
                                                $modifier->modifier?->name,

                                            'quantity' =>
                                                $modifier->quantity,

                                            'price' =>
                                                $modifier->price,
                                        ])
                                        ->values()
                                        ->all(),
                            ];
                        })
                        ->filter()
                        ->values();

                    return [
                        'id' =>
                            $job->id,

                        'printer' => [
                                'id' => $job->printerConfig?->id,
                                'name' => $job->printerConfig?->system_name,
                                'address' => $job->printerConfig?->address,
                                'connection_type' => $job->printerConfig?->connection_type,
                                'port' => $job->printerConfig?->port,
                            ],

                        'type' =>
                            $job->type,

                        'status' =>
                            $job->status,

                        'order' => [
                            'id' =>
                                $order->id,

                            'order_no' =>
                                $order->order_no,

                            'ordered_at' =>
                                $order->ordered_at,

                            'cashier' =>
                                $order->cashier?->name,

                            'type' =>
                                $order->orderType?->name,

                            'table' =>
                                $order->table?->name,

                            'customer' =>
                                $order->customer?->name,

                            'notes' =>
                                $order->notes,

                            'items' =>
                                $printItems,
                        ],

                        'orders' => [],
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | Normal receipt
                |--------------------------------------------------------------------------
                */

                if ($job->type === 'RECEIPT') {
                    return [
                        'id' =>
                            $job->id,

                        'printer' => [
                            'id' => $job->printerConfig?->id,
                            'name' => $job->printerConfig?->system_name,
                            'address' => $job->printerConfig?->address,
                            'connection_type' => $job->printerConfig?->connection_type,
                            'port' => $job->printerConfig?->port,
                        ],

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

                    'printer' => [
                            'id' => $job->printerConfig?->id,
                            'name' => $job->printerConfig?->system_name,
                            'address' => $job->printerConfig?->address,
                            'connection_type' => $job->printerConfig?->connection_type,
                            'port' => $job->printerConfig?->port,
                        ],

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
                                        new OrderResource($order)
                                    )->resolve()
                            )
                            ->values(),
                ];
            })
            ->filter()
            ->values()
        );
    }

    public function done($id)
    {
        Log::info(
            'Print job completed',
            [
                'job_id' => $id,
            ]
        );

        $job = PrintJob::findOrFail($id);

        $job->update([
            'status' => 'printed',
            'printed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
        ]);
    }
}