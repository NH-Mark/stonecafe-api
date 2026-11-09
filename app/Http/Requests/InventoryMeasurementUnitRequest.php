<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryMeasurementUnitRequest extends FormRequest
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
                Rule::unique('inventory_measurement_units', 'name')
                    ->ignore($this->inventory_measurement_unit?->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Measurement unit name is required.',
            'name.unique' => 'Measurement unit name already exists.',
        ];
    }
}
