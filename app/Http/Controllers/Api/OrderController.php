<?php

namespace App\Http\Controllers\Api;

use App\Events\KitchenOrderCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\AddOrderItemsRequest;
use App\Http\Requests\Order\OrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\DiningSession;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PrintJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with([
            'orderType',
            'orderSource',
            'customer',
            'table',
            'cashier',
            'location',
            'items.menuItem',
            'payments.paymentMethod',
            'payments.receivedBy',
        ])
            ->latest()
            ->get();

        return OrderResource::collection($orders);
    }



    public function store(OrderRequest $request)
    {
        $order = DB::transaction(function () use ($request) {

            /*
            |--------------------------------------------------------------------------
            | Dining session
            |--------------------------------------------------------------------------
            */

            $diningSession = null;

            if ($request->filled('dining_session_id')) {

                $diningSession = DiningSession::query()
                    ->with('table')
                    ->lockForUpdate()
                    ->findOrFail(
                        $request->dining_session_id
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | Order Type
            |--------------------------------------------------------------------------
            |
            | Dining session:
            |     dine_in
            |
            | No session:
            |     use frontend order_type_id
            |     or your takeaway default.
            |
            */

            $orderTypeId = $request->order_type;


            if ($diningSession) {

                $orderType =
                    \App\Models\OrderType::query()
                    ->where('code', 'dine_in')
                    ->firstOrFail();

                $orderTypeId =
                    $orderType->id;
            }else{
                $orderType =
                    \App\Models\OrderType::query()
                    ->where('code', $orderTypeId)
                    ->firstOrFail();

                $orderTypeId =
                    $orderType->id;
            }


            /*
            |--------------------------------------------------------------------------
            | Table
            |--------------------------------------------------------------------------
            |
            | For a dining session, always use the table
            | attached to the session.
            |
            */

            $tableId =
                $diningSession
                ? $diningSession->table_id
                : $request->table_id;


            /*
            |--------------------------------------------------------------------------
            | Generate order number
            |--------------------------------------------------------------------------
            */

            $lastSequence =
                Order::query()
                ->lockForUpdate()
                ->max('order_sequence');


            $orderSequence =
                $lastSequence
                ? $lastSequence + 1
                : 1001;


            /*
            |--------------------------------------------------------------------------
            | Create Order
            |--------------------------------------------------------------------------
            */

            $order = Order::create([

                'order_no' =>
                'ORD-' .
                    $orderSequence,

                'order_sequence' =>
                $orderSequence,

                'location_id' =>
                $request->location_id,

                'customer_id' =>
                $request->customer_id,

                'order_type_id' =>
                $orderTypeId,

                'order_source_id' =>
                $request->order_source_id,

                'table_id' =>
                $tableId,

                /*
                * IMPORTANT:
                *
                * Store the dining session if your
                * orders table has this column.
                */
                'dining_session_id' =>
                $diningSession?->id,

                'cashier_id' =>
                Auth::id(),

                /*
                |--------------------------------------------------------------------------
                | Order lifecycle
                |--------------------------------------------------------------------------
                */

                'status' =>
                Order::STATUS_CONFIRMED,

                /*
                |--------------------------------------------------------------------------
                | Kitchen lifecycle
                |--------------------------------------------------------------------------
                */

                'kitchen_status' =>
                Order::KITCHEN_STATUS_PENDING,

                /*
                |--------------------------------------------------------------------------
                | Payment lifecycle
                |--------------------------------------------------------------------------
                */

                'payment_status' =>
                'unpaid',

                /*
                |--------------------------------------------------------------------------
                | Amounts
                |--------------------------------------------------------------------------
                */

                'subtotal' =>
                $request->subtotal,

                'discount_amount' =>
                $request->discount_amount,

                'tax_amount' =>
                $request->tax_amount,

                'service_charge' =>
                $request->service_charge,

                'total_amount' =>
                $request->total_amount,

                'notes' =>
                $request->notes,

                'ordered_at' =>
                now(),

            ]);


            /*
            |--------------------------------------------------------------------------
            | Order Items
            |--------------------------------------------------------------------------
            */

            foreach (
                $request->items
                as $row
            ) {

                $item =
                    $order->items()->create([

                        'menu_item_id' =>
                        $row['menu_item_id'],

                        'quantity' =>
                        $row['quantity'],

                        'unit_price' =>
                        $row['unit_price'],

                        'total_price' =>
                        $row['total_price'],

                        'notes' =>
                        $row['notes'] ?? null,

                    ]);


                /*
                |--------------------------------------------------------------------------
                | Modifiers
                |--------------------------------------------------------------------------
                */

                foreach (
                    $row['modifiers'] ?? []
                    as $modifier
                ) {

                    $item->modifiers()->create([

                        'modifier_id' =>
                        $modifier['modifier_id'],

                        'quantity' =>
                        $modifier['quantity'],

                        'price' =>
                        $modifier['price'],

                    ]);


                   
                }
                 foreach ($row['discounts'] ?? [] as $discount) {

                        $item->discounts()->create([
                            'discount_id' => $discount['discount_id'],
                            'amount' => $discount['amount'],
                        ]);
                    }
            }


            /*
            |--------------------------------------------------------------------------
            | Discounts
            |--------------------------------------------------------------------------
            */

            foreach (
                $request->discounts ?? []
                as $discount
            ) {

                $order->discounts()->create([

                    'discount_id' =>
                    $discount['discount_id'],

                    'amount' =>
                    $discount['amount'],

                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Load complete order
            |--------------------------------------------------------------------------
            */

            $order->load([

                'items.menuItem',

                'items.modifiers.modifier',

                'payments.paymentMethod',

                'discounts.discount',

                'customer',

                'table',

                'cashier',

                'location',

                'orderType',

                'orderSource',

                /*
                * Include session if relationship exists.
                */
                'diningSession',

            ]);


            /*
            |--------------------------------------------------------------------------
            | Kitchen event
            |--------------------------------------------------------------------------
            |
            | At this point:
            |
            | Order:
            |     CONFIRMED
            |
            | Kitchen:
            |     PENDING
            |
            | Payment:
            |     UNPAID
            |
            */

            // event(
            //     new KitchenOrderCreated(
            //         $order
            //     )
            // );


            return $order;
        });


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return new OrderResource(
            $order
        );
    }

    public function addItems(
        AddOrderItemsRequest $request,
        Order $order
    ) {
        $order = DB::transaction(function () use (
            $request,
            $order
        ) {

            /*
        |--------------------------------------------------------------------------
        | Lock order
        |--------------------------------------------------------------------------
        */

            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);


            /*
        |--------------------------------------------------------------------------
        | Do not allow adding items to closed orders
        |--------------------------------------------------------------------------
        */

            if (
                in_array(
                    $order->status,
                    [
                        Order::STATUS_COMPLETED,
                        Order::STATUS_CANCELLED,
                    ]
                )
            ) {
                abort(
                    422,
                    'Items cannot be added to this order.'
                );
            }


            /*
        |--------------------------------------------------------------------------
        | Add items
        |--------------------------------------------------------------------------
        */

            foreach (
                $request->items as $row
            ) {

                $item =
                    $order->items()->create([

                        'menu_item_id' =>
                        $row['menu_item_id'],

                        'quantity' =>
                        $row['quantity'],

                        'unit_price' =>
                        $row['unit_price'],

                        'total_price' =>
                        $row['total_price'],

                        'notes' =>
                        $row['notes'] ?? null,

                    ]);


                /*
            |--------------------------------------------------------------------------
            | Modifiers
            |--------------------------------------------------------------------------
            */

                foreach (
                    $row['modifiers'] ?? []
                    as $modifier
                ) {

                    $item->modifiers()->create([

                        'modifier_id' =>
                        $modifier['modifier_id'],

                        'quantity' =>
                        $modifier['quantity'],

                        'price' =>
                        $modifier['price'],

                    ]);
                }


                /*
            |--------------------------------------------------------------------------
            | Item discounts
            |--------------------------------------------------------------------------
            */

                foreach (
                    $row['discounts'] ?? []
                    as $discount
                ) {

                    $item->discounts()->create([

                        'discount_id' =>
                        $discount['discount_id'],

                        'amount' =>
                        $discount['amount'],

                    ]);
                }
            }


            /*
        |--------------------------------------------------------------------------
        | Recalculate order totals
        |--------------------------------------------------------------------------
        */

            $subtotal =
                $order->items()
                ->sum('total_price');


            $itemDiscount =
                $order->items()
                ->with('discounts')
                ->get()
                ->sum(
                    fn($item) =>
                    $item->discounts->sum(
                        'amount'
                    )
                );


            $orderDiscount =
                $order->discounts()
                ->sum('amount');


            $discountAmount =
                $itemDiscount +
                $orderDiscount;


            $taxAmount =
                0;


            $serviceCharge =
                0;


            $total =
                max(
                    $subtotal -
                        $discountAmount +
                        $taxAmount +
                        $serviceCharge,

                    0
                );


            /*
        |--------------------------------------------------------------------------
        | Update order
        |--------------------------------------------------------------------------
        */

            $order->update([

                'subtotal' =>
                $subtotal,

                'discount_amount' =>
                $discountAmount,

                'tax_amount' =>
                $taxAmount,

                'service_charge' =>
                $serviceCharge,

                'total_amount' =>
                $total,

                'kitchen_status' =>
                Order::KITCHEN_STATUS_PENDING,

            ]);


            /*
        |--------------------------------------------------------------------------
        | Return complete order
        |--------------------------------------------------------------------------
        */

            $order->load([

                'items.menuItem',

                'items.modifiers.modifier',

                'items.discounts.discount',

                'payments.paymentMethod',

                'discounts.discount',

                'customer',

                'table',

                'cashier',

                'location',

                'orderType',

                'orderSource',

                'diningSession',

            ]);


            return $order;
        });


        return new OrderResource(
            $order
        );
    }



    // public function store(OrderRequest $request)
    // {
    //     $order = DB::transaction(function () use ($request) {
    //         $lastSequence = Order::lockForUpdate()
    //             ->max('order_sequence');


    //         $orderSequence = $lastSequence
    //             ? $lastSequence + 1
    //             : 1001;

    //         $order = Order::create([

    //             'order_no' =>
    //             'ORD-' .
    //                 $orderSequence,
    //             'order_sequence' => $orderSequence,
    //             'location_id' => $request->location_id,
    //             'customer_id' => $request->customer_id,
    //             'order_type_id' => $request->order_type_id,
    //             'order_source_id' => $request->order_source_id,
    //             'table_id' => $request->table_id,
    //             'cashier_id' => Auth::id(),
    //             'status' => Order::STATUS_CONFIRMED,
    //             'kitchen_status'=>Order::KITCHEN_STATUS_PENDING,
    //             'payment_status' => 'unpaid',
    //             'subtotal' => $request->subtotal,
    //             'discount_amount' => $request->discount_amount,
    //             'tax_amount' => $request->tax_amount,
    //             'service_charge' => $request->service_charge,
    //             'total_amount' => $request->total_amount,
    //             'notes' => $request->notes,
    //             'ordered_at' => now(),
    //         ]);

    //         foreach ($request->items as $row) {

    //             $item = $order->items()->create([

    //                 'menu_item_id' => $row['menu_item_id'],
    //                 'quantity' => $row['quantity'],
    //                 'unit_price' => $row['unit_price'],
    //                 'total_price' => $row['total_price'],
    //                 'notes' => $row['notes'] ?? null,

    //             ]);

    //             foreach ($row['modifiers'] ?? [] as $modifier) {

    //                 $item->modifiers()->create([

    //                     'modifier_id' => $modifier['modifier_id'],
    //                     'quantity' => $modifier['quantity'],
    //                     'price' => $modifier['price'],

    //                 ]);
    //             }
    //         }

    //         foreach ($request->discounts ?? [] as $discount) {

    //             $order->discounts()->create([

    //                 'discount_id' => $discount['discount_id'],
    //                 'amount' => $discount['amount']

    //             ]);
    //         }

    //         // PrintJob::create([
    //         //     'order_id' => $order->id,
    //         //     'printer' => "EPSON TM-T20III Receipt",
    //         //     'status' => "pending"
    //         // ]);


    //         DB::commit();

    //         $order->load([
    //             'items.menuItem',
    //             'items.modifiers.modifier',
    //             'payments.paymentMethod',
    //             'discounts.discount',
    //             'customer',
    //             'table',
    //             'cashier',
    //             'location',
    //             'orderType',
    //             'orderSource'
    //         ]);
    //         event(
    //             new KitchenOrderCreated($order)
    //         );
    //         return new OrderResource($order);
    //     });

    //     return new OrderResource($order);
    // }

    public function show(Order $order)
    {
        $order->load([
            'items.menuItem',
            'items.modifiers.modifier',
            'payments.paymentMethod',
            'discounts.discount',
            'customer',
            'table',
            'cashier',
            'location',
            'orderType',
            'orderSource'
        ]);

        return new OrderResource($order);
    }

    public function update(OrderRequest $request, Order $order)
    {
        return response()->json([
            'message' => 'Not implemented'
        ]);
    }

    public function destroy(Order $order)
    {
        $order->delete();

        return response()->json([
            'message' => 'Order deleted'
        ]);
    }

    public function updatePaymentStatus(
        Request $request,
        Order $order
    ) {
        $request->validate([
            'payment_status' => [
                'required',
                'in:unpaid,partial,paid,refunded'
            ],
        ]);

        $order->update([
            'payment_status' => $request->payment_status
        ]);

        return response()->json([
            'message' => 'Payment status updated'
        ]);
    }
    public function updateOrderStatus(
        Request $request,
        Order $order
    ) {
        $request->validate([
            'status' => [
                'required',
                'in:pending,confirmed,preparing,completed,cancelled'
            ],
        ]);

        $order->update([
            'status' => $request->status
        ]);

        return response()->json([
            'message' => 'Order status updated'
        ]);
    }

public function storePayment(
    Request $request,
    Order $order
) {
    $request->validate([
        'payment_method_id' => 'required|exists:payment_methods,id',
        'amount' => 'required|numeric|min:0',
        'reference' => 'nullable|string',
    ]);

    $result = DB::transaction(function () use (
        $request,
        $order
    ) {

        /*
        |--------------------------------------------------------------------------
        | Lock order
        |--------------------------------------------------------------------------
        */

        $order = Order::query()
            ->lockForUpdate()
            ->findOrFail($order->id);


        /*
        |--------------------------------------------------------------------------
        | Create payment
        |--------------------------------------------------------------------------
        */

        $payment = $order->payments()->create([
            'payment_method_id' => $request->payment_method_id,
            'amount' => $request->amount,
            'reference' => $request->reference,
            'received_by' => Auth::id(),
            'paid_at' => now(),
        ]);


        /*
        |--------------------------------------------------------------------------
        | Calculate paid amount
        |--------------------------------------------------------------------------
        */

        $paidAmount =
            $order->payments()->sum('amount');


        /*
        |--------------------------------------------------------------------------
        | Determine payment status
        |--------------------------------------------------------------------------
        */

        if (
            $paidAmount >=
            $order->total_amount
        ) {

            $paymentStatus = 'paid';

        } elseif (
            $paidAmount > 0
        ) {

            $paymentStatus = 'partial';

        } else {

            $paymentStatus = 'unpaid';

        }


        /*
        |--------------------------------------------------------------------------
        | Update order payment status
        |--------------------------------------------------------------------------
        */

        $order->update([
            'payment_status' =>
                $paymentStatus,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Session closed?
        |--------------------------------------------------------------------------
        */

        $sessionClosed = false;


        /*
        |--------------------------------------------------------------------------
        | Order fully paid
        |--------------------------------------------------------------------------
        */

        if (
            $paymentStatus === 'paid'
        ) {

            $order->update([
                'status' =>
                    Order::STATUS_COMPLETED,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Print receipt
            |--------------------------------------------------------------------------
            */

            PrintJob::create([
                'order_id' =>
                    $order->id,

                'dining_session_id' => null,
                'payment_batch_id' => null,
                'printer' =>
                    'EPSON TM-T20III Receipt',

                'type' =>
                    'RECEIPT',

                'status' =>
                    'pending',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Check dining session
            |--------------------------------------------------------------------------
            */

            $session =
                $order->diningSession;


            if ($session) {

                /*
                |--------------------------------------------------------------------------
                | Check for any other unpaid/active orders
                |--------------------------------------------------------------------------
                |
                | The current order is already paid, so we only
                | need to look at the OTHER orders.
                |
                */

                $hasUnpaidOrders =
                    $session
                        ->orders()
                        ->where(
                            'id',
                            '!=',
                            $order->id
                        )
                        ->where(
                            'payment_status',
                            '!=',
                            'paid'
                        )
                        ->exists();


                /*
                |--------------------------------------------------------------------------
                | This was the last unpaid order
                |--------------------------------------------------------------------------
                */

                if (
                    !$hasUnpaidOrders
                ) {

                    $session->update([
                        'status' =>
                            'closed',

                        'closed_at' =>
                            now(),
                    ]);


                    $sessionClosed = true;

                }

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Return transaction result
        |--------------------------------------------------------------------------
        */

        return [

            'order_paid' =>
                $paymentStatus === 'paid',

            'payment_status' =>
                $paymentStatus,

            'session_closed' =>
                $sessionClosed,

        ];

    });


    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    return response()->json([

        'message' =>
            'Payment received',

        'order_paid' =>
            $result['order_paid'],

        'payment_status' =>
            $result['payment_status'],

        'session_closed' =>
            $result['session_closed'],

    ]);
}

   
}
