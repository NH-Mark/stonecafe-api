<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Location;
use App\Models\User;
use App\Models\Customer;
use App\Models\OrderType;
use App\Models\OrderSource;

use Illuminate\Database\Eloquent\Factories\Factory;


class OrderFactory extends Factory
{

    protected $model = Order::class;


    public function definition(): array
    {

        $subtotal = fake()->numberBetween(20,200);

        return [

            'order_no' =>
                'ORD-' . fake()->unique()->numberBetween(1000,9999),


            'location_id' =>
                Location::inRandomOrder()->value('id'),


            'customer_id' =>
                Customer::inRandomOrder()->value('id'),


            'order_type_id' =>
                OrderType::inRandomOrder()->value('id'),


            'order_source_id' =>
                OrderSource::inRandomOrder()->value('id'),


            'cashier_id' =>
                User::inRandomOrder()->value('id'),


            'status'=>'completed',


            'payment_status'=>'paid',


            'subtotal'=>$subtotal,


            'discount_amount'=>0,


            'tax_amount'=>$subtotal * .05,


            'service_charge'=>0,


            'total_amount'=>$subtotal * 1.05,


            'ordered_at'=>fake()
                ->dateTimeBetween(
                    '-3 months',
                    'now'
                ),

        ];

    }

}