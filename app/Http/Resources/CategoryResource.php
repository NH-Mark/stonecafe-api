<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CategoryResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'name' => $this->name,
            'name_ar' => $this->name_ar,

            'description' => $this->description,
            'description_ar' => $this->description_ar,

            'image' => $this->image
            ? Storage::url(
                str_replace('/storage/', '', $this->image)
            )
            : null,

            'active' => (bool) $this->active,

            'parent_id' => $this->parent_id,

            'sort_order' => $this->sort_order,


            // Parent category
            'parent' => $this->whenLoaded(
                'parent',
                function () {
                    return [
                        'id' => $this->parent->id,
                        'name' => $this->parent->name,
                    ];
                }
            ),


            // Sub categories
            'children' => CategoryResource::collection(
                $this->whenLoaded('children')
            ),


            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,
            'menu_items_count' => $this->menu_items_count,

        ];
    }

}