<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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


            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email'),
            ],


            'password' => [
                'required',
                'string',
                'min:8',
            ],


            'role_id' => [
                'required',
                'exists:roles,id',
            ],


            'location_id' => [
                'nullable',
                'exists:locations,id',
            ],

        ];
    }


    public function messages(): array
    {
        return [

            'email.unique'
                => 'Email already exists.',

            'role_id.exists'
                => 'Invalid role selected.',

            'location_id.exists'
                => 'Invalid location selected.',

        ];
    }
}