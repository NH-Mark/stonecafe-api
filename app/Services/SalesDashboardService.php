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
            ->where('status', Order::STATUS_COMPLETED);

        $previousOrders = Order::query()
            ->where('status', Order::STATUS_COMPLETED);

        $this->applyFilters($currentOrders, $request, $from, $to);
        $this->applyFilters($previousOrders, $request, $previousFrom, $previousTo);

        $currentRefunds = Refund::query()
            ->whereBetween('created_at', [$from, $to]);

        $previousRefunds = Refund::query()
            ->whereBetween('created_at', [$previousFrom, $previousTo]);

        $currentTotalSales = (float) DB::query()
            ->fromSub(
                $this->paymentOrderQuery($request, $from, $to),
                'po'
            )
            ->sum('po.paid_amount');

        $previousTotalSales = (float) DB::query()
            ->fromSub(
                $this->paymentOrderQuery(
                    $request,
                    $previousFrom,
                    $previousTo
                ),
                'po'
            )
            ->sum('po.paid_amount');

        $currentCogs = $this->paymentBasedCogs(
            $request,
            $from,
            $to
        );

        $previousCogs =  $this->paymentBasedCogs(
            $request,
            $previousFrom,
            $previousTo
        );

        $currentProfit = $currentTotalSales - $currentCogs;
        $previousProfit = $previousTotalSales - $previousCogs;

        $currentProfitPercent = $currentTotalSales > 0
            ? ($currentProfit / $currentTotalSales) * 100
            : 0;

        $previousProfitPercent = $previousTotalSales > 0
            ? ($previousProfit / $previousTotalSales) * 100
            : 0;

        $currentPaidModifiers = $this->paymentBasedModifiers(
            $request,
            $from,
            $to
        );


        $previousPaidModifiers = $this->paymentBasedModifiers(
            $request,
            $previousFrom,
            $previousTo
        );

        $grossSales = $currentTotalSales + $currentDiscounts;


        $previousGrossSales = $previousTotalSales + $previousDiscounts;

        // if ($request->filled('location_id')) {
        //     $currentRefunds->where('location_id', $request->location_id);
        //     $previousRefunds->where('location_id', $request->location_id);
        // }

        return [

            'stats' => [
                $this->card(
                    'Total Sales',
                    $currentTotalSales,
                    $this->percentage(
                        $previousTotalSales,
                        $currentTotalSales
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

                [
                    'title' => 'Total COGS',
                    'value' => 'QAR ' . number_format((float) $currentCogs, 2),
                    'change' => $this->percentage(
                        (float) $previousCogs,
                        (float) $currentCogs
                    ),
                    'icon' => 'cogs',
                ],

                $this->card(
                    'Total Profit',
                    $currentProfit,
                    $this->percentage(
                        $previousProfit,
                        $currentProfit
                    ),
                    'profit',
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
            'discount_details' => $this->discountDetails(
                $request,
                $from,
                $to
            ),

        ];
    }

    private function applyFilters($query, Request $request, Carbon $from, Carbon $to): void
    {
        $query->whereBetween('ordered_at', [$from, $to]);
        $query->where('status', Order::STATUS_COMPLETED);
        $query->where('payment_status', 'paid');

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->filled('order_type')) {
            $query->where('order_type_id', $request->order_type);
        }

        if ($request->filled('order_source_id')) {
            $query->where('order_source_id', $request->order_source_id);
        }

        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', $request->payment_method_id);
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
                Carbon::parse($request->input('start_date'))->startOfDay(),
                Carbon::parse($request->input('end_date'))->endOfDay(),
            ],

            default => [
                now()->startOfDay(),
                now()->endOfDay(),
            ],
        };
    }

    private function percentage($previous, $current): string
    {
        $previousValue = (float) $previous;
        $currentValue = (float) $current;

        if ($previousValue == 0.0 || $previous === null || $previous === '') {
            return '+0%';
        }

        $change = (($currentValue - $previousValue) / $previousValue) * 100;

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
        $query = DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->whereBetween('payments.paid_at', [$from, $to])
            ->where('orders.status', Order::STATUS_COMPLETED);

        // Dashboard filters
        if ($request->filled('location_id')) {
            $query->where('orders.location_id', $request->location_id);
        }

        if ($request->filled('order_type')) {
            $query->where('orders.order_type_id', $request->order_type);
        }

        if ($request->filled('order_source_id')) {
            $query->where('orders.order_source_id', $request->order_source_id);
        }

        if ($request->filled('payment_method_id')) {
            $query->where(
                'payments.payment_method_id',
                $request->payment_method_id
            );
        }

        return $query
            ->selectRaw("
                DATE(payments.paid_at) as date,
                SUM(payments.amount) as sales
            ")
            ->groupByRaw('DATE(payments.paid_at)')
            ->orderByRaw('DATE(payments.paid_at)')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('d M'),
                    'sales' => (float) $item->sales,
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
        $query = DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->leftJoin(
                'order_types',
                'order_types.id',
                '=',
                'orders.order_type_id'
            )
            ->whereBetween('payments.paid_at', [$from, $to])
            ->where('orders.status', Order::STATUS_COMPLETED);

        // Dashboard filters
        if ($request->filled('location_id')) {
            $query->where('orders.location_id', $request->location_id);
        }

        if ($request->filled('order_type')) {
            $query->where('orders.order_type_id', $request->order_type);
        }

        if ($request->filled('order_source_id')) {
            $query->where('orders.order_source_id', $request->order_source_id);
        }

        if ($request->filled('payment_method_id')) {
            $query->where(
                'payments.payment_method_id',
                $request->payment_method_id
            );
        }

        return $query
            ->selectRaw("
            orders.order_type_id,
            COALESCE(order_types.name, 'Unknown') as name,
            SUM(payments.amount) as value
        ")
            ->groupBy(
                'orders.order_type_id',
                'order_types.name'
            )
            ->orderByDesc('value')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'value' => (float) $item->value,
                ];
            })
            ->values()
            ->toArray();
    }
    private function topGrossingItems(
        Request $request,
        Carbon $from,
        Carbon $to
    ): array {
        $payments = $this->paymentAllocationQuery(
            $request,
            $from,
            $to
        );

        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->leftJoin(
                'menu_categories',
                'menu_categories.id',
                '=',
                'menu_items.menu_category_id'
            )
            ->joinSub($payments, 'payment_totals', function ($join) {
                $join->on('payment_totals.order_id', '=', 'orders.id');
            });

        if ($request->filled('category_id')) {
            $query->where(
                'menu_items.category_id',
                $request->category_id
            );
        }

        return $query
            ->selectRaw("
            menu_items.id as menu_item_id,
            menu_items.name as name,
            menu_items.sku as sku,
            menu_categories.id as category_id,
            menu_categories.name as category_name,

            SUM(
                order_items.quantity *
                CASE
                    WHEN orders.total_amount > 0
                    THEN LEAST(
                        payment_totals.paid_amount / orders.total_amount,
                        1
                    )
                    ELSE 0
                END
            ) as qty,

            SUM(
                order_items.quantity * order_items.unit_price *
                CASE
                    WHEN orders.total_amount > 0
                    THEN LEAST(
                        payment_totals.paid_amount / orders.total_amount,
                        1
                    )
                    ELSE 0
                END
            ) as sales,

            SUM(
                order_items.quantity * menu_items.cost_price *
                CASE
                    WHEN orders.total_amount > 0
                    THEN LEAST(
                        payment_totals.paid_amount / orders.total_amount,
                        1
                    )
                    ELSE 0
                END
            ) as cogs
        ")
            ->groupBy(
                'menu_items.id',
                'menu_items.name',
                'menu_items.sku',
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
                    'sku' => $item->sku,
                    'category_id' => $item->category_id
                        ? (int) $item->category_id
                        : null,
                    'category_name' => $item->category_name,
                    'qty' => round((float) $item->qty, 2),
                    'sales' => round($sales, 2),
                    'cogs' => round($cogs, 2),
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
        $payments = $this->paymentAllocationQuery(
            $request,
            $from,
            $to
        );

        $query = DB::table('order_item_modifiers')
            ->join(
                'order_items',
                'order_items.id',
                '=',
                'order_item_modifiers.order_item_id'
            )
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
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
            )
            ->joinSub($payments, 'payment_totals', function ($join) {
                $join->on('payment_totals.order_id', '=', 'orders.id');
            });

        if ($request->filled('category_id')) {
            $query->where(
                'menu_items.category_id',
                $request->category_id
            );
        }

        return $query
            ->selectRaw("
            menu_items.name as menu_item,
            menu_items.menu_category_id as category_id,
            menu_categories.name as category_name,
            modifiers.name as name,
            modifiers.price as modifier_price,

            SUM(
                order_item_modifiers.quantity *
                CASE
                    WHEN orders.total_amount > 0
                    THEN LEAST(
                        payment_totals.paid_amount / orders.total_amount,
                        1
                    )
                    ELSE 0
                END
            ) as qty,

            SUM(
                order_item_modifiers.quantity * modifiers.price *
                CASE
                    WHEN orders.total_amount > 0
                    THEN LEAST(
                        payment_totals.paid_amount / orders.total_amount,
                        1
                    )
                    ELSE 0
                END
            ) as total_amount,

            SUM(
                order_item_modifiers.quantity * menu_items.cost_price *
                CASE
                    WHEN orders.total_amount > 0
                    THEN LEAST(
                        payment_totals.paid_amount / orders.total_amount,
                        1
                    )
                    ELSE 0
                END
            ) as cogs
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
            ->havingRaw('SUM(order_item_modifiers.quantity * modifiers.price) > 0')
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
                    'qty' => round((float) $item->qty, 2),
                    'price' => (float) $item->modifier_price,
                    'sales' => round((float) $item->total_amount, 2),
                    'cogs' => round((float) $item->cogs, 2),
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

    // private function discountTotal(
    //     Request $request,
    //     Carbon $from,
    //     Carbon $to
    // ): float {

    //     /*
    // |--------------------------------------------------------------------------
    // | Order Discounts
    // |--------------------------------------------------------------------------
    // */

    //     $orderDiscounts = OrderDiscount::query()
    //         ->join(
    //             'orders',
    //             'orders.id',
    //             '=',
    //             'order_discounts.order_id'
    //         )
    //         ->where('orders.status', Order::STATUS_COMPLETED)
    //         ->where('orders.payment_status', 'paid')
    //         ->whereBetween(
    //             'orders.ordered_at',
    //             [$from, $to]
    //         );


    //     /*
    // |--------------------------------------------------------------------------
    // | Item Discounts
    // |--------------------------------------------------------------------------
    // */

    //     $itemDiscounts = OrderItemDiscount::query()
    //         ->join(
    //             'order_items',
    //             'order_items.id',
    //             '=',
    //             'order_item_discounts.order_item_id'
    //         )
    //         ->join(
    //             'orders',
    //             'orders.id',
    //             '=',
    //             'order_items.order_id'
    //         )
    //         ->where('orders.status', Order::STATUS_COMPLETED)
    //         ->where('orders.payment_status', 'paid')
    //         ->whereBetween(
    //             'orders.ordered_at',
    //             [$from, $to]
    //         );


    //     /*
    // |--------------------------------------------------------------------------
    // | Filters
    // |--------------------------------------------------------------------------
    // */

    //     if ($request->filled('location_id')) {

    //         $orderDiscounts->where(
    //             'orders.location_id',
    //             $request->location_id
    //         );

    //         $itemDiscounts->where(
    //             'orders.location_id',
    //             $request->location_id
    //         );
    //     }


    //     if ($request->filled('order_type')) {

    //         $orderDiscounts->where(
    //             'orders.order_type_id',
    //             $request->order_type
    //         );

    //         $itemDiscounts->where(
    //             'orders.order_type_id',
    //             $request->order_type
    //         );
    //     }


    //     if ($request->filled('order_source_id')) {

    //         $orderDiscounts->where(
    //             'orders.order_source_id',
    //             $request->order_source_id
    //         );

    //         $itemDiscounts->where(
    //             'orders.order_source_id',
    //             $request->order_source_id
    //         );
    //     }


    //     if ($request->filled('payment_method_id')) {

    //         $orderDiscounts->where(
    //             'orders.payment_method_id',
    //             $request->payment_method_id
    //         );

    //         $itemDiscounts->where(
    //             'orders.payment_method_id',
    //             $request->payment_method_id
    //         );
    //     }

    //     /*
    // |--------------------------------------------------------------------------
    // | Calculate
    // |--------------------------------------------------------------------------
    // */

    //     $orderDiscountAmount =
    //         (float) $orderDiscounts->sum(
    //             'order_discounts.amount'
    //         );


    //     $itemDiscountAmount =
    //         (float) $itemDiscounts->sum(
    //             'order_item_discounts.amount'
    //         );


    //     return $orderDiscountAmount + $itemDiscountAmount;
    // }


    private function discountTotal(
        Request $request,
        Carbon $from,
        Carbon $to
    ): float {

        $orderDiscounts = DB::table('order_discounts')
            ->selectRaw('order_id, SUM(amount) as amount')
            ->groupBy('order_id');

        $itemDiscounts = DB::table('order_item_discounts')
            ->join(
                'order_items',
                'order_items.id',
                '=',
                'order_item_discounts.order_item_id'
            )
            ->selectRaw("
                order_items.order_id,
                SUM(order_item_discounts.amount) as amount
            ")
            ->groupBy('order_items.order_id');

        return (float) DB::query()
            ->fromSub(
                $this->paymentOrderQuery($request, $from, $to),
                'po'
            )
            ->leftJoin('orders', 'orders.id', '=', 'po.order_id')
            ->leftJoinSub($orderDiscounts, 'od', function ($join) {
                $join->on('od.order_id', '=', 'po.order_id');
            })
            ->leftJoinSub($itemDiscounts, 'id', function ($join) {
                $join->on('id.order_id', '=', 'po.order_id');
            })
            ->sum(DB::raw("
                (
                    COALESCE(od.amount, 0) +
                    COALESCE(id.amount, 0)
                ) * po.paid_ratio
            "));
    }



    private function topSellingItems(
        Request $request,
        Carbon $from,
        Carbon $to
    ): array {
        $payments = $this->paymentAllocationQuery(
            $request,
            $from,
            $to
        );

        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->leftJoin(
                'menu_categories',
                'menu_categories.id',
                '=',
                'menu_items.menu_category_id'
            )
            ->joinSub($payments, 'payment_totals', function ($join) {
                $join->on('payment_totals.order_id', '=', 'orders.id');
            });

        if ($request->filled('category_id')) {
            $query->where(
                'menu_items.menu_category_id',
                $request->category_id
            );
        }

        return $query
            ->selectRaw("
            menu_items.id as menu_item_id,
            menu_items.name as name,
            menu_items.sku as sku,
            menu_categories.id as category_id,
            menu_categories.name as category_name,

            SUM(
                order_items.quantity *
                CASE
                    WHEN orders.total_amount > 0
                    THEN LEAST(
                        payment_totals.paid_amount / orders.total_amount,
                        1
                    )
                    ELSE 0
                END
            ) as qty,

            SUM(
                order_items.quantity * order_items.unit_price *
                CASE
                    WHEN orders.total_amount > 0
                    THEN LEAST(
                        payment_totals.paid_amount / orders.total_amount,
                        1
                    )
                    ELSE 0
                END
            ) as sales,

            SUM(
                order_items.quantity * menu_items.cost_price *
                CASE
                    WHEN orders.total_amount > 0
                    THEN LEAST(
                        payment_totals.paid_amount / orders.total_amount,
                        1
                    )
                    ELSE 0
                END
            ) as cogs
        ")
            ->groupBy(
                'menu_items.id',
                'menu_items.name',
                'menu_items.sku',
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
                    'sku' => $item->sku,
                    'category_id' => $item->category_id
                        ? (int) $item->category_id
                        : null,
                    'category_name' => $item->category_name,
                    'qty' => round((float) $item->qty, 2),
                    'sales' => round($sales, 2),
                    'cogs' => round($cogs, 2),
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
        $payments = $this->paymentAllocationQuery(
            $request,
            $from,
            $to
        );

        $query = DB::table('order_item_modifiers')
            ->join(
                'order_items',
                'order_items.id',
                '=',
                'order_item_modifiers.order_item_id'
            )
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
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
            )
            ->joinSub($payments, 'payment_totals', function ($join) {
                $join->on('payment_totals.order_id', '=', 'orders.id');
            });

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

            SUM(
                order_item_modifiers.quantity *
                CASE
                    WHEN orders.total_amount > 0
                    THEN LEAST(
                        payment_totals.paid_amount / orders.total_amount,
                        1
                    )
                    ELSE 0
                END
            ) as qty,

            SUM(
                order_item_modifiers.quantity * modifiers.price *
                CASE
                    WHEN orders.total_amount > 0
                    THEN LEAST(
                        payment_totals.paid_amount / orders.total_amount,
                        1
                    )
                    ELSE 0
                END
            ) as total_amount,

            SUM(
                order_item_modifiers.quantity * menu_items.cost_price *
                CASE
                    WHEN orders.total_amount > 0
                    THEN LEAST(
                        payment_totals.paid_amount / orders.total_amount,
                        1
                    )
                    ELSE 0
                END
            ) as cogs
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
                    'qty' => round((float) $item->qty, 2),
                    'price' => (float) $item->modifier_price,
                    'sales' => round((float) $item->total_amount, 2),
                    'cogs' => round((float) $item->cogs, 2),
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
            ->where('orders.status', Order::STATUS_COMPLETED);

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
        $payments = $this->paymentOrderQuery($request, $from, $to);

        $categories = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->leftJoin(
                'menu_categories',
                'menu_categories.id',
                '=',
                'menu_items.menu_category_id'
            )
            ->joinSub($payments, 'payment_totals', function ($join) {
                $join->on('payment_totals.order_id', '=', 'orders.id');
            })
            ->selectRaw("
            menu_categories.id as category_id,
            COALESCE(menu_categories.name, 'Uncategorized') as category_name,

            SUM(
                order_items.quantity *
                LEAST(
                    GREATEST(payment_totals.paid_ratio, 0),
                    1
                )
            ) as qty,

            SUM(
                order_items.quantity * order_items.unit_price *
                LEAST(
                    GREATEST(payment_totals.paid_ratio, 0),
                    1
                )
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

                'qty' => round($qty, 2),

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
        $payments = $this->paymentOrderQuery($request, $from, $to);

        // Order-level discounts
        $orderDiscounts = DB::table('order_discounts')
            ->join('orders', 'orders.id', '=', 'order_discounts.order_id')
            ->leftJoin(
                'discounts',
                'discounts.id',
                '=',
                'order_discounts.discount_id'
            )
            ->joinSub($payments, 'payment_totals', function ($join) {
                $join->on('payment_totals.order_id', '=', 'orders.id');
            })
            ->where('orders.status', Order::STATUS_COMPLETED);

        if ($request->filled('discount_id')) {
            $orderDiscounts->where(
                'order_discounts.discount_id',
                $request->discount_id
            );
        }

        $orderRows = $orderDiscounts
            ->selectRaw("
            order_discounts.discount_id,
            COALESCE(discounts.name, 'Unknown') as discount_name,
            COUNT(*) as qty,
            SUM(
                order_discounts.amount *
                LEAST(
                    GREATEST(payment_totals.paid_ratio, 0),
                    1
                )
            ) as value
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
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin(
                'discounts',
                'discounts.id',
                '=',
                'order_item_discounts.discount_id'
            )
            ->joinSub($payments, 'payment_totals', function ($join) {
                $join->on('payment_totals.order_id', '=', 'orders.id');
            })
            ->where('orders.status', Order::STATUS_COMPLETED);

        if ($request->filled('discount_id')) {
            $itemDiscounts->where(
                'order_item_discounts.discount_id',
                $request->discount_id
            );
        }

        $itemRows = $itemDiscounts
            ->selectRaw("
            order_item_discounts.discount_id,
            COALESCE(discounts.name, 'Unknown') as discount_name,
            COUNT(*) as qty,
            SUM(
                order_item_discounts.amount *
                LEAST(
                    GREATEST(payment_totals.paid_ratio, 0),
                    1
                )
            ) as value
        ")
            ->groupBy(
                'order_item_discounts.discount_id',
                'discounts.name'
            )
            ->get();

        // Combine order-level and item-level discounts.
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

    public function discountDetails(
        Request $request,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): array {
        if ($from === null || $to === null) {
            [$from, $to] = $this->getDateRange($request);
        }

        // Payments grouped by order and payment date.
        $payments = DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->whereBetween('payments.paid_at', [$from, $to])
            ->where('orders.status', Order::STATUS_COMPLETED);

        if ($request->filled('location_id')) {
            $payments->where(
                'orders.location_id',
                $request->location_id
            );
        }

        if ($request->filled('order_type')) {
            $payments->where(
                'orders.order_type_id',
                $request->order_type
            );
        }

        if ($request->filled('order_source_id')) {
            $payments->where(
                'orders.order_source_id',
                $request->order_source_id
            );
        }

        if ($request->filled('payment_method_id')) {
            $payments->where(
                'payments.payment_method_id',
                $request->payment_method_id
            );
        }

        $payments = $payments
            ->selectRaw("
            orders.id as order_id,
            DATE(payments.paid_at) as payment_date,
            orders.total_amount,
            SUM(payments.amount) as paid_amount
        ")
            ->groupBy(
                'orders.id',
                'orders.total_amount',
                DB::raw('DATE(payments.paid_at)')
            );

        // Order-level discounts.
        $orderDiscounts = DB::table('order_discounts')
            ->join('orders', 'orders.id', '=', 'order_discounts.order_id')
            ->leftJoin(
                'discounts',
                'discounts.id',
                '=',
                'order_discounts.discount_id'
            )
            ->leftJoin(
                'order_types',
                'order_types.id',
                '=',
                'orders.order_type_id'
            )
            ->leftJoin(
                'users',
                'users.id',
                '=',
                'orders.cashier_id'
            )
            ->joinSub($payments, 'payment_totals', function ($join) {
                $join->on('payment_totals.order_id', '=', 'orders.id');
            })
            ->where('orders.status', Order::STATUS_COMPLETED);

        // Item-level discounts.
        $itemDiscounts = DB::table('order_item_discounts')
            ->join(
                'order_items',
                'order_items.id',
                '=',
                'order_item_discounts.order_item_id'
            )
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin(
                'discounts',
                'discounts.id',
                '=',
                'order_item_discounts.discount_id'
            )
            ->leftJoin(
                'order_types',
                'order_types.id',
                '=',
                'orders.order_type_id'
            )
            ->leftJoin(
                'users',
                'users.id',
                '=',
                'orders.cashier_id'
            )
            ->joinSub($payments, 'payment_totals', function ($join) {
                $join->on('payment_totals.order_id', '=', 'orders.id');
            })
            ->where('orders.status', Order::STATUS_COMPLETED);

        if ($request->filled('discount_id')) {
            $orderDiscounts->where(
                'order_discounts.discount_id',
                $request->discount_id
            );

            $itemDiscounts->where(
                'order_item_discounts.discount_id',
                $request->discount_id
            );
        }

        $orderRows = $orderDiscounts
            ->selectRaw("
            payment_totals.payment_date as date,
            orders.order_no,
            order_types.name as order_type,
            COALESCE(users.name, 'Unknown') as user_name,
            COALESCE(discounts.name, 'Unknown') as discount_name,
            'order' as scope,
            'order_level' as discount_type,

            order_discounts.amount *
            CASE
                WHEN payment_totals.total_amount > 0
                THEN LEAST(
                    GREATEST(
                        payment_totals.paid_amount /
                        payment_totals.total_amount,
                        0
                    ),
                    1
                )
                ELSE 0
            END as discount_amount
        ")
            ->get();

        $itemRows = $itemDiscounts
            ->selectRaw("
            payment_totals.payment_date as date,
            orders.order_no,
            order_types.name as order_type,
            COALESCE(users.name, 'Unknown') as user_name,
            COALESCE(discounts.name, 'Unknown') as discount_name,
            'item' as scope,
            'item_level' as discount_type,

            order_item_discounts.amount *
            CASE
                WHEN payment_totals.total_amount > 0
                THEN LEAST(
                    GREATEST(
                        payment_totals.paid_amount /
                        payment_totals.total_amount,
                        0
                    ),
                    1
                )
                ELSE 0
            END as discount_amount
        ")
            ->get();

        return $orderRows
            ->merge($itemRows)
            ->map(function ($row) {
                return [
                    'date' => $row->date,
                    'order_no' => $row->order_no,
                    'order_type' => $row->order_type,
                    'by' => $row->user_name ?? 'Unknown',
                    'discount_name' => $row->discount_name,
                    'scope' => $row->scope,
                    'discount_type' => $row->discount_type,
                    'discount_amount' => round(
                        (float) $row->discount_amount,
                        2
                    ),
                ];
            })
            ->values()
            ->toArray();
    }

    private function applyDiscountFilters(
        $query,
        Request $request
    ): void {
        $query->where('orders.status', Order::STATUS_COMPLETED);
        $query->where('orders.payment_status', 'paid');

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
    }


    private function paymentOrderQuery(
        Request $request,
        Carbon $from,
        Carbon $to
    ) {
        $query = DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->whereBetween('payments.paid_at', [$from, $to])
            ->where('orders.status', Order::STATUS_COMPLETED);

        if ($request->filled('location_id')) {
            $query->where('orders.location_id', $request->location_id);
        }

        if ($request->filled('order_type')) {
            $query->where('orders.order_type_id', $request->order_type);
        }

        if ($request->filled('order_source_id')) {
            $query->where('orders.order_source_id', $request->order_source_id);
        }

        if ($request->filled('payment_method_id')) {
            $query->where(
                'payments.payment_method_id',
                $request->payment_method_id
            );
        }

        return $query
            ->selectRaw("
            orders.id as order_id,
            orders.total_amount,
            SUM(payments.amount) as paid_amount,
            SUM(payments.amount) /
                NULLIF(orders.total_amount, 0) as paid_ratio
        ")
            ->groupBy('orders.id', 'orders.total_amount');
    }


    private function paymentBasedCogs(
        Request $request,
        Carbon $from,
        Carbon $to
    ): float {

        $cogs = DB::table('order_items')
            ->join(
                'menu_items',
                'menu_items.id',
                '=',
                'order_items.menu_item_id'
            )
            ->selectRaw("
            order_items.order_id,
            SUM(
                order_items.quantity * menu_items.cost_price
            ) as cogs
        ")
            ->groupBy('order_items.order_id');

        return (float) DB::query()
            ->fromSub(
                $this->paymentOrderQuery($request, $from, $to),
                'po'
            )
            ->leftJoinSub($cogs, 'c', function ($join) {
                $join->on('c.order_id', '=', 'po.order_id');
            })
            ->sum(DB::raw("
            COALESCE(c.cogs, 0) * po.paid_ratio
        "));
    }


    private function paymentBasedModifiers(
        Request $request,
        Carbon $from,
        Carbon $to
    ): float {

        $modifiers = DB::table('order_item_modifiers')
            ->join(
                'order_items',
                'order_items.id',
                '=',
                'order_item_modifiers.order_item_id'
            )
            ->join(
                'modifiers',
                'modifiers.id',
                '=',
                'order_item_modifiers.modifier_id'
            )
            ->selectRaw("
            order_items.order_id,
            SUM(
                order_item_modifiers.quantity * modifiers.price
            ) as modifier_total
        ")
            ->groupBy('order_items.order_id');

        return (float) DB::query()
            ->fromSub(
                $this->paymentOrderQuery($request, $from, $to),
                'po'
            )
            ->joinSub($modifiers, 'm', function ($join) {
                $join->on('m.order_id', '=', 'po.order_id');
            })
            ->sum(DB::raw("
            m.modifier_total * po.paid_ratio
        "));
    }

    private function paymentAllocationQuery(
        Request $request,
        Carbon $from,
        Carbon $to
    ) {
        $query = DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->whereBetween('payments.paid_at', [$from, $to])
            ->where('orders.status', Order::STATUS_COMPLETED);

        if ($request->filled('location_id')) {
            $query->where('orders.location_id', $request->location_id);
        }

        if ($request->filled('order_type')) {
            $query->where('orders.order_type_id', $request->order_type);
        }

        if ($request->filled('order_source_id')) {
            $query->where('orders.order_source_id', $request->order_source_id);
        }

        if ($request->filled('payment_method_id')) {
            $query->where(
                'payments.payment_method_id',
                $request->payment_method_id
            );
        }

        return $query
            ->selectRaw('
            payments.order_id,
            SUM(payments.amount) as paid_amount
        ')
            ->groupBy('payments.order_id');
    }
}
