<?php

namespace App\Services;

use App\Models\Location;
use App\Models\MenuCategory;


class CategoryService
{


    public function create(array $data): MenuCategory
    {

        $locationStatuses = $data['location_statuses'] ?? [];
        $defaultActive = (bool) ($data['active'] ?? true);

        unset($data['location_statuses']);

        $category = MenuCategory::create($data);

        $this->syncLocationStatuses(
            $category,
            $locationStatuses,
            $defaultActive,
            false
        );

        return $category
            ->load([
                'parent',
                'children'
            ]);

    }



    public function update(
        MenuCategory $category,
        array $data
    ): MenuCategory
    {

        $activeProvided = array_key_exists('active', $data);
        $locationStatuses = $data['location_statuses'] ?? [];
        $defaultActive = (bool) ($data['active'] ?? $category->active);

        unset($data['location_statuses']);

        $category->update($data);

        $this->syncLocationStatuses(
            $category,
            $locationStatuses,
            $defaultActive,
            $activeProvided
        );


        return $category->load([
            'parent',
            'children'
        ]);

    }



    public function delete(
        MenuCategory $category
    ): bool
    {

        $category->locations()->detach();

        return $category->delete();

    }

    protected function syncLocationStatuses(
        MenuCategory $category,
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

            $category->locations()->syncWithoutDetaching($statusData);
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

            $category->locations()->syncWithoutDetaching($allLocations);
            return;
        }

        $existingLocationIds = $category->locations()
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

        $category->locations()->syncWithoutDetaching($missingStatusData);
    }



}