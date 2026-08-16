<?php

namespace App\Services;

use App\Events\KitchenOrderCreated;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Location;
use App\Models\OrderSource;
use App\Models\OrderType;
use App\Models\PrintJob;
use Illuminate\Support\Facades\DB;

class CustomerOrderService
{
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {

            /*
            |--------------------------------------------------------------------------
            | Customer
            |--------------------------------------------------------------------------
            |
            | Customer information is optional.
            |
            | If phone is provided, we update/create the customer.
            | If no phone is provided, customer_id remains null.
            |
            */

            $customer = null;

            $customerPhone =
                $data['customer']['phone'] ?? null;

            $customerName =
                $data['customer']['name'] ?? null;

            if ($customerPhone) {

                $customer = Customer::updateOrCreate(
                    [
                        'phone' => $customerPhone,
                    ],
                    [
                        'name' =>
                            $customerName ?? '',

                        'email' =>
                            $data['customer']['email'] ?? null,

                        'address' =>
                            $data['customer']['address'] ?? null,
                    ]
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Order Sequence
            |--------------------------------------------------------------------------
            */

            $lastSequence =
                Order::lockForUpdate()
                    ->max('order_sequence');

            $sequence =
                $lastSequence
                    ? $lastSequence + 1
                    : 1001;


            /*
            |--------------------------------------------------------------------------
            | Location
            |--------------------------------------------------------------------------
            */

            $location =
                Location::firstOrFail();


            /*
            |--------------------------------------------------------------------------
            | Order Type
            |--------------------------------------------------------------------------
            */

            $orderTypeCode =
                $data['order_type'] ?? 'takeaway';

            $orderTypeId =
                $this->getOrderTypeId(
                    $orderTypeCode
                );

            if (!$orderTypeId) {

                throw new \RuntimeException(
                    "Invalid order type: {$orderTypeCode}"
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Table
            |--------------------------------------------------------------------------
            |
            | table_id is ONLY used for dine_in.
            |
            | Examples:
            |
            | dine_in + table_id = 5
            | => save table_id = 5
            |
            | dine_in + no table_id
            | => save table_id = null
            |
            | takeaway + table_id = 5
            | => ignore table_id and save null
            |
            */

            $tableId = null;

            if (
                $orderTypeCode === 'dine_in' &&
                !empty($data['table_id'])
            ) {
                $tableId =
                    (int) $data['table_id'];
            }


            /*
            |--------------------------------------------------------------------------
            | Create Order
            |--------------------------------------------------------------------------
            */

            $orderData = [

                'order_no' =>
                    'ORD-' . $sequence,

                'order_sequence' =>
                    $sequence,

                'customer_id' =>
                    $customer?->id,

                'location_id' =>
                    $location->id,

                'order_source_id' =>
                    $this->getOrderSourceId('qr'),

                'order_type_id' =>
                    $orderTypeId,
                'number_plate'=>$data['number_plate'],

                /*
                | Customer QR orders are immediately confirmed.
                */

                'status' =>
                    Order::STATUS_PENDING,

                'kitchen_status' =>
                    Order::KITCHEN_STATUS_PENDING,

                'payment_status' =>
                    'unpaid',

                'subtotal' =>
                    $data['subtotal'],

                'tax_amount' =>
                    $data['tax_amount']
                    ?? $data['vat']
                    ?? 0,

                'total_amount' =>
                    $data['total_amount'],

                'notes' =>
                    $data['notes'] ?? null,

                'ordered_at' =>
                    now(),
            ];


            /*
            |--------------------------------------------------------------------------
            | Add Table ID
            |--------------------------------------------------------------------------
            |
            | Only add the table when this is dine_in.
            |
            */

            if (
                $orderTypeCode === 'dine_in'
            ) {
                $orderData['table_id'] =
                    $tableId;
            } else {
                /*
                | Takeaway must never have a table.
                */

                $orderData['table_id'] = null;
            }


            /*
            |--------------------------------------------------------------------------
            | Create Order
            |--------------------------------------------------------------------------
            */

            $order =
                Order::create(
                    $orderData
                );


            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */

            foreach (
                $data['items']
                as $row
            ) {

                $quantity =
                    (int) (
                        $row['qty'] ?? 1
                    );

                $unitPrice =
                    (float) (
                        $row['price'] ?? 0
                    );

                $item =
                    $order->items()->create([

                        'menu_item_id' =>
                            $row['product_id'],

                        'quantity' =>
                            $quantity,

                        'unit_price' =>
                            $unitPrice,

                        'total_price' =>
                            $unitPrice *
                            $quantity,
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
                            $modifier['id'],

                        'quantity' =>
                            $modifier['qty']
                            ?? 1,

                        'price' =>
                            $modifier['price']
                            ?? 0,
                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Print Job
            |--------------------------------------------------------------------------
            |
            | No payment is required before sending the order.
            |
            */

            // PrintJob::create([

            //     'order_id' =>
            //         $order->id,

            //     'printer' =>
            //         'EPSON TM-T20III Receipt',

            //     'status' =>
            //         'pending',
            // ]);


            /*
            |--------------------------------------------------------------------------
            | Kitchen Event
            |--------------------------------------------------------------------------
            */

            // event(
            //     new KitchenOrderCreated(
            //         $order
            //     )
            // );


            /*
            |--------------------------------------------------------------------------
            | Return Order
            |--------------------------------------------------------------------------
            */

            return $order->load([

                'items.menuItem',

                'items.modifiers.modifier',

                'customer',

            ]);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Order Type
    |--------------------------------------------------------------------------
    */

    private function getOrderTypeId(
        string $type
    ) {
        return OrderType::where(
            'code',
            $type
        )
            ->where(
                'status',
                true
            )
            ->value('id');
    }


    /*
    |--------------------------------------------------------------------------
    | Order Source
    |--------------------------------------------------------------------------
    */

    private function getOrderSourceId(
        string $type
    ) {
        return OrderSource::where(
            'code',
            $type
        )
            ->where(
                'status',
                true
            )
            ->value('id');
    }
}