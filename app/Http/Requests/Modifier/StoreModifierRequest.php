<?php

namespace App\Http\Requests\Modifier;

use Illuminate\Foundation\Http\FormRequest;

class StoreModifierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'modifier_group_id' => [
                'required',
                'exists:modifier_groups,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'active' => [
                'required',
                'boolean',
            ],

        ];
    }
}