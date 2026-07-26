<?php

namespace App\Http\Requests\ModifierGroup;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreModifierGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'selection_type' => [
                'required',
                Rule::in([
                    'single',
                    'multiple',
                ]),
            ],

            'required' => [
                'required',
                'boolean',
            ],

            'min_selection' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'max_selection' => [
                'nullable',
                'integer',
                'gte:min_selection',
            ],

            'active' => [
                'required',
                'boolean',
            ],

        ];
    }
}