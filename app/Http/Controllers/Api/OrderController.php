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

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 20);

        $query = Order::query()
            ->with([
                'orderType',
                'orderSource',
                'customer',
                'table',
                'cashier',
                'location',
                'items.menuItem',
                'items.discounts',
                'payments.paymentMethod',
                'payments.receivedBy',
            ]);

        /*
    |--------------------------------------------------------------------------
    | Global Search
    |--------------------------------------------------------------------------
    */

        if ($request->filled('search')) {
            $search = trim($request->string('search'));

            $query->where(function ($q) use ($search) {
                $q->where(
                    'order_no',
                    'like',
                    "%{$search}%"
                )

                    ->orWhere(
                        'status',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'payment_status',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhereHas(
                        'customer',
                        function ($customerQuery) use ($search) {
                            $customerQuery->where(
                                'name',
                                'like',
                                "%{$search}%"
                            );
                        }
                    );
            });
        }

        /*
    |--------------------------------------------------------------------------
    | DataTable Column Filters
    |--------------------------------------------------------------------------
    |
    | Example:
    |
    | filters[0][id]    = payment_status
    | filters[0][value] = unpaid
    |
    */

        $filters = $request->input(
            'filters',
            []
        );

        /*
     * If filters arrives as JSON string,
     * decode it.
     *
     * This protects against:
     *
     * json_decode(): Argument #1 ($json)
     * must be of type string, array given
     */
        if (is_string($filters)) {
            $filters = json_decode(
                $filters,
                true
            ) ?? [];
        }

        if (is_array($filters)) {
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

                    /*
                 * Payment Status
                 */
                    case 'payment_status':

                        $query->where(
                            'payment_status',
                            $value
                        );

                        break;

                    /*
                 * Order Number
                 */
                    case 'order_no':

                        $query->where(
                            'order_no',
                            'like',
                            "%{$value}%"
                        );

                        break;

                    /*
                 * Customer Name
                 */
                    case 'customer_name':

                        $query->whereHas(
                            'customer',
                            function ($q) use ($value) {
                                $q->where(
                                    'name',
                                    'like',
                                    "%{$value}%"
                                );
                            }
                        );

                        break;

                    case 'total':

                        $query->where(
                            'total_amount',
                            $value
                        );


                        break;

                    /*
                 * Status
                 */
                    case 'status':

                        $query->where(
                            'status',
                            $value
                        );

                        break;

                    /*
                 * Order Type
                 */
                    case 'type':

                       $query->whereHas(
                            'orderType',
                            function ($q) use ($value) {
                                $q->where(
                                    'name',
                                    'like',
                                    "%{$value}%"
                                );
                            }
                        );

                        break;

                     case 'source':

                       $query->whereHas(
                            'orderSource',
                            function ($q) use ($value) {
                                $q->where(
                                    'name',
                                    'like',
                                    "%{$value}%"
                                );
                            }
                        );

                        break;

                    /*
                 * Location
                 */
                    case 'location_id':

                        $query->where(
                            'location_id',
                            $value
                        );

                        break;
                }
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Top Orders Filters
    |--------------------------------------------------------------------------
    */

        /*
     * Date range preset
     *
     * today
     * yesterday
     * this_week
     * this_month
     * last_month
     * custom
     */

        $range = $request->input('range');

        switch ($range) {

            /*
         * Today
         */
            case 'today':

                $query->whereDate(
                    'ordered_at',
                    now()->toDateString()
                );

                break;

            /*
         * Yesterday
         */
            case 'yesterday':

                $query->whereDate(
                    'ordered_at',
                    now()
                        ->subDay()
                        ->toDateString()
                );

                break;

            /*
         * This Week
         */
            case 'this_week':

                $query->whereBetween(
                    'ordered_at',
                    [
                        now()->startOfWeek(),
                        now()->endOfWeek(),
                    ]
                );

                break;

            /*
         * This Month
         */
            case 'this_month':

                $query->whereBetween(
                    'ordered_at',
                    [
                        now()->startOfMonth(),
                        now()->endOfMonth(),
                    ]
                );

                break;

            /*
         * Last Month
         */
            case 'last_month':

                $lastMonth = now()->subMonth();

                $query->whereBetween(
                    'ordered_at',
                    [
                        $lastMonth->copy()->startOfMonth(),
                        $lastMonth->copy()->endOfMonth(),
                    ]
                );

                break;

            /*
         * Custom
         */
            case 'custom':

                if (
                    $request->filled('start_date') &&
                    $request->filled('end_date')
                ) {
                    $query->whereBetween(
                        'ordered_at',
                        [
                            $request->start_date . ' 00:00:00',
                            $request->end_date . ' 23:59:59',
                        ]
                    );
                }

                break;
        }

        /*
    |--------------------------------------------------------------------------
    | Location Filter
    |--------------------------------------------------------------------------
    */

        if ($request->filled('location_id')) {
            $query->where(
                'location_id',
                $request->integer('location_id')
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Order Type Filter
    |--------------------------------------------------------------------------
    */

        if ($request->filled('order_type')) {
            $query->where(
                'order_type_id',
                $request->integer('order_type')
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

        $orders = $query
            ->latest('ordered_at')
            ->paginate($perPage);

        return OrderResource::collection(
            $orders
        );
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
            } else {
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
        $validated = $request->validate([
            'payments' => [
                'required',
                'array',
                'min:1',
            ],

            'payments.*.payment_method_id' => [
                'required',
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
            ],
        ]);

        $result = DB::transaction(function () use (
            $validated,
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
        | Get current paid amount
        |--------------------------------------------------------------------------
        */

            $paidAmount = (float) $order
                ->payments()
                ->sum('amount');


            /*
        |--------------------------------------------------------------------------
        | Get order total
        |--------------------------------------------------------------------------
        */

            $orderTotal = (float) $order->total_amount;


            /*
        |--------------------------------------------------------------------------
        | Calculate remaining amount
        |--------------------------------------------------------------------------
        */

            $remainingAmount = max(
                $orderTotal - $paidAmount,
                0
            );


            /*
        |--------------------------------------------------------------------------
        | Calculate total requested payment
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | Payment 1 = 30 Cash
        | Payment 2 = 7 Card
        |
        | Total requested = 37
        |
        */

            $paymentAmount = collect($validated['payments'])
                ->sum(function ($payment) {
                    return (float) $payment['amount'];
                });


            /*
        |--------------------------------------------------------------------------
        | Do not allow payment above remaining balance
        |--------------------------------------------------------------------------
        */

            if (
                round($paymentAmount, 2) >
                round($remainingAmount, 2)
            ) {

                throw \Illuminate\Validation\ValidationException::withMessages([
                    'payments' => [
                        'Payment amount cannot exceed the remaining balance of '
                            . number_format($remainingAmount, 2)
                            . '.',
                    ],
                ]);
            }


            /*
        |--------------------------------------------------------------------------
        | Create payments
        |--------------------------------------------------------------------------
        */

            $payments = [];

            foreach ($validated['payments'] as $paymentData) {

                $payments[] = $order->payments()->create([
                    'payment_method_id' =>
                    $paymentData['payment_method_id'],

                    'amount' =>
                    $paymentData['amount'],

                    'reference' =>
                    $paymentData['reference'] ?? null,

                    'received_by' =>
                    Auth::id(),

                    'paid_at' =>
                    now(),
                ]);
            }


            /*
        |--------------------------------------------------------------------------
        | Calculate new paid amount
        |--------------------------------------------------------------------------
        */

            $paidAmount = (float) $order
                ->payments()
                ->sum('amount');


            /*
        |--------------------------------------------------------------------------
        | Calculate new remaining amount
        |--------------------------------------------------------------------------
        */

            $remainingAmount = max(
                $orderTotal - $paidAmount,
                0
            );


            /*
        |--------------------------------------------------------------------------
        | Determine payment status
        |--------------------------------------------------------------------------
        */

            if (
                round($remainingAmount, 2) <= 0
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
                'payment_status' => $paymentStatus,
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

                /*
            |--------------------------------------------------------------------------
            | Complete order
            |--------------------------------------------------------------------------
            */

                $order->update([
                    'status' => Order::STATUS_COMPLETED,
                ]);


                /*
            |--------------------------------------------------------------------------
            | Create receipt print job
            |--------------------------------------------------------------------------
            */

                PrintJob::create([
                    'order_id' =>
                    $order->id,

                    'dining_session_id' =>
                    null,

                    'payment_batch_id' =>
                    null,

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

                $session = $order->diningSession;

                if ($session) {

                    /*
                |--------------------------------------------------------------------------
                | Check for other unpaid orders
                |--------------------------------------------------------------------------
                */

                    $hasUnpaidOrders = $session
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
                | Last unpaid order
                |--------------------------------------------------------------------------
                */

                    if (!$hasUnpaidOrders) {

                        $session->update([
                            'status' => 'closed',
                            'closed_at' => now(),
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

                'paid_amount' =>
                round($paidAmount, 2),

                'remaining_amount' =>
                round($remainingAmount, 2),

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

            'paid_amount' =>
            $result['paid_amount'],

            'remaining_amount' =>
            $result['remaining_amount'],

            'session_closed' =>
            $result['session_closed'],
        ]);
    }
}
