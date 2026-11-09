<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryIngredientSupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ingredient_id' => $this->ingredient_id,
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->supplier ? [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
                'supplier_code' => $this->supplier->supplier_code,
            ] : null,
            'price' => $this->price !== null ? (float) $this->price : null,
            'buying_unit' => $this->buying_unit,
            'has_sub_unit' => (bool) ($this->has_sub_unit ?? false),
            'sub_unit_name' => $this->sub_unit_name,
            'unit_to_sub_unit_quantity' => $this->unit_to_sub_unit_quantity !== null ? (float) $this->unit_to_sub_unit_quantity : null,
            'sub_unit_to_base_quantity' => $this->sub_unit_to_base_quantity !== null ? (float) $this->sub_unit_to_base_quantity : null,
            'net_quantity' => $this->net_quantity !== null ? (float) $this->net_quantity : null,
            'is_default' => (bool) ($this->is_default ?? false),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
