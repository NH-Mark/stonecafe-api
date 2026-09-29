<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SapaadImportUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'orders_file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:51200',
            ],

            'items_file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:51200',
            ],

            'location_id' => [
                'nullable',
                'integer',
                'exists:locations,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'orders_file.required' => 'Orders CSV file is required.',
            'orders_file.file' => 'Orders file must be a valid file.',
            'orders_file.mimes' => 'Orders file must be a CSV file.',

            'items_file.required' => 'Items CSV file is required.',
            'items_file.file' => 'Items file must be a valid file.',
            'items_file.mimes' => 'Items file must be a CSV file.',

            'location_id.exists' => 'The selected location does not exist.',
        ];
    }
}