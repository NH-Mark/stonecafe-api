<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModifierResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'name' => $this->name,

            'price' => (float) $this->price,

            'active' => (bool) $this->active,


            'modifier_group_id' => $this->modifier_group_id,


            'modifier_group' => $this->whenLoaded(
                'group',
                function () {

                    return [

                        'id' => $this->group->id,

                        'name' => $this->group->name,

                    ];

                }
            ),


            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

        ];
    }

}