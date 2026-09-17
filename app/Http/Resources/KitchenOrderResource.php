<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KitchenOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'order_no' => $this->order_no,

            'table' => $this->table
                ? $this->table->name
                : null,

            'ordered_at' => $this->ordered_at,

            'kitchen_status' => $this->kitchen_status,

            'status' => $this->status,

            'notes' => $this->notes,

            'customer' => $this->customer
                ? [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'phone' => $this->customer->phone,
                    'email' => $this->customer->email,
                ]
                : null,

            'items' => $this->items->map(
                function ($item) {
                    return [
                        'id' => $item->id,

                        'quantity' => $item->quantity,

                        'notes' => $item->notes,

                        'unit_price' => $item->unit_price,

                        'total_price' => $item->total_price,

                        'menu_item' => $item->menuItem,

                        'modifiers' => $item->modifiers->map(
                            function ($modifier) {
                                return [
                                    'id' => $modifier->id,

                                    'modifier' =>
                                        $modifier->modifier,

                                    'quantity' =>
                                        $modifier->quantity,
                                ];
                            }
                        )->values(),
                    ];
                }
            )->values(),
        ];
    }
}