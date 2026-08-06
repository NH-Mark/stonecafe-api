<?php

namespace App\Http\Controllers\Api;

use App\Events\KitchenOrderCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrderRequest;
use App\Http\Resources\OrderResource;
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
            $lastSequence = Order::lockForUpdate()
                ->max('order_sequence');


            $orderSequence = $lastSequence
                ? $lastSequence + 1
                : 1001;

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
                'kitchen_status'=>Order::KITCHEN_STATUS_PENDING,
                'payment_status' => 'unpaid',
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


            //    $order->payments()->create([

            //         'payment_method_id' => $request->payment['payment_method_id'],
            //         'amount' => $request->payment['amount'],
            //         'reference' => $request->payment['reference'] ?? null,
            //         'received_by' => Auth::id(),
            //         'paid_at' => now(),

            //     ]);

            // PrintJob::create([
            //     'order_id' => $order->id,
            //     'printer' => "EPSON TM-T20III Receipt",
            //     'status' => "pending"
            // ]);


            DB::commit();

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
            event(
                new KitchenOrderCreated($order)
            );
            return new OrderResource($order);
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

    public function storePayment(Request $request, Order $order)
    {
        $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount' => 'required|numeric|min:0',
        ]);


        DB::transaction(function () use ($request, $order) {

            $order->payments()->create([

                'payment_method_id' => $request->payment_method_id,
                'amount' => $request->amount,
                'reference' => $request->reference,
                'received_by' => Auth::id(),
                'paid_at' => now(),

            ]);


            $paidAmount = $order->payments()
                ->sum('amount');


            if ($paidAmount >= $order->total_amount) {

                $status = 'paid';
            } elseif ($paidAmount > 0) {

                $status = 'partial';
            } else {

                $status = 'unpaid';
            }


            $order->update([
                'payment_status' => $status
            ]);

            if ($status == 'paid') {

                PrintJob::create([
                    'order_id' => $order->id,
                    'printer' => 'EPSON TM-T20III Receipt',
                    'type' => 'RECEIPT',
                    'status' => 'pending'
                ]);
            }
        });


        return response()->json([
            'message' => 'Payment received'
        ]);
    }
}
