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

        $currentOrders = Order::query();
        $previousOrders = Order::query();

        $this->applyFilters($currentOrders, $request, $from, $to);
        $this->applyFilters($previousOrders, $request, $previousFrom, $previousTo);

        $currentRefunds = Refund::query()
            ->whereBetween('created_at', [$from, $to]);

        $previousRefunds = Refund::query()
            ->whereBetween('created_at', [$previousFrom, $previousTo]);

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
                    $currentOrders->sum('subtotal'),
                    $this->percentage(
                        $previousOrders->sum('subtotal'),
                        $currentOrders->sum('subtotal')
                    ),
                    'gross',
                    true
                ),

                $this->card(
                    'Net Sales',

                    $currentOrders->sum('total_amount') -
                        $currentDiscounts,

                    $this->percentage(

                        $previousOrders->sum('total_amount') -
                            $previousDiscounts,

                        $currentOrders->sum('total_amount') -
                            $currentDiscounts
                    ),

                    'net',
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
            'top_selling_items' => $this->topSellingItems(
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
        ];
    }

    private function applyFilters($query, Request $request, Carbon $from, Carbon $to): void
    {
        $query->whereBetween('ordered_at', [$from, $to]);

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

    private function topSellingItems(
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
            );


        $this->applyFilters(
            $query,
            $request,
            $from,
            $to
        );


        return $query
            ->selectRaw("
            menu_items.name as name,

            SUM(order_items.quantity) as qty,

            SUM(
                order_items.quantity *
                order_items.unit_price
            ) as sales,

            SUM(
                order_items.quantity *
                menu_items.cost_price
            ) as cogs
        ")

            ->groupBy(
                'menu_items.id',
                'menu_items.name'
            )

            ->orderByDesc(
                'sales'
            )

            ->limit(50)

            ->get()

            ->map(function ($item) {

                $profit = $item->sales - $item->cogs;

                $profitability = $item->sales > 0
                    ? ($profit / $item->sales) * 100
                    : 0;


                return [

                    'name' => $item->name,

                    'qty' => (int)$item->qty,

                    'sales' => (float)$item->sales,

                    'cogs' => (float)$item->cogs,

                    'profitability' => [
                        'percentage' => round($profitability, 2),
                        'amount' => round($profit, 2)
                    ]
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

            ->join(
                'modifiers',
                'modifiers.id',
                '=',
                'order_item_modifiers.modifier_id'
            );


        $this->applyFilters(
            $query,
            $request,
            $from,
            $to
        );


        return $query

            ->selectRaw("
            menu_items.name as menu_item,

            modifiers.name as name,

            SUM(
                order_item_modifiers.quantity
            ) as qty,

            SUM(
                order_item_modifiers.quantity *
                order_item_modifiers.price
            ) as total_amount
        ")


            ->groupBy(
                'menu_items.id',
                'menu_items.name',
                'modifiers.id',
                'modifiers.name'
            )


            ->orderByDesc(
                'qty'
            )

            ->limit(5)

            ->get()

            ->map(function ($item) {

                // $profit = $item->total_amount - $item->total_cogs;

                // $profitability = $item->total_amount > 0
                //     ? ($profit / $item->total_amount) * 100
                //     : 0;


                return [

                    'menu_item' => $item->menu_item,

                    'name' => $item->name,

                    'qty' => (int)$item->qty,

                    'sales' => (float)$item->total_amount,

                    // 'total_cogs' => (float)$item->total_cogs,

                    // 'profitability' => [
                    //     'percentage' => round($profitability, 2),
                    //     'amount' => round($profit, 2)
                    // ]
                ];
            })

            ->toArray();
    }

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
}
