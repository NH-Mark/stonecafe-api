<?php

namespace App\Services;

use App\Models\Location;
use App\Models\MenuItem;

class MenuItemService
{
    public function create(array $data): MenuItem
    {
        $modifierGroups = $data['modifier_groups'] ?? [];
        $foodSymbols = $data['food_symbols'] ?? [];
        $tags = $data['menu_item_tags'] ?? [];
        $locationStatuses = $data['location_statuses'] ?? [];
        $defaultActive = (bool) ($data['active'] ?? true);

        unset(
            $data['modifier_groups'],
            $data['food_symbols'],
            $data['menu_item_tags'],
            $data['location_statuses']
        );

        $item = MenuItem::create($data);

        $this->syncLocationStatuses(
            $item,
            $locationStatuses,
            $defaultActive,
            false
        );

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
            'printer',
        ]);
    }

    public function update(
        MenuItem $item,
        array $data
    ): MenuItem {

        $activeProvided = array_key_exists('active', $data);

        $modifierGroups = $data['modifier_groups'] ?? [];
        $foodSymbols = $data['food_symbols'] ?? [];
        $tags = $data['menu_item_tags'] ?? [];
        $locationStatuses = $data['location_statuses'] ?? [];
        $defaultActive = (bool) ($data['active'] ?? $item->active);

        unset(
            $data['modifier_groups'],
            $data['food_symbols'],
            $data['menu_item_tags'],
            $data['location_statuses']
        );

        $item->update($data);

        $this->syncLocationStatuses(
            $item,
            $locationStatuses,
            $defaultActive,
            $activeProvided
        );

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
              'printer',
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
        $menuItem->locations()->detach();

        $menuItem->modifierGroups()->detach();

        $menuItem->foodSymbols()->detach();

        $menuItem->menuItemTags()->detach();

        $menuItem->delete();
    }

    protected function syncLocationStatuses(
        MenuItem $item,
        array $locationStatuses,
        bool $defaultActive,
        bool $updateAllWhenNoStatuses
    ): void {
        if ($locationStatuses !== []) {
            $statusData = [];

            foreach ($locationStatuses as $status) {
                $statusData[$status['location_id']] = [
                    'active' => (bool) $status['active'],
                ];
            }

            $item->locations()->syncWithoutDetaching($statusData);
        }

        if ($updateAllWhenNoStatuses && $locationStatuses === []) {
            $locationIds = Location::query()->pluck('id');

            if ($locationIds->isEmpty()) {
                return;
            }

            $allLocations = [];
            foreach ($locationIds as $locationId) {
                $allLocations[$locationId] = ['active' => $defaultActive];
            }

            $item->locations()->syncWithoutDetaching($allLocations);
            return;
        }

        $existingLocationIds = $item->locations()
            ->pluck('locations.id')
            ->all();

        $missingLocationIds = Location::query()
            ->whereNotIn('id', $existingLocationIds)
            ->pluck('id');

        if ($missingLocationIds->isEmpty()) {
            return;
        }

        $missingStatusData = [];
        foreach ($missingLocationIds as $locationId) {
            $missingStatusData[$locationId] = ['active' => $defaultActive];
        }

        $item->locations()->syncWithoutDetaching($missingStatusData);
    }
}
