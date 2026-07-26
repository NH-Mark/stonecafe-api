<?php

namespace App\Http\Requests\Modifier;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuItemTagRequest extends FormRequest
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

            'active' => [
                'required',
                'boolean',
            ],

        ];
    }
}