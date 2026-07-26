<?php

namespace Database\Factories;


use App\Models\OrderItem;
use App\Models\Order;
use App\Models\MenuItem;


use Illuminate\Database\Eloquent\Factories\Factory;


class OrderItemFactory extends Factory
{

    protected $model = OrderItem::class;



    public function definition(): array
    {


        $qty = fake()->numberBetween(1, 3);


        $price = fake()->numberBetween(5, 50);



        return [

            'order_id' =>
            Order::inRandomOrder()->value('id'),


            'menu_item_id' =>
            MenuItem::inRandomOrder()->value('id'),


            'quantity' => $qty,


            'unit_price' => $price,


            'total_price' => $qty * $price,


        ];
    }
}
