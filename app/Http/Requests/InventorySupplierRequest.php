<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventorySupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('inventory_suppliers', 'supplier_code')
                    ->ignore($this->inventory_supplier?->id),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                // Rule::unique('inventory_suppliers', 'email')
                //     ->ignore($this->inventory_supplier?->id),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],
            'alternate_phone' => [
                'nullable',
                'string',
                'max:50',
            ],
            'tax_number' => [
                'nullable',
                'string',
                'max:255',
            ],
            'address' => [
                'nullable',
                'string',
            ],
            'city' => [
                'nullable',
                'string',
                'max:255',
            ],
            'country' => [
                'nullable',
                'string',
                'max:255',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Supplier name is required.',
            'email.email' => 'Supplier email must be a valid email address.',
            'email.unique' => 'Supplier email already exists.',
            'supplier_code.unique' => 'Supplier code already exists.',
        ];
    }
}
