<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemIngredientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'menu_item_id' => $this->menu_item_id,
            'ingredient_id' => $this->ingredient_id,
            'ingredient' => $this->whenLoaded('ingredient', function () {
                return new InventoryIngredientResource($this->ingredient);
            }),
            'unit_name' => $this->unit_name,
            'cost_per_unit' => (float) ($this->cost_per_unit ?? 0),
            'quantity' => (float) ($this->quantity ?? 0),
            'subtotal' => (float) ($this->subtotal ?? 0),
            'sort_order' => (int) ($this->sort_order ?? 0),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
