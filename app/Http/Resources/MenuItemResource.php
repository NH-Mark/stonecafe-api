<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MenuItemResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'name' => $this->name,

            'description' => $this->description,


           'image' => $this->image
            ? Storage::url(
                str_replace('/storage/', '', $this->image)
            )
            : null,


            'price' => $this->price,
            'required' => $this->required,

            'active' => $this->active,
            'barcode' => $this->barcode,
            'sku' => $this->sku,

            'available' => $this->available,
            'menu_category_id' => $this->menu_category_id,

            'category' => new CategoryResource(
                $this->whenLoaded('menu_category')
            ),


            'modifier_groups' =>
            ModifierGroupResource::collection(
                $this->whenLoaded(
                    'modifierGroups'
                )
            ),
            'food_symbols' => FoodSymbolResource::collection(
                $this->whenLoaded('foodSymbols')
            ),

            'menu_item_tags' => MenuItemTagResource::collection(
                $this->whenLoaded('menuItemTags')
            ),

        ];
    }
}
