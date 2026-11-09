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

            'name_ar' => $this->name_ar,
            'description_ar' => $this->description_ar,

            'description' => $this->description,


           'image' => $this->image
            ? Storage::url(
                str_replace('/storage/', '', $this->image)
            )
            : null,


            'price' => $this->price,
            'cost_price' => $this->cost_price,
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

            'location_statuses' => $this->whenLoaded(
                'locations',
                function () {
                    return $this->locations->map(function ($location) {
                        return [
                            'location_id' => $location->id,
                            'location_name' => $location->name,
                            'active' => (bool) $location->pivot?->active,
                        ];
                    })->values();
                },
                []
            ),

            'menu_item_ingredients' => $this->whenLoaded(
                'menu_item_ingredients',
                fn () => MenuItemIngredientResource::collection($this->menu_item_ingredients),
                []
            ),

            'printer_id' => $this->printer?->id,
            'printer' => $this->printer,

        ];
    }
}
