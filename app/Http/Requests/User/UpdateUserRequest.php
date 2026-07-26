<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        $user = $this->route('user');


        return [

            'name' => [
                'required',
                'string',
                'max:255',
            ],


            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')
                    ->ignore($user->id),
            ],


            'password' => [
                'nullable',
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