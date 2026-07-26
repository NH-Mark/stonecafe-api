<?php

namespace Database\Factories;


use App\Models\Payment;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\User;


use Illuminate\Database\Eloquent\Factories\Factory;


class PaymentFactory extends Factory
{

    protected $model = Payment::class;



    public function definition(): array
    {


        return [


            'order_id' =>
            Order::inRandomOrder()->value('id'),


            'payment_method_id' =>
            PaymentMethod::inRandomOrder()->value('id'),


            'amount' =>
            fake()->numberBetween(20, 200),


            'received_by' =>
            User::inRandomOrder()->value('id'),


            'paid_at' =>
            fake()->dateTimeBetween(
                '-3 months',
                'now'
            ),


        ];
    }
}
