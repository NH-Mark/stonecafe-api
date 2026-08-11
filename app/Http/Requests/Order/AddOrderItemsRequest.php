<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\Order\OrderRequest;

class AddOrderItemsRequest extends OrderRequest
{
      public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'location_id' => ['nullable', 'exists:locations,id'],
            'order_type_id' => ['nullable', 'exists:order_types,id'],
            'order_source_id' => ['nullable', 'exists:order_sources,id'],

            'customer_id' => ['nullable', 'exists:customers,id'],
            'table_id' => ['nullable', 'exists:restaurant_tables,id'],

            'subtotal' => ['nullable', 'numeric'],
            'discount_amount' => ['nullable', 'numeric'],
            'tax_amount' => ['nullable', 'numeric'],
            'service_charge' => ['nullable', 'numeric'],
            'total_amount' => ['nullable', 'numeric'],

            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],

            'items.*.menu_item_id' => [
                'required',
                'exists:menu_items,id'
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1'
            ],

            'items.*.unit_price' => [
                'required',
                'numeric'
            ],

            'items.*.total_price' => [
                'required',
                'numeric'
            ],

            'items.*.notes' => [
                'nullable',
                'string'
            ],

            'items.*.modifiers' => [
                'array'
            ],

            'items.*.modifiers.*.modifier_id' => [
                'required',
                'exists:modifiers,id'
            ],

            'items.*.modifiers.*.quantity' => [
                'required',
                'integer',
                'min:1'
            ],

            'items.*.modifiers.*.price' => [
                'required',
                'numeric'
            ],

            'discounts' => ['array'],

            'discounts.*.discount_id' => [
                'required',
                'exists:discounts,id'
            ],

            'discounts.*.amount' => [
                'required',
                'numeric'
            ],

            // 'payment.payment_method_id' => [
            //     'required',
            //     'exists:payment_methods,id',
            // ],

            // 'payment.amount' => [
            //     'required',
            //     'numeric',
            // ],

            'payment.reference' => [
                'nullable',
                'string'
            ],

            'items.*.discounts' => [
                'nullable',
                'array',
            ],

            'items.*.discounts.*.discount_id' => [
                'required',
                'integer',
            ],

            'items.*.discounts.*.amount' => [
                'required',
                'numeric',
                'min:0',
            ],

        ];
    }
}