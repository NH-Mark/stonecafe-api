<?php

namespace App\Http\Requests\MenuItem;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuItemRequest extends StoreMenuItemRequest
{
  public function rules(): array
    {
        $rules = parent::rules();

        $rules['barcode'] = [
            'nullable',
            'string',
            Rule::unique('menu_items', 'barcode')
                ->ignore($this->route('menu_item')),
        ];

        return $rules;
    }
}