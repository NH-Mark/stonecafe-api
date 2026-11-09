<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryIngredientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ingredient_type' => $this->ingredient_type,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'measurement_unit_id' => $this->measurement_unit_id,
            'measurement_unit' => $this->measurementUnit ? [
                'id' => $this->measurementUnit->id,
                'name' => $this->measurementUnit->name,
            ] : null,
            'buying_unit_name' => $this->buying_unit_name,
            'has_sub_unit' => (bool) $this->has_sub_unit,
            'sub_unit_name' => $this->sub_unit_name,
            'buying_unit_quantity' => (float) ($this->buying_unit_quantity ?? 0),
            'unit_to_sub_unit_quantity' => $this->unit_to_sub_unit_quantity !== null ? (float) $this->unit_to_sub_unit_quantity : null,
            'sub_unit_to_base_quantity' => $this->sub_unit_to_base_quantity !== null ? (float) $this->sub_unit_to_base_quantity : null,
            'net_quantity' => $this->net_quantity !== null ? (float) $this->net_quantity : null,
            'last_buying_price' => $this->last_buying_price !== null ? (float) $this->last_buying_price : null,
            'cost_per_unit' => $this->cost_per_unit !== null ? (float) $this->cost_per_unit : $this->calculateCostPerUnit(),
            'is_active' => (bool) $this->is_active,
            'suppliers' => InventoryIngredientSupplierResource::collection($this->whenLoaded('suppliers', fn () => $this->suppliers, $this->suppliers)),
            'locations' => InventoryLocationIngredientResource::collection($this->whenLoaded('locations', fn () => $this->locations, $this->locations)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
