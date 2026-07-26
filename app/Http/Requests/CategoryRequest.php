<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class CategoryRequest extends FormRequest
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

                Rule::unique('menu_categories')
                    ->ignore(
                        $this->category?->id
                    ),
            ],


            'description' => [
                'nullable',
                'string',
            ],


            'image' => [
                'nullable',
                'string',
            ],


            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],


            'active' => [
                'required',
                'boolean',
            ],


            'parent_id' => [
                'nullable',
                'integer',
                Rule::excludeIf(fn() => request('parent_id') == 0),
                'exists:menu_categories,id',
            ],

        ];
    }


    public function messages(): array
    {

        return [

            'name.required'
            => 'Category name is required.',


            'parent_id.exists'
            => 'Selected parent category does not exist.',

        ];
    }
}
