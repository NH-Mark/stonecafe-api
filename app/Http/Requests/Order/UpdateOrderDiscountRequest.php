<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => [
                'sometimes',
                'array',
            ],

            'items.*.id' => [
                'required',
                'integer',
                'exists:order_items,id',
            ],

            'items.*.discounts' => [
                'nullable',
                'array',
            ],

            'items.*.discounts.*.discount_id' => [
                'required',
                'integer',
                'exists:discounts,id',
            ],

            'items.*.discounts.*.amount' => [
                'required',
                'numeric',
                'min:0',
            ],

            'discounts' => [
                'sometimes',
                'array',
            ],

            'discounts.*.discount_id' => [
                'required',
                'integer',
                'exists:discounts,id',
            ],

            'discounts.*.amount' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }
}