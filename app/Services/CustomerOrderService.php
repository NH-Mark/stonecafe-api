<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Location;
use App\Models\OrderSource;
use App\Models\OrderType;
use App\Models\PaymentMethod;
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
            */


            $customer = Customer::updateOrCreate(

                [
                    'phone'=>$data['customer']['phone']
                ],

                [
                    'name'=>$data['customer']['name'],

                    'email'=>$data['customer']['email'] ?? null,

                    'address'=>$data['customer']['address'] ?? null
                ]

            );





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
            | Payment Method
            |--------------------------------------------------------------------------
            */


            $paymentMethod =
                PaymentMethod::where(
                    'id',
                    $data['payment']['payment_method_id']
                )->firstOrFail();



            $location = Location::firstOrFail();



            /*
            |--------------------------------------------------------------------------
            | Payment Status
            |--------------------------------------------------------------------------
            */


            $paymentStatus =
                $paymentMethod->code === 'SKIPCASH'
                ? 'unpaid'
                : 'unpaid';






            /*
            |--------------------------------------------------------------------------
            | Create Order
            |--------------------------------------------------------------------------
            */


            $order =
            Order::create([


                'order_no'=>
                    'ORD-'.$sequence,


                'order_sequence'=>
                    $sequence,


                'customer_id'=>
                    $customer->id,


                'location_id'=>
                    $location->id,


                'order_source_id'=>
                    $this->getOrderSourceId('qr'),



                'order_type_id'=>
                    $this->getOrderTypeId(
                        $data['order_type']
                    ),



                'status'=>
                    $paymentMethod->code === 'SKIPCASH'
                    ? Order::STATUS_PENDING
                    : Order::STATUS_CONFIRMED,



                'payment_status'=>
                    $paymentStatus,



                'subtotal'=>
                    $data['subtotal'],



                'tax_amount'=>
                    $data['vat'] ?? 0,



                'total_amount'=>
                    $data['total_amount'],


                'notes'=>
                    $data['notes'] ?? null,


                'ordered_at'=>now()


            ]);









            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */


            foreach($data['items'] as $row){


                $item =
                $order->items()->create([


                    'menu_item_id'=>
                        $row['product_id'],


                    'quantity'=>
                        $row['qty'],


                    'unit_price'=>
                        $row['price'],


                    'total_price'=>
                        $row['price']
                        *
                        $row['qty']


                ]);





                foreach ($row['modifiers'] ?? [] as $modifier) {


                    $item->modifiers()->create([

                        'modifier_id'=>
                            $modifier['id'],

                        'quantity'=>
                            $modifier['qty'] ?? 1,

                        'price'=>
                            $modifier['price'] ?? 0,

                    ]);

                }


            }









            /*
            |--------------------------------------------------------------------------
            | Payment Record
            |--------------------------------------------------------------------------
            */


            $order->payments()->create([


                'payment_method_id'=>
                    $paymentMethod->id,


                'amount'=>
                    $data['total_amount'],


                'paid_at'=>null


            ]);









            /*
            |--------------------------------------------------------------------------
            | Print Only For Non SkipCash
            |--------------------------------------------------------------------------
            */


            if($paymentMethod->code !== 'SKIPCASH')
            {


                PrintJob::create([


                    'order_id'=>
                        $order->id,


                    'printer'=>
                        'EPSON TM-T20III Receipt',


                    'status'=>
                        'pending'


                ]);


            }







            return $order->load([

                'items.menuItem',

                'items.modifiers.modifier',

                'customer',

                'payments.paymentMethod'

            ]);



        });


    }





    private function getOrderTypeId(string $type)
    {

        return OrderType::where(
            'code',
            $type
        )
        ->where('status',true)
        ->value('id');

    }




    private function getOrderSourceId(string $type)
    {

        return OrderSource::where(
            'code',
            $type
        )
        ->where('status',true)
        ->value('id');

    }


}