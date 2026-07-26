<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ModifierGroupResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'name' => $this->name,

            'required' => (bool) $this->required,

            'min_selection' => $this->min_selection,

            'max_selection' => $this->max_selection,

            'selection_type' => $this->selection_type,
            'modifiers_count' => count($this->modifiers),
            'active' => (bool) $this->active,
            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

            'pivot' => $this->pivot ? [

                'menu_item_id' => $this->pivot->menu_item_id,

                'modifier_group_id' => $this->pivot->modifier_group_id,

                'selection_type' =>
                    $this->pivot->selection_type,

                'required' =>
                    (bool) $this->pivot->required,

                'min_selection' =>
                    $this->pivot->min_selection,

                'max_selection' =>
                    $this->pivot->max_selection,

            ] : null,



        ];
    }

}