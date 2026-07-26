<?php

namespace App\Http\Requests\MenuItem;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [

            'menu_category_id' => [
                'required',
                'exists:menu_categories,id'
            ],

            'name' => [
                'required',
                'string',
                'max:255'
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'sku' => [
                'nullable',
                'string'
            ],

            'barcode' => [
                'nullable',
                'string',
                 'unique:menu_items,barcode',
            ],

            'image' => [
                'nullable',
                'string'
            ],

            'price' => [
                'required',
                'numeric',
                'min:0'
            ],

            'cost_price' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'active' => [
                'boolean'
            ],

            'food_symbols' => [
                'array'
            ],

            'food_symbols.*' => [
                'exists:food_symbols,id'
            ],

            'menu_item_tags' => [
                'array'
            ],

            'menu_item_tags.*' => [
                'exists:menu_item_tags,id'
            ],

            'modifier_groups' => [
                'array'
            ],

            'modifier_groups.*.id' => [
                'required',
                'exists:modifier_groups,id'
            ],

            'modifier_groups.*.selection_type' => [
                'required',
                'in:single,multiple'
            ],

            'modifier_groups.*.required' => [
                'required',
                'boolean'
            ],

            'modifier_groups.*.min_selection' => [
                'required',
                'integer',
                'min:0'
            ],

            'modifier_groups.*.max_selection' => [
                'required',
                'integer',
                'min:1'
            ],

        ];
    }


    public function messages(): array
    {
        return [

            'menu_category_id.required'
            => 'Category is required.',


            'menu_category_id.exists'
            => 'Selected category does not exist.',


            'name.required'
            => 'Menu item name is required.',


            'price.required'
            => 'Price is required.',


            'price.numeric'
            => 'Price must be a number.',


            'modifier_group_ids.*.exists'
            => 'Invalid modifier group selected.'

        ];
    }
}
