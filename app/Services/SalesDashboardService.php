<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\OrderItem;
use App\Models\OrderItemDiscount;
use App\Models\OrderItemModifier;
use App\Models\Refund;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesDashboardService
{
    public function dashboard(Request $request): array
    {
        [$from, $to] = $this->getDateRange($request);


        $previousDays = $from->diffInDays($to) + 1;

        $previousFrom = $from->copy()->subDays($previousDays);
        $previousTo = $to->copy()->subDays($previousDays);

        $currentDiscounts =
            $this->discountTotal(
                $request,
                $from,
                $to
            );


        $previousDiscounts =
            $this->discountTotal(
                $request,
                $previousFrom,
                $previousTo
            );

        $currentOrders = Order::query()
            ->where('status', Order::STATUS_CONFIRMED);

        $previousOrders = Order::query()
            ->where('status', Order::STATUS_CONFIRMED);

        $this->applyFilters($currentOrders, $request, $from, $to);
        $this->applyFilters($previousOrders, $request, $previousFrom, $previousTo);

        $currentRefunds = Refund::query()
            ->whereBetween('created_at', [$from, $to]);

        $previousRefunds = Refund::query()
            ->whereBetween('created_at', [$previousFrom, $previousTo]);


        $currentTotalSales = $currentOrders->sum('subtotal') - $currentDiscounts;

        $previousTotalSales = $previousOrders->sum('subtotal') - $previousDiscounts;

        $currentCogs = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->whereBetween('orders.ordered_at', [$from, $to])
            ->where('orders.status', Order::STATUS_CONFIRMED)
            ->when(
                $request->filled('location_id'),
                fn($q) =>
                $q->where('orders.location_id', $request->location_id)
            )
            ->sum(
                DB::raw('order_items.quantity * menu_items.cost_price')
            );

        $previousCogs = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->whereBetween('orders.ordered_at', [$previousFrom, $previousTo])
            ->where('orders.status', Order::STATUS_CONFIRMED)
            ->when(
                $request->filled('location_id'),
                fn($q) =>
                $q->where('orders.location_id', $request->location_id)
            )
            ->sum(
                DB::raw('order_items.quantity * menu_items.cost_price')
            );

        $currentPaidModifiers = OrderItemModifier::query()
            ->join('order_items', 'order_items.id', '=', 'order_item_modifiers.order_item_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('modifiers', 'modifiers.id', '=', 'order_item_modifiers.modifier_id')
            ->whereBetween('orders.ordered_at', [$from, $to])
            ->where('orders.status', Order::STATUS_CONFIRMED)
            ->when(
                $request->filled('location_id'),
                fn($q) =>
                $q->where('orders.location_id', $request->location_id)
            )
            ->sum(
                DB::raw('order_item_modifiers.quantity * modifiers.price')
            );

        $previousPaidModifiers = OrderItemModifier::query()
            ->join('order_items', 'order_items.id', '=', 'order_item_modifiers.order_item_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('modifiers', 'modifiers.id', '=', 'order_item_modifiers.modifier_id')
            ->whereBetween('orders.ordered_at', [$previousFrom, $previousTo])
            ->where('orders.status', Order::STATUS_CONFIRMED)
            ->when(
                $request->filled('location_id'),
                fn($q) =>
                $q->where('orders.location_id', $request->location_id)
            )
            ->sum(
                DB::raw('order_item_modifiers.quantity * modifiers.price')
            );

        $grossSales = $currentOrders->sum('total_amount')
            + $currentDiscounts;

        $previousGrossSales = $previousOrders->sum('total_amount')
            + $previousDiscounts;

        // if ($request->filled('location_id')) {
        //     $currentRefunds->where('location_id', $request->location_id);
        //     $previousRefunds->where('location_id', $request->location_id);
        // }

        return [

            'stats' => [
                $this->card(
                    'Total Sales',
                    $currentOrders->sum('total_amount'),
                    $this->percentage(
                        $previousOrders->sum('total_amount'),
                        $currentOrders->sum('total_amount')
                    ),
                    'sales',
                    true
                ),

                $this->card(
                    'Orders',
                    $currentOrders->count(),
                    $this->percentage(
                        $previousOrders->count(),
                        $currentOrders->count()
                    ),
                    'orders'
                ),

                $this->card(
                    'Gross Sales',
                    $grossSales,
                    $this->percentage(
                        $previousGrossSales,
                        $grossSales
                    ),
                    'gross',
                    true
                ),

                $this->card(
                    'Paid Modifiers',
                    $currentPaidModifiers,
                    $this->percentage(
                        $previousPaidModifiers,
                        $currentPaidModifiers
                    ),
                    'modifiers',
                    true
                ),

                $this->card(
                    'Average Order',
                    $currentOrders->avg('total_amount'),
                    $this->percentage(
                        $previousOrders->avg('total_amount'),
                        $currentOrders->avg('total_amount')
                    ),
                    'average',
                    true
                ),

                $this->card(
                    'Customers',
                    $currentOrders->distinct('customer_id')->count('customer_id'),
                    $this->percentage(
                        $previousOrders->distinct('customer_id')->count('customer_id'),
                        $currentOrders->distinct('customer_id')->count('customer_id')
                    ),
                    'customers'
                ),

                $this->card(
                    'Discounts',

                    $currentDiscounts,

                    $this->percentage(
                        $previousDiscounts,
                        $currentDiscounts
                    ),

                    'discount',
                    true
                ),

                $this->card(
                    'Refunds',
                    $currentRefunds->sum('amount'),
                    $this->percentage(
                        $previousRefunds->sum('amount'),
                        $currentRefunds->sum('amount')
                    ),
                    'refund',
                    true
                ),

            ],
            'sales_trend' => $this->salesTrend(
                $request,
                $from,
                $to
            ),
            'sales_by_order_type' => $this->salesByOrderType(
                $request,
                $from,
                $to
            ),
            'top_grossing_items' => $this->topGrossingItems(
                $request,
                $from,
                $to
            ),

            'top_selling_items' => $this->topSellingItems(
                $request,
                $from,
                $to
            ),

            'top_grossing_modifiers' => $this->topGrossingModifiers(
                $request,
                $from,
                $to
            ),

            'top_selling_modifiers' => $this->topSellingModifiers(
                $request,
                $from,
                $to
            ),
            'hourly_breakdown' => $this->hourlyBreakdown(
                $request,
                $from,
                $to
            ),
            'sales_by_payment_method' => $this->salesByPaymentMethod(
                $request,
                $from,
                $to
            ),

            'sales_by_category' => $this->salesByCategory(
                $request,
                $from,
                $to
            ),
            'sales_by_discount' => $this->salesByDiscount(
                $request,
                $from,
                $to
            ),

        ];
    }

    private function applyFilters($query, Request $request, Carbon $from, Carbon $to): void
    {
        $query->whereBetween('ordered_at', [$from, $to]);
        $query->where('status', Order::STATUS_CONFIRMED);

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->filled('order_type')) {

            $query->where(
                'order_type_id',
                $request->order_type
            );
        }

        if ($request->filled('order_source_id')) {
            $query->where('order_source_id', $request->order_source_id);
        }

        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', $request->payment_method_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
    }

    private function getDateRange(Request $request): array
    {
        $range = $request->input('range', 'today');

        return match ($range) {

            'today' => [
                now()->startOfDay(),
                now()->endOfDay(),
            ],

            'yesterday' => [
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
            ],

            'this_week' => [
                now()->startOfWeek(Carbon::SUNDAY),
                now()->endOfWeek(Carbon::MONDAY),
            ],

            'this_month' => [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ],

            'last_month' => [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ],

            'custom' => [
                Carbon::parse(request('start_date'))->startOfDay(),
                Carbon::parse(request('end_date'))->endOfDay(),
            ],

            default => [
                now()->startOfDay(),
                now()->endOfDay(),
            ],
        };
    }

    private function percentage($previous, $current): string
    {
        if (!$previous) {
            return '+0%';
        }

        $change = (($current - $previous) / $previous) * 100;

        return sprintf('%+.1f%%', $change);
    }

    private function card(
        string $title,
        $value,
        string $change,
        string $icon,
        bool $currency = false
    ): array {
        return [
            'title' => $title,
            'value' => $currency
                ? 'QAR ' . number_format($value ?? 0, 2)
                : number_format($value ?? 0),
            'change' => $change,
            'icon' => $icon,
        ];
    }

    private function salesTrend(
        Request $request,
        Carbon $from,
        Carbon $to
    ): array {


        $query = Order::query();


        $this->applyFilters(
            $query,
            $request,
            $from,
            $to
        );


        return $query
            ->selectRaw("
            DATE(ordered_at) as date,
            SUM(total_amount) as sales
        ")
            ->groupBy(
                'date'
            )
            ->orderBy(
                'date'
            )
            ->get()
            ->map(function ($item) {


                return [

                    'date' => Carbon::parse(
                        $item->date
                    )->format('d M'),


                    'sales' => (float)$item->sales

                ];
            })
            ->values()
            ->toArray();
    }

    private function salesByOrderType(
        Request $request,
        Carbon $from,
        Carbon $to
    ): array {


        $query = Order::query();


        $this->applyFilters(
            $query,
            $request,
            $from,
            $to
        );


        return $query
            ->with('orderType')
            ->selectRaw("
            order_type_id,
            SUM(total_amount) as value
        ")
            ->groupBy(
                'order_type_id'
            )
            ->get()
            ->map(function ($item) {

                return [

                    'name' => $item->orderType->name,

                    'value' => (float)$item->value

                ];
            })
            ->toArray();
    }

    private function topGrossingItems(
        Request $request,
        Carbon $from,
        Carbon $to
    ): array {
        $query = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->leftJoin('menu_categories', 'menu_categories.id', '=', 'menu_items.menu_category_id');

        $this->applyFilters($query, $request, $from, $to);

        // Optional category filter
        if ($request->filled('category_id')) {
            $query->where('menu_items.category_id', $request->category_id);
        }

        return $query
            ->selectRaw("
            menu_items.id as menu_item_id,
            menu_items.name as name,
            menu_categories.id as category_id,
            menu_categories.name as category_name,
            SUM(order_items.quantity) as qty,
            SUM(order_items.quantity * order_items.unit_price) as sales,
            SUM(order_items.quantity * menu_items.cost_price) as cogs
        ")
            ->groupBy(
                'menu_items.id',
                'menu_items.name',
                'menu_categories.id',
                'menu_categories.name'
            )
            ->orderByDesc('sales')
            ->get()
            ->map(function ($item) {
                $sales = (float) $item->sales;
                $cogs = (float) $item->cogs;
                $profit = $sales - $cogs;

                return [
                    'menu_item_id' => (int) $item->menu_item_id,
                    'name' => $item->name,
                    'category_id' => $item->category_id
                        ? (int) $item->category_id
                        : null,
                    'category_name' => $item->category_name,
                    'qty' => (int) $item->qty,
                    'sales' => $sales,
                    'cogs' => $cogs,
                    'profitability' => [
                        'percentage' => $sales > 0
                            ? round(($profit / $sales) * 100, 2)
                            : 0,
                        'amount' => round($profit, 2),
                    ],
                ];
            })
            ->toArray();
    }
    private function topGrossingModifiers(
        Request $request,
        Carbon $from,
        Carbon $to
    ): array {
        $query = OrderItemModifier::query()
            ->join(
                'order_items',
                'order_items.id',
                '=',
                'order_item_modifiers.order_item_id'
            )
            ->join(
                'orders',
                'orders.id',
                '=',
                'order_items.order_id'
            )
            ->join(
                'menu_items',
                'menu_items.id',
                '=',
                'order_items.menu_item_id'
            )
            ->leftJoin(
                'menu_categories',
                'menu_categories.id',
                '=',
                'menu_items.menu_category_id'
            )
            ->join(
                'modifiers',
                'modifiers.id',
                '=',
                'order_item_modifiers.modifier_id'
            );

        $this->applyFilters($query, $request, $from, $to);

        // Optional category filter
        if ($request->filled('category_id')) {
            $query->where('menu_items.category_id', $request->category_id);
        }

        return $query
            ->selectRaw("
            menu_items.name as menu_item,
            menu_items.menu_category_id as category_id,
            menu_categories.name as category_name,
            modifiers.name as name,
            modifiers.price as modifier_price,
            SUM(order_item_modifiers.quantity) as qty,
            SUM(
                order_item_modifiers.quantity * modifiers.price
            ) as total_amount
        ")
            ->groupBy(
                'menu_items.id',
                'menu_items.name',
                'menu_items.menu_category_id',
                'menu_categories.name',
                'modifiers.id',
                'modifiers.name',
                'modifiers.price'
            )
            ->havingRaw("
            SUM(order_item_modifiers.quantity * modifiers.price) > 0
        ")
            ->orderByDesc('total_amount')
            ->get()
            ->map(function ($item) {
                return [
                    'menu_item' => $item->menu_item,
                    'category_id' => $item->category_id
                        ? (int) $item->category_id
                        : null,
                    'category_name' => $item->category_name,
                    'name' => $item->name,
                    'qty' => (int) $item->qty,
                    'price' => (float) $item->modifier_price,
                    'sales' => (float) $item->total_amount,
                ];
            })
            ->toArray();
    }

    // private function topSellingModifiers(
    //     Request $request,
    //     Carbon $from,
    //     Carbon $to
    // ): array {

    //     $query = OrderItemModifier::query()

    //         ->join(
    //             'order_items',
    //             'order_items.id',
    //             '=',
    //             'order_item_modifiers.order_item_id'
    //         )

    //         ->join(
    //             'orders',
    //             'orders.id',
    //             '=',
    //             'order_items.order_id'
    //         )

    //         ->join(
    //             'menu_items',
    //             'menu_items.id',
    //             '=',
    //             'order_items.menu_item_id'
    //         )

    //         ->join(
    //             'modifiers',
    //             'modifiers.id',
    //             '=',
    //             'order_item_modifiers.modifier_id'
    //         );


    //     $this->applyFilters(
    //         $query,
    //         $request,
    //         $from,
    //         $to
    //     );
    //     return $query

    //         ->selectRaw("
    //         menu_items.name as menu_item,

    //         modifiers.name as name,

    //         SUM(
    //             order_item_modifiers.quantity
    //         ) as qty,

    //         SUM(
    //             order_item_modifiers.quantity *
    //             order_item_modifiers.price
    //         ) as total_amount
    //     ")


    //         ->groupBy(
    //             'menu_items.id',
    //             'menu_items.name',
    //             'modifiers.id',
    //             'modifiers.name'
    //         )


    //         ->orderByDesc(
    //             'qty'
    //         )

    //         ->limit(5)

    //         ->get()

    //         ->map(function ($item) {

    //             // $profit = $item->total_amount - $item->total_cogs;

    //             // $profitability = $item->total_amount > 0
    //             //     ? ($profit / $item->total_amount) * 100
    //             //     : 0;


    //             return [

    //                 'menu_item' => $item->menu_item,

    //                 'name' => $item->name,

    //                 'qty' => (int)$item->qty,

    //                 'sales' => (float)$item->total_amount,

    //                 // 'total_cogs' => (float)$item->total_cogs,

    //                 // 'profitability' => [
    //                 //     'percentage' => round($profitability, 2),
    //                 //     'amount' => round($profit, 2)
    //                 // ]
    //             ];
    //         })

    //         ->toArray();
    // }

    private function hourlyBreakdown(
        Request $request,
        Carbon $from,
        Carbon $to
    ): array {

        $query = Order::query();

        $this->applyFilters(
            $query,
            $request,
            $from,
            $to
        );

        return $query
            ->selectRaw("
            DATE(ordered_at) as date,
            HOUR(ordered_at) as hour,
            COUNT(*) as orders
        ")
            ->groupByRaw("
            DATE(ordered_at),
            HOUR(ordered_at)
        ")
            ->orderBy("date")
            ->orderBy("hour")
            ->get()
            ->groupBy("date")
            ->map(function ($rows, $date) {

                $hours = [];

                for ($i = 0; $i < 24; $i++) {
                    $hours[$i] = 0;
                }

                foreach ($rows as $row) {
                    $hours[(int)$row->hour] = (int)$row->orders;
                }

                return [
                    "date" => Carbon::parse($date)->format("d M"),
                    "hours" => array_values($hours),
                ];
            })
            ->values()
            ->toArray();
    }

    private function discountTotal(
        Request $request,
        Carbon $from,
        Carbon $to
    ): float {

        /*
    |--------------------------------------------------------------------------
    | Order Discounts
    |--------------------------------------------------------------------------
    */

        $orderDiscounts = OrderDiscount::query()
            ->join(
                'orders',
                'orders.id',
                '=',
                'order_discounts.order_id'
            )
            ->whereBetween(
                'orders.ordered_at',
                [$from, $to]
            );


        /*
    |--------------------------------------------------------------------------
    | Item Discounts
    |--------------------------------------------------------------------------
    */

        $itemDiscounts = OrderItemDiscount::query()
            ->join(
                'order_items',
                'order_items.id',
                '=',
                'order_item_discounts.order_item_id'
            )
            ->join(
                'orders',
                'orders.id',
                '=',
                'order_items.order_id'
            )
            ->whereBetween(
                'orders.ordered_at',
                [$from, $to]
            );


        /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

        if ($request->filled('location_id')) {

            $orderDiscounts->where(
                'orders.location_id',
                $request->location_id
            );

            $itemDiscounts->where(
                'orders.location_id',
                $request->location_id
            );
        }


        if ($request->filled('order_type')) {

            $orderDiscounts->where(
                'orders.order_type_id',
                $request->order_type
            );

            $itemDiscounts->where(
                'orders.order_type_id',
                $request->order_type
            );
        }


        if ($request->filled('order_source_id')) {

            $orderDiscounts->where(
                'orders.order_source_id',
                $request->order_source_id
            );

            $itemDiscounts->where(
                'orders.order_source_id',
                $request->order_source_id
            );
        }


        if ($request->filled('payment_method_id')) {

            $orderDiscounts->where(
                'orders.payment_method_id',
                $request->payment_method_id
            );

            $itemDiscounts->where(
                'orders.payment_method_id',
                $request->payment_method_id
            );
        }


        if ($request->filled('status')) {

            $orderDiscounts->where(
                'orders.status',
                $request->status
            );

            $itemDiscounts->where(
                'orders.status',
                $request->status
            );
        }


        /*
    |--------------------------------------------------------------------------
    | Calculate
    |--------------------------------------------------------------------------
    */

        $orderDiscountAmount =
            (float) $orderDiscounts->sum(
                'order_discounts.amount'
            );


        $itemDiscountAmount =
            (float) $itemDiscounts->sum(
                'order_item_discounts.amount'
            );


        return $orderDiscountAmount + $itemDiscountAmount;
    }


    private function topSellingItems(
        Request $request,
        Carbon $from,
        Carbon $to
    ): array {
        $query = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->leftJoin('menu_categories', 'menu_categories.id', '=', 'menu_items.menu_category_id');

        $this->applyFilters($query, $request, $from, $to);

        return $query
            ->selectRaw("
            menu_items.id as menu_item_id,
            menu_items.name as name,
            menu_categories.id as category_id,
            menu_categories.name as category_name,
            SUM(order_items.quantity) as qty,
            SUM(order_items.quantity * order_items.unit_price) as sales,
            SUM(order_items.quantity * menu_items.cost_price) as cogs
        ")
            ->groupBy(
                'menu_items.id',
                'menu_items.name',
                'menu_categories.id',
                'menu_categories.name'
            )
            ->orderByDesc('qty')
            ->get()
            ->map(function ($item) {
                $sales = (float) $item->sales;
                $cogs = (float) $item->cogs;
                $profit = $sales - $cogs;

                return [
                    'menu_item_id' => (int) $item->menu_item_id,
                    'name' => $item->name,
                    'category_id' => $item->category_id
                        ? (int) $item->category_id
                        : null,
                    'category_name' => $item->category_name,
                    'qty' => (int) $item->qty,
                    'sales' => $sales,
                    'cogs' => $cogs,
                    'profitability' => [
                        'percentage' => $sales > 0
                            ? round(($profit / $sales) * 100, 2)
                            : 0,
                        'amount' => round($profit, 2),
                    ],
                ];
            })
            ->toArray();
    }


    private function topSellingModifiers(
        Request $request,
        Carbon $from,
        Carbon $to
    ): array {
        $query = OrderItemModifier::query()
            ->join(
                'order_items',
                'order_items.id',
                '=',
                'order_item_modifiers.order_item_id'
            )
            ->join(
                'orders',
                'orders.id',
                '=',
                'order_items.order_id'
            )
            ->join(
                'menu_items',
                'menu_items.id',
                '=',
                'order_items.menu_item_id'
            )
            ->leftJoin(
                'menu_categories',
                'menu_categories.id',
                '=',
                'menu_items.menu_category_id'
            )
            ->join(
                'modifiers',
                'modifiers.id',
                '=',
                'order_item_modifiers.modifier_id'
            );

        $this->applyFilters($query, $request, $from, $to);

        // Filter by the selected menu item category
        if ($request->filled('category_id')) {
            $query->where(
                'menu_items.menu_category_id',
                $request->category_id
            );
        }

        return $query
            ->selectRaw("
            menu_items.name as menu_item,
            menu_items.menu_category_id as category_id,
            menu_categories.name as category_name,
            modifiers.id as modifier_id,
            modifiers.name as name,
            modifiers.price as modifier_price,
            SUM(order_item_modifiers.quantity) as qty,
            SUM(
                order_item_modifiers.quantity * modifiers.price
            ) as total_amount
        ")
            ->groupBy(
                'menu_items.id',
                'menu_items.name',
                'menu_items.menu_category_id',
                'menu_categories.name',
                'modifiers.id',
                'modifiers.name',
                'modifiers.price'
            )
            ->orderByDesc('qty')
            ->get()
            ->map(function ($item) {
                return [
                    'menu_item' => $item->menu_item,
                    'category_id' => $item->category_id !== null
                        ? (int) $item->category_id
                        : null,
                    'category_name' => $item->category_name,
                    'modifier_id' => (int) $item->modifier_id,
                    'name' => $item->name,
                    'qty' => (int) $item->qty,
                    'price' => (float) $item->modifier_price,
                    'sales' => (float) $item->total_amount,
                ];
            })
            ->toArray();
    }

    private function salesByPaymentMethod(
        Request $request,
        Carbon $from,
        Carbon $to
    ): array {
        $query = DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->leftJoin(
                'payment_methods',
                'payment_methods.id',
                '=',
                'payments.payment_method_id'
            )
            ->whereBetween('payments.paid_at', [$from, $to])
            ->where('orders.status', Order::STATUS_CONFIRMED);

        // Apply dashboard filters
        if ($request->filled('location_id')) {
            $query->where(
                'orders.location_id',
                $request->location_id
            );
        }

        if ($request->filled('order_type')) {
            $query->where(
                'orders.order_type_id',
                $request->order_type
            );
        }

        if ($request->filled('order_source_id')) {
            $query->where(
                'orders.order_source_id',
                $request->order_source_id
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'orders.status',
                $request->status
            );
        }

        if ($request->filled('payment_method_id')) {
            $query->where(
                'payments.payment_method_id',
                $request->payment_method_id
            );
        }

        return $query
            ->selectRaw("
                payments.payment_method_id,
                COALESCE(payment_methods.name, 'Unknown') as name,
                SUM(payments.amount) as value,
                COUNT(payments.id) as payment_count
            ")
            ->groupBy(
                'payments.payment_method_id',
                'payment_methods.name'
            )
            ->orderByDesc('value')
            ->get()
            ->map(function ($item) {
                return [
                    'payment_method_id' => $item->payment_method_id
                        ? (int) $item->payment_method_id
                        : null,
                    'name' => $item->name,
                    'value' => (float) $item->value,
                    'payment_count' => (int) $item->payment_count,
                ];
            })
            ->toArray();
    }


    private function salesByCategory(
        Request $request,
        Carbon $from,
        Carbon $to
    ): array {
        $query = OrderItem::query()
            ->join(
                'orders',
                'orders.id',
                '=',
                'order_items.order_id'
            )
            ->join(
                'menu_items',
                'menu_items.id',
                '=',
                'order_items.menu_item_id'
            )
            ->leftJoin(
                'menu_categories',
                'menu_categories.id',
                '=',
                'menu_items.menu_category_id'
            );

        // Apply dashboard date, location, order type,
        // source, payment method and status filters.
        $this->applyFilters($query, $request, $from, $to);

        $categories = $query
            ->selectRaw("
            menu_categories.id as category_id,
            COALESCE(menu_categories.name, 'Uncategorized') as category_name,
            SUM(order_items.quantity) as qty,
            SUM(
                order_items.quantity * order_items.unit_price
            ) as value
        ")
            ->groupBy(
                'menu_categories.id',
                'menu_categories.name'
            )
            ->orderByDesc('value')
            ->get();

        $totalQty = (float) $categories->sum('qty');
        $totalValue = (float) $categories->sum('value');

        return $categories->map(function ($item) use (
            $totalQty,
            $totalValue
        ) {
            $qty = (float) $item->qty;
            $value = (float) $item->value;

            return [
                'category_id' => $item->category_id !== null
                    ? (int) $item->category_id
                    : null,

                'category_name' => $item->category_name,

                'qty' => $qty,

                'qty_percentage' => $totalQty > 0
                    ? round(($qty / $totalQty) * 100, 2)
                    : 0,

                'value' => round($value, 2),

                'value_percentage' => $totalValue > 0
                    ? round(($value / $totalValue) * 100, 2)
                    : 0,
            ];
        })->values()->toArray();
    }

    private function salesByDiscount(
        Request $request,
        Carbon $from,
        Carbon $to
    ): array {
        // Order-level discounts
        $orderDiscounts = DB::table('order_discounts')
            ->join('orders', 'orders.id', '=', 'order_discounts.order_id')
            ->leftJoin(
                'discounts',
                'discounts.id',
                '=',
                'order_discounts.discount_id'
            )
            ->whereBetween('orders.ordered_at', [$from, $to]);

        $this->applyDiscountFilters(
            $orderDiscounts,
            $request
        );

        $orderRows = $orderDiscounts
            ->selectRaw("
            order_discounts.discount_id,
            COALESCE(discounts.name, 'Unknown') as discount_name,
            COUNT(*) as qty,
            SUM(order_discounts.amount) as value
        ")
            ->groupBy(
                'order_discounts.discount_id',
                'discounts.name'
            )
            ->get();

        // Item-level discounts
        $itemDiscounts = DB::table('order_item_discounts')
            ->join(
                'order_items',
                'order_items.id',
                '=',
                'order_item_discounts.order_item_id'
            )
            ->join(
                'orders',
                'orders.id',
                '=',
                'order_items.order_id'
            )
            ->leftJoin(
                'discounts',
                'discounts.id',
                '=',
                'order_item_discounts.discount_id'
            )
            ->whereBetween('orders.ordered_at', [$from, $to]);

        $this->applyDiscountFilters(
            $itemDiscounts,
            $request
        );

        $itemRows = $itemDiscounts
            ->selectRaw("
            order_item_discounts.discount_id,
            COALESCE(discounts.name, 'Unknown') as discount_name,
            COUNT(*) as qty,
            SUM(order_item_discounts.amount) as value
        ")
            ->groupBy(
                'order_item_discounts.discount_id',
                'discounts.name'
            )
            ->get();

        // Combine order-level and item-level totals by discount.
        return $orderRows
            ->concat($itemRows)
            ->groupBy(fn($item) => $item->discount_id ?? 'unknown')
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'discount_id' => $first->discount_id !== null
                        ? (int) $first->discount_id
                        : null,
                    'discount_name' => $first->discount_name,
                    'qty' => (int) $rows->sum('qty'),
                    'value' => round(
                        (float) $rows->sum('value'),
                        2
                    ),
                ];
            })
            ->sortByDesc('value')
            ->values()
            ->toArray();
    }

    private function applyDiscountFilters(
        $query,
        Request $request
    ): void {
        $query->where('orders.status', Order::STATUS_CONFIRMED);

        if ($request->filled('location_id')) {
            $query->where(
                'orders.location_id',
                $request->location_id
            );
        }

        if ($request->filled('order_type')) {
            $query->where(
                'orders.order_type_id',
                $request->order_type
            );
        }

        if ($request->filled('order_source_id')) {
            $query->where(
                'orders.order_source_id',
                $request->order_source_id
            );
        }

        if ($request->filled('payment_method_id')) {
            $query->where(
                'orders.payment_method_id',
                $request->payment_method_id
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'orders.status',
                $request->status
            );
        }
    }
}
