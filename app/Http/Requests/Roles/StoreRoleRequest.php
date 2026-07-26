<?php

namespace App\Http\Requests\Roles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
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
                'min:2',
                Rule::unique('roles', 'name'),
            ],

            'permissions' => [
                'required',
                'array'
            ],

            'permissions.*' => [
                'string',
                'exists:permissions,name'
            ]

        ];
    }
}