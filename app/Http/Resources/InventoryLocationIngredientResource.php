<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryLocationIngredientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ingredient_id' => $this->ingredient_id,
            'location_id' => $this->location_id,
            'location' => $this->location ? [
                'id' => $this->location->id,
                'name' => $this->location->name,
                'code' => $this->location->code,
            ] : null,
            'opening_balance' => $this->opening_balance !== null ? (float) $this->opening_balance : null,
            'reorder_level' => $this->reorder_level !== null ? (float) $this->reorder_level : null,
            'track_inventory' => (bool) ($this->track_inventory ?? true),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
