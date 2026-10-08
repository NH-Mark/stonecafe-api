<?php

namespace App\Http\Controllers\Api;

use App\Events\KitchenOrderCreated;
use App\Events\KitchenOrderUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\AddOrderItemsRequest;
use App\Http\Requests\Order\OrderRequest;
use App\Http\Requests\Order\UpdateOrderDiscountRequest as OrderUpdateOrderDiscountRequest;
use App\Http\Resources\OrderResource;
use App\Models\DiningSession;
use App\Models\Order;
use App\Services\PrintJobService;
use App\Models\PrintJob;
use App\Models\RestaurantTable;
use App\Services\OrderHistoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{

    public function __construct(
        protected PrintJobService $printJobService,
        protected OrderHistoryService $orderHistoryService
    ) {
    }


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
                        now()->startOfWeek(Carbon::SUNDAY),
                        now()->endOfWeek(Carbon::MONDAY),
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
            ->latest('id')
            ->paginate($perPage);

        return OrderResource::collection(
            $orders
        );
    }

    public function getTodayOrders(Request $request)
    {
        $orders = Order::query()
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
            ])
            ->whereDate('ordered_at', now())
            ->latest('ordered_at')
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

                'dining_session_id' =>
                $diningSession?->id,

                'cashier_id' =>
                Auth::id(),

                'status' =>
                Order::STATUS_CONFIRMED,

                'kitchen_status' =>
                Order::KITCHEN_STATUS_PREPARING,

                'payment_status' => 'unpaid',

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

            $this->orderHistoryService->log(
                $order,
                'order_created',
                'Order created'
            );


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

                'diningSession',

            ]);


            // event(
            //     new KitchenOrderCreated(
            //         $order
            //     )
            // );

            $this->printJobService->createKotJobs($order);


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

    public function updateDiscount(
        OrderUpdateOrderDiscountRequest $request,
        Order $order
    ) {
        $order = DB::transaction(function () use (
            $request,
            $order
        ) {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if (in_array($order->status, [
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELLED,
            ])) {
                abort(
                    422,
                    'Order cannot be updated because it is already closed.'
                );
            }

            /*
        |--------------------------------------------------------------------------
        | Item discounts
        |--------------------------------------------------------------------------
        */

            foreach (
                $request->input('items', [])
                as $row
            ) {
                $item = $order->items()
                    ->lockForUpdate()
                    ->findOrFail($row['id']);

                $item->discounts()->delete();

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
        | Order discounts
        |--------------------------------------------------------------------------
        */

            $order->discounts()->delete();

            foreach (
                $request->input('discounts', [])
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
        | Recalculate totals
        |--------------------------------------------------------------------------
        */

            $subtotal = $order->items()
                ->sum('total_price');

            $itemDiscount = $order->items()
                ->with('discounts')
                ->get()
                ->sum(
                    fn($item) =>
                    $item->discounts->sum('amount')
                );

            $orderDiscount = $order->discounts()
                ->sum('amount');

            $discountAmount =
                $itemDiscount +
                $orderDiscount;

            $taxAmount = 0;
            $serviceCharge = 0;

            $total = max(
                $subtotal -
                    $discountAmount +
                    $taxAmount +
                    $serviceCharge,
                0
            );

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

        return new OrderResource($order);
    }

    public function update(
        OrderUpdateOrderDiscountRequest $request,
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

          


            if (
                $request->filled('version') &&
                $order->version !== null &&
                (int) $request->input('version') !==
                (int) $order->version
            ) {
                abort(
                    409,
                    'Order has been modified by another request.'
                );
            }

            $oldOrderValues = [
                'order_type_id' => $order->order_type_id,
                'location_id' => $order->location_id,
                'order_source_id' => $order->order_source_id,
                'customer_id' => $order->customer_id,
                'kitchen_status' => $order->kitchen_status,
                'payment_status' => $order->payment_status,
                'table_id' => $order->table_id,
                'number_plate' => $order->number_plate,
                'dining_session_id' => $order->dining_session_id,
                'notes' => $order->notes,
                'subtotal' => $order->subtotal,
                'discount_amount' => $order->discount_amount,
                'tax_amount' => $order->tax_amount,
                'service_charge' => $order->service_charge,
                'total_amount' => $order->total_amount,
            ];


            if ($request->has('order_type_id')) {
                $order->order_type_id =
                    $request->input('order_type_id');
            }

            if ($request->has('location_id')) {
                $order->location_id =
                    $request->input('location_id');
            }

            if ($request->has('order_source_id')) {
                $order->order_source_id =
                    $request->input('order_source_id');
            }

            if ($request->has('customer_id')) {
                $order->customer_id =
                    $request->input('customer_id');
            }

            if ($request->has('kitchen_status')) {
                $order->kitchen_status =
                    $request->input('kitchen_status');
            }

            if ($request->has('payment_status')) {
                $order->payment_status =
                    $request->input('payment_status');
            }

            if ($request->has('restaurant_table_id')) {
                $order->table_id =
                    $request->input('restaurant_table_id');
            }

            if ($request->has('number_plate')) {
                $order->number_plate =
                    $request->input('number_plate');
            }

            if ($request->has('dining_session_id')) {
                $order->dining_session_id =
                    $request->input('dining_session_id');
            }

            if ($request->has('notes')) {
                $order->notes =
                    $request->input('notes');
            }

            $newOrderValues = [ 'order_type_id' => $order->order_type_id, 'location_id' => $order->location_id, 'order_source_id' => $order->order_source_id, 'customer_id' => $order->customer_id, 'kitchen_status' => $order->kitchen_status, 'payment_status' => $order->payment_status, 'table_id' => $order->table_id, 'number_plate' => $order->number_plate, 'dining_session_id' => $order->dining_session_id, 'notes' => $order->notes, ];
            if ($oldOrderValues != $newOrderValues) { 
                $this->orderHistoryService->log( $order, 'order_updated', 'Order details changed', $oldOrderValues, $newOrderValues ); 
            }
            /*
            |--------------------------------------------------------------------------
            | Complete current items
            |--------------------------------------------------------------------------
            */

            $items = $request->input('items', []);

            $requestedItemIds = collect($items)
                ->pluck('id')
                ->filter()
                ->map(fn($id) => (int) $id)
                ->values();


            $newItem = collect($items)
                ->first(
                    fn($row) =>
                    empty($row['id'])
                );

            if ($newItem) {
                abort(
                    422,
                    'Order contains an unsaved item. Send new items before saving order changes.'
                );
            }      

            $removedItems = $order->items()
                ->with('menuItem')
                ->whereNotIn('id', $requestedItemIds)
                ->get();

            foreach ($removedItems as $removedItem) {
                $this->orderHistoryService->log(
                    $order,
                    'item_removed',
                    "Removed {$removedItem->menuItem?->name}",
                    [
                        'order_item_id' => $removedItem->id,
                        'menu_item_id' => $removedItem->menu_item_id,
                        'quantity' => $removedItem->quantity,
                        'unit_price' => $removedItem->unit_price,
                        'total_price' => $removedItem->total_price,
                    ],
                    null,
                    $request->input('reason')
                );
            }

            $order->items()
                ->whereNotIn('id', $requestedItemIds)
                ->delete();


            foreach ($items as $row) {
                $item = $order->items()
                    ->lockForUpdate()
                    ->findOrFail(
                        (int) $row['id']
                    );

                $oldItemValues = [
                    'menu_item_id' => $item->menu_item_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'notes' => $item->notes,
                ];

                $oldModifiers = $item->modifiers()->get()->map(fn ($modifier) => 
                    [ 'modifier_id' => $modifier->modifier_id,
                    'quantity' => $modifier->quantity,
                    'price' => $modifier->price, ]
                    )->values()->all();

                $oldItemDiscounts = $item->discounts()->get()->map(fn ($discount) => [
                    'discount_id' => $discount->discount_id,
                    'amount' => $discount->amount,
                ])->values()->all();    
                
                $item->update([
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

                $newItemValues = [
                    'menu_item_id' => $item->menu_item_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'notes' => $item->notes,
                ];

                if ($oldItemValues != $newItemValues) {
                    $this->orderHistoryService->log(
                        $order,
                        'item_updated',
                        "Order item #{$item->id} updated",
                        $oldItemValues,
                        $newItemValues
                    );
                }


                $item->modifiers()->delete();

                

                foreach (
                    $row['modifiers'] ?? []
                    as $modifier
                ) {
                    $item->modifiers()->create([
                        'modifier_id' =>
                        $modifier['modifier_id'],

                        'quantity' =>
                        $modifier['quantity'] ?? 1,

                        'price' =>
                        $modifier['price'],
                    ]);
                }

                $newModifiers = $item->modifiers() ->get() ->map(fn ($modifier) => [ 'modifier_id' => $modifier->modifier_id, 'quantity' => $modifier->quantity, 'price' => $modifier->price, ]) ->values() ->all();
                if ($oldModifiers != $newModifiers) { 
                    $this->orderHistoryService->log( $order, 'item_modifiers_changed', "Modifiers changed for order item #{$item->id}", [ 'modifiers' => $oldModifiers, ], [ 'modifiers' => $newModifiers, ] ); 
                }
                $item->discounts()->delete();

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

                $newItemDiscounts = $item->discounts() ->get() ->map(fn ($discount) => [ 'discount_id' => $discount->discount_id, 'amount' => $discount->amount, ]) ->values() ->all(); 
                /* |-------------------------------------------------------------------------- | Log item discount changes |-------------------------------------------------------------------------- */ 
                if ($oldItemDiscounts != $newItemDiscounts) { 
                    $this->orderHistoryService->log( $order, 'item_discount_changed', "Discount changed for order item #{$item->id}", [ 'discounts' => $oldItemDiscounts, ], [ 'discounts' => $newItemDiscounts, ] ); 
                }
                
            }

            $oldOrderDiscounts = $order->discounts()
            ->get()
            ->map(fn ($discount) => [
                'discount_id' => $discount->discount_id,
                'amount' => $discount->amount,
            ])
            ->values()
            ->all();


            $order->discounts()->delete();

            $orderDiscounts =
                $request->input(
                    'discounts',
                    []
                );

            if (count($orderDiscounts) > 1) {
                abort(
                    422,
                    'Only one order discount can be applied.'
                );
            }

            foreach (
                $orderDiscounts
                as $discount
            ) {
                $order->discounts()->create([
                    'discount_id' =>
                    $discount['discount_id'],

                    'amount' =>
                    $discount['amount'],
                ]);
            }

            $newOrderDiscounts = $order->discounts()
            ->get()
            ->map(fn ($discount) => [
                'discount_id' => $discount->discount_id,
                'amount' => $discount->amount,
            ])
            ->values()
            ->all();

            if ($oldOrderDiscounts != $newOrderDiscounts) {
                $this->orderHistoryService->log(
                    $order,
                    'order_discount_changed',
                    'Order discount changed',
                    [
                        'discounts' => $oldOrderDiscounts,
                    ],
                    [
                        'discounts' => $newOrderDiscounts,
                    ],
                    $request->input('reason')
                );
            }
            
            $oldTotals = [
                'subtotal' => $order->subtotal,
                'discount_amount' => $order->discount_amount,
                'tax_amount' => $order->tax_amount,
                'service_charge' => $order->service_charge,
                'total_amount' => $order->total_amount,
            ];

            $subtotal = $order->items()
                ->sum('total_price');

            $itemDiscount =
                $order->items()
                ->with('discounts')
                ->get()
                ->sum(
                    fn($item) =>
                    $item->discounts
                        ->sum('amount')
                );

            $orderDiscount =
                $order->discounts()
                ->sum('amount');

            $discountAmount =
                $itemDiscount +
                $orderDiscount;

            $taxAmount =
                $request->has('tax_amount')
                ? (float) $request->input(
                    'tax_amount'
                )
                : (float) $order->tax_amount;

            $serviceCharge =
                $request->has('service_charge')
                ? (float) $request->input(
                    'service_charge'
                )
                : (float) $order->service_charge;

            $total = max(
                $subtotal -
                    $discountAmount +
                    $taxAmount +
                    $serviceCharge,
                0
            );

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

                'version' => ($order->version ?? 0) + 1,
            ]);

            $newTotals = [
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'service_charge' => $serviceCharge,
                'total_amount' => $total,
            ];

            if ($oldTotals != $newTotals) {
                $this->orderHistoryService->log(
                    $order,
                    'order_totals_changed',
                    'Order totals changed',
                    $oldTotals,
                    $newTotals
                );
            }

            $oldPayments = $order->payments()
            ->get()
            ->map(fn ($payment) => [
                'id' => $payment->id,
                'payment_method_id' => $payment->payment_method_id,
                'amount' => $payment->amount,
                'reference' => $payment->reference,
                'paid_at' => $payment->paid_at,
            ])
            ->values()
            ->all();

            $payments = $request->input(
                'payments',
                []
            );

            /*
            |--------------------------------------------------------------------------
            | Validate total payment amount
            |--------------------------------------------------------------------------
            */

            $paymentTotal = collect($payments)
                ->sum(
                    fn($payment) =>
                    (float) ($payment['amount'] ?? 0)
                );

            if ($paymentTotal > $total + 0.01) {
                abort(
                    422,
                    sprintf(
                        'Payment total (%.2f) cannot exceed order total (%.2f).',
                        $paymentTotal,
                        $total
                    )
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate payment methods
            |--------------------------------------------------------------------------
            */

            foreach ($payments as $payment) {
                $paymentMethodId =
                    (int) ($payment['payment_method_id'] ?? 0);

                $amount =
                    (float) ($payment['amount'] ?? 0);

                if ($paymentMethodId <= 0) {
                    abort(
                        422,
                        'Every payment must have a payment method.'
                    );
                }

                if ($amount < 0) {
                    abort(
                        422,
                        'Payment amount cannot be negative.'
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Existing payment IDs sent by frontend
            |--------------------------------------------------------------------------
            */

            $existingPaymentIds = collect($payments)
                ->pluck('id')
                ->filter()
                ->map(fn($id) => (int) $id)
                ->values();

            /*
            |--------------------------------------------------------------------------
            | Make sure existing payment IDs belong to this order
            |--------------------------------------------------------------------------
            */

            $orderPaymentIds = $order->payments()
                ->pluck('id')
                ->map(fn($id) => (int) $id);

            $invalidPaymentIds = $existingPaymentIds
                ->diff($orderPaymentIds);

            if ($invalidPaymentIds->isNotEmpty()) {
                abort(
                    422,
                    'One or more payments do not belong to this order.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Delete payments removed from the frontend
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            | Do this BEFORE creating new payments.
            |
            */

            if ($existingPaymentIds->isEmpty()) {
                $order->payments()->delete();
            } else {
                $order->payments()
                    ->whereNotIn(
                        'id',
                        $existingPaymentIds
                    )
                    ->delete();
            }

            /*
            |--------------------------------------------------------------------------
            | Update existing payments / create new payments
            |--------------------------------------------------------------------------
            */

            foreach ($payments as $payment) {
                $paymentId =
                    !empty($payment['id'])
                    ? (int) $payment['id']
                    : null;

                $paymentMethodId =
                    (int) $payment['payment_method_id'];

                $amount =
                    (float) $payment['amount'];

                /*
                |--------------------------------------------------------------------------
                | Existing payment
                |--------------------------------------------------------------------------
                */

                if ($paymentId) {
                    $existingPayment =
                        $order->payments()
                        ->lockForUpdate()
                        ->findOrFail($paymentId);

                    $existingPayment->update([
                        'payment_method_id' =>
                        $paymentMethodId,

                        'amount' =>
                        $amount,

                        'reference' =>
                        $payment['reference'] ?? null,

                        'paid_at' =>
                        $payment['paid_at'] ?? null,
                    ]);

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | New payment
                |--------------------------------------------------------------------------
                */

                $order->payments()->create([
                    'payment_method_id' =>
                    $paymentMethodId,

                    'amount' =>
                    $amount,

                    'reference' =>
                    $payment['reference'] ?? null,

                    'paid_at' =>
                    $payment['paid_at'] ?? now(),
                ]);
            }
            
            $newPayments = $order->payments()
                ->get()
                ->map(fn ($payment) => [
                    'id' => $payment->id,
                    'payment_method_id' => $payment->payment_method_id,
                    'amount' => $payment->amount,
                    'reference' => $payment->reference,
                    'paid_at' => $payment->paid_at,
                ])
                ->values()
                ->all();

            if ($oldPayments != $newPayments) {
                $this->orderHistoryService->log(
                    $order,
                    'payments_changed',
                    'Order payments changed',
                    [
                        'payments' => $oldPayments,
                    ],
                    [
                        'payments' => $newPayments,
                    ]
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Kitchen event
            |--------------------------------------------------------------------------
            */

            event(
                new KitchenOrderUpdated($order)
            );

            /*
            |--------------------------------------------------------------------------
            | Reload complete order
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

        return new OrderResource($order);
    }


    public function addItems(
        AddOrderItemsRequest $request,
        Order $order
    ) {
        [$order, $createdItems] = DB::transaction(function () use (
            $request,
            $order
        ) {

            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

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
            $createdOrderItems = new \Illuminate\Database\Eloquent\Collection();
            $createdItems = [];

            foreach ($request->items as $row) {

                $item = $order->items()->create([
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

                /*
            |--------------------------------------------------------------------------
            | Keep client line_id -> database item id mapping
            |--------------------------------------------------------------------------
            */
               $createdOrderItems->push($item);

                $createdItems[] = [
                    'id' => $item->id,
                    'line_id' => $row['line_id'] ?? null,
                ];
            }

            foreach ($createdOrderItems as $item) {
                $this->orderHistoryService->log(
                    $order,
                    'item_added',
                    "Added {$item->menuItem?->name}",
                    null,
                    [
                        'order_item_id' => $item->id,
                        'menu_item_id' => $item->menu_item_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total_price' => $item->total_price,
                    ]
                );
            }

            /*
        |--------------------------------------------------------------------------
        | Recalculate order totals
        |--------------------------------------------------------------------------
        */

            $subtotal = $order->items()
                ->sum('total_price');

            $itemDiscount = $order->items()
                ->with('discounts')
                ->get()
                ->sum(
                    fn($item) =>
                    $item->discounts->sum('amount')
                );

            $orderDiscount = $order->discounts()
                ->sum('amount');

            $discountAmount =
                $itemDiscount +
                $orderDiscount;

            $taxAmount = 0;
            $serviceCharge = 0;

            $total = max(
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
                Order::KITCHEN_STATUS_PREPARING,
            ]);

            event(
                new KitchenOrderUpdated($order)
            );
            $this->printJobService->createKotJobs(
                $order,
                $createdOrderItems
            );
            /*
        |--------------------------------------------------------------------------
        | Load complete order
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

            /*
        |--------------------------------------------------------------------------
        | Return data from transaction, NOT JsonResponse
        |--------------------------------------------------------------------------
        */

            return [
                $order,
                $createdItems,
            ];
        });

        /*
    |--------------------------------------------------------------------------
    | HTTP response
    |--------------------------------------------------------------------------
    */

        return response()->json([
            'data' => new OrderResource($order),
            'created_items' => $createdItems,
        ]);
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
            'order_source_id' => 'nullable',
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
                'order_source_id' => $validated['order_source_id'] ?? 1
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
                    'kitchen_status' => Order::KITCHEN_STATUS_READY,
                    'completed_at' => now(),
                    'status' => Order::STATUS_COMPLETED,
                    'order_source_id' => $validated['order_source_id'] ?? 1
                ]);

                event(
                    new KitchenOrderUpdated($order)
                );


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

    public function updateStatus(
        Request $request,
        Order $order
    ) {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'confirmed',
                    'cancelled',
                ]),
            ],
        ]);

        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending orders can be updated.',
            ], 422);
        }

        $order->status = $validated['status'];
        $order->save();

        if ($order->status === 'confirmed') {
            event(
                new KitchenOrderCreated($order->fresh())
            );
            $this->printJobService->createKotJobs($order);
        }

        return response()->json([
            'message' => 'Order status updated successfully.',
            'order' => $order->fresh(),
        ]);
    }

    public function print(Order $order)
    {
        if (
            strtolower($order->status) !== 'completed' ||
            strtolower($order->payment_status) !== 'paid'
        ) {
            return response()->json([
                'message' => 'Order must be completed and fully paid before printing.',
            ], 422);
        }

        $this->printJobService->createKotJobs($order);

        PrintJob::create([
            'order_id' => $order->id,

            'dining_session_id' => null,

            'payment_batch_id' => null,

            'printer' => 'EPSON TM-T20III Receipt',

            'type' => 'RECEIPT',

            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Order sent to printer successfully.',
        ]);
    }

    public function assignTable(
        Request $request,
        Order $order
    ) {
        $validated = $request->validate([
            'table_id' => [
                'required',
                'integer',
                'exists:restaurant_tables,id',
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
        | Make sure this is a QR dine-in order
        |--------------------------------------------------------------------------
        */

            $order->load([
                'orderType',
                'orderSource',
                'diningSession',
            ]);

            $orderTypeCode =
                strtolower(
                    (string) $order->orderType?->code
                );

            $sourceName =
                strtolower(
                    (string) $order->orderSource?->name
                );


            if ($orderTypeCode !== 'dine_in') {

                abort(
                    422,
                    'Only dine-in orders can have a table assigned.'
                );
            }


            if ($sourceName !== 'qr order') {

                abort(
                    422,
                    'Only QR orders can have a table assigned.'
                );
            }


            if (
                strtolower(
                    (string) $order->status
                ) !== 'confirmed'
            ) {

                abort(
                    422,
                    'Only confirmed orders can have a table assigned.'
                );
            }


            /*
        |--------------------------------------------------------------------------
        | Already assigned?
        |--------------------------------------------------------------------------
        */

            if (
                $order->table_id ||
                $order->dining_session_id
            ) {

                abort(
                    422,
                    'A table is already assigned to this order.'
                );
            }


            /*
        |--------------------------------------------------------------------------
        | Lock selected table
        |--------------------------------------------------------------------------
        */

            $table = RestaurantTable::query()
                ->lockForUpdate()
                ->findOrFail(
                    $validated['table_id']
                );


            /*
        |--------------------------------------------------------------------------
        | Make sure table isn't already occupied
        |--------------------------------------------------------------------------
        |
        | We determine occupancy from active dining sessions rather
        | than trusting a potentially stale table status.
        |
        */

            $tableOccupied =
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
                ->exists();


            if ($tableOccupied) {

                abort(
                    422,
                    'This table is already occupied.'
                );
            }


            /*
        |--------------------------------------------------------------------------
        | Create dining session
        |--------------------------------------------------------------------------
        */

            $diningSession =
                DiningSession::create([

                    'table_id' =>
                    $table->id,

                    'guest_count' =>
                    1,

                    'status' =>
                    'open',

                    'subtotal' =>
                    $order->subtotal ?? 0,

                    'discount_amount' =>
                    $order->discount_amount ?? 0,

                    'total' =>
                    $order->total_amount ?? 0,

                    'opened_at' =>
                    now(),

                ]);


            /*
        |--------------------------------------------------------------------------
        | Attach order to table + session
        |--------------------------------------------------------------------------
        */

            $order->update([

                'table_id' =>
                $table->id,

                'dining_session_id' =>
                $diningSession->id,

            ]);


            /*
        |--------------------------------------------------------------------------
        | Reload complete order
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

                'diningSession.table',

            ]);


            return $order;
        });


        return new OrderResource(
            $result
        );
    }

    public function history(Order $order)
    {
        $history = $order->histories()
            ->with('user:id,name')
            ->latest()
            ->paginate(50);
            
        return response()->json($history);
    }
}
