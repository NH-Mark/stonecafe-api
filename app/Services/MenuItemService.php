<?php

namespace App\Services;

use App\Models\MenuItem;
use Illuminate\Support\Arr;

class MenuItemService
{
    public function create(array $data): MenuItem
    {
        $modifierGroups = $data['modifier_groups'] ?? [];
        $foodSymbols = $data['food_symbols'] ?? [];
        $tags = $data['menu_item_tags'] ?? [];

        unset(
            $data['modifier_groups'],
            $data['food_symbols'],
            $data['menu_item_tags']
        );

        $item = MenuItem::create($data);

        $this->syncRelations(
            $item,
            $modifierGroups,
            $foodSymbols,
            $tags
        );

        return $item->load([
            'modifierGroups',
            'foodSymbols',
            'menuItemTags',
        ]);
    }

    public function update(
        MenuItem $item,
        array $data
    ): MenuItem {

        $modifierGroups = $data['modifier_groups'] ?? [];
        $foodSymbols = $data['food_symbols'] ?? [];
        $tags = $data['menu_item_tags'] ?? [];

        unset(
            $data['modifier_groups'],
            $data['food_symbols'],
            $data['menu_item_tags']
        );

        $item->update($data);

        $this->syncRelations(
            $item,
            $modifierGroups,
            $foodSymbols,
            $tags
        );

        return $item->refresh()->load([
            'modifierGroups',
            'foodSymbols',
            'menuItemTags',
        ]);
    }

    protected function syncRelations(
        MenuItem $item,
        array $modifierGroups,
        array $foodSymbols,
        array $tags
    ): void {

        $pivot = [];

        foreach ($modifierGroups as $group) {

            $pivot[$group['id']] = [

                'selection_type' => $group['selection_type'],

                'required' => $group['required'],

                'min_selection' => $group['selection_type'] === 'single'
                    ? ($group['required'] ? 1 : 0)
                    : $group['min_selection'],

                'max_selection' => $group['selection_type'] === 'single'
                    ? 1
                    : $group['max_selection'],
            ];
        }

        $item->modifierGroups()->sync($pivot);

        $item->foodSymbols()->sync($foodSymbols);

        $item->menuItemTags()->sync($tags);
    }

    public function delete(MenuItem $menuItem): void
    {
        $menuItem->modifierGroups()->detach();

        $menuItem->foodSymbols()->detach();

        $menuItem->menuItemTags()->detach();

        $menuItem->delete();
    }
}
