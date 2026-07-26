<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class LocationRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }



    public function rules(): array
    {

        $locationId = $this->route('location')?->id;


        return [

            'name' => [
                'required',
                'string',
                'max:255'
            ],


            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('locations')
                    ->ignore($locationId)
            ],


            'address' => [
                'nullable',
                'string',
                'max:500'
            ],


            'phone' => [
                'nullable',
                'string',
                'max:50'
            ],


            'status' => [
                'boolean'
            ],

        ];

    }



    public function messages(): array
    {

        return [

            'name.required' =>
                'Location name is required.',


            'code.required' =>
                'Location code is required.',


            'code.unique' =>
                'Location code already exists.',


            'status.boolean' =>
                'Invalid status value.',

        ];

    }

}