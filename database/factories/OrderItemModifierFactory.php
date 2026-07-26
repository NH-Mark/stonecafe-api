<?php

namespace Database\Factories;


use App\Models\OrderItem;
use App\Models\Order;
use App\Models\MenuItem;
use App\Models\Modifier;
use App\Models\OrderItemModifier;
use Illuminate\Database\Eloquent\Factories\Factory;


class OrderItemModifierFactory extends Factory
{

    protected $model = OrderItemModifier::class;



    public function definition(): array
    {


        $qty = fake()->numberBetween(1, 3);


        $price = fake()->numberBetween(5, 50);



        return [

            'order_item_id' =>
            OrderItem::inRandomOrder()->value('id'),

            'modifier_id' =>
            Modifier::inRandomOrder()->value('id'),

            'quantity' => $qty,
            'price' => $price,


        ];
    }
}
