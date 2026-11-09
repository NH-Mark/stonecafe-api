<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryIngredientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ingredient_type' => ['nullable', 'string', 'in:ingredient,sub_recipe'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('inventory_ingredients', 'sku')->ignore($this->route('inventory_ingredient')?->id),
            ],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:inventory_ingredient_categories,id'],
            'measurement_unit_id' => ['required', 'integer', 'exists:inventory_measurement_units,id'],
            'buying_unit_name' => ['nullable', 'string', 'max:255'],
            'has_sub_unit' => ['nullable', 'boolean'],
            'sub_unit_name' => ['nullable', 'string', 'max:255'],
            'buying_unit_quantity' => ['nullable', 'numeric'],
            'unit_to_sub_unit_quantity' => ['nullable', 'numeric'],
            'sub_unit_to_base_quantity' => ['nullable', 'numeric'],
            'net_quantity' => ['nullable', 'numeric'],
            'last_buying_price' => ['nullable', 'numeric'],
            'cost_per_unit' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],

            'suppliers' => ['nullable', 'array'],
            'suppliers.*.supplier_id' => ['required_with:suppliers', 'integer', 'exists:inventory_suppliers,id'],
            'suppliers.*.price' => ['nullable', 'numeric'],
            'suppliers.*.buying_unit' => ['nullable', 'string', 'max:255'],
            'suppliers.*.has_sub_unit' => ['nullable', 'boolean'],
            'suppliers.*.sub_unit_name' => ['nullable', 'string', 'max:255'],
            'suppliers.*.unit_to_sub_unit_quantity' => ['nullable', 'numeric'],
            'suppliers.*.sub_unit_to_base_quantity' => ['nullable', 'numeric'],
            'suppliers.*.net_quantity' => ['nullable', 'numeric'],
            'suppliers.*.is_default' => ['nullable', 'boolean'],

            'locations' => ['nullable', 'array'],
            'locations.*.location_id' => ['required_with:locations', 'integer', 'exists:locations,id'],
            'locations.*.opening_balance' => ['nullable', 'numeric'],
            'locations.*.reorder_level' => ['nullable', 'numeric'],
            'locations.*.track_inventory' => ['nullable', 'boolean'],
        ];
    }
}
