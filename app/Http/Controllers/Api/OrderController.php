<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PrintJob;
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
            $lastSequence = Order::lockForUpdate()
                ->max('order_sequence');


            $orderSequence = $lastSequence
                ? $lastSequence + 1
                : 100001;

            $order = Order::create([

                // 'order_no' => 'ORD-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                'order_no' =>
                    'ORD-' .
                    $orderSequence,
                'order_sequence' => $orderSequence,
                'location_id' => $request->location_id,
                'customer_id' => $request->customer_id,
                'order_type_id' => $request->order_type_id,
                'order_source_id' => $request->order_source_id,
                'table_id' => $request->table_id,
                'cashier_id' => Auth::id(),
                'status' => Order::STATUS_CONFIRMED,
                'payment_status' => 'paid',
                'subtotal' => $request->subtotal,
                'discount_amount' => $request->discount_amount,
                'tax_amount' => $request->tax_amount,
                'service_charge' => $request->service_charge,
                'total_amount' => $request->total_amount,
                'notes' => $request->notes,
                'ordered_at' => now(),
            ]);

            foreach ($request->items as $row) {

                $item = $order->items()->create([

                    'menu_item_id' => $row['menu_item_id'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'total_price' => $row['total_price'],
                    'notes' => $row['notes'] ?? null,

                ]);

                foreach ($row['modifiers'] ?? [] as $modifier) {

                    $item->modifiers()->create([

                        'modifier_id' => $modifier['modifier_id'],
                        'quantity' => $modifier['quantity'],
                        'price' => $modifier['price'],

                    ]);

                }
            }

            foreach ($request->discounts ?? [] as $discount) {

                $order->discounts()->create([

                    'discount_id' => $discount['discount_id'],
                    'amount' => $discount['amount']

                ]);
            }


           $order->payments()->create([

                'payment_method_id' => $request->payment['payment_method_id'],
                'amount' => $request->payment['amount'],
                'reference' => $request->payment['reference'] ?? null,
                'received_by' => Auth::id(),
                'paid_at' => now(),

            ]);

            PrintJob::create([
                'order_id'=>$order->id,
                'printer'=>"EPSON TM-T20III Receipt",
                'status'=>"pending"
            ]);


            DB::commit();
            return $order->load([

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

        });

        return new OrderResource($order);
    }

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
}
