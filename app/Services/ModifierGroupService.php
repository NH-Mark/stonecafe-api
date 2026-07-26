<?php

namespace App\Services;

use App\Models\ModifierGroup;

class ModifierGroupService
{
    public function create(array $data): ModifierGroup
    {
        return ModifierGroup::create(
            $this->prepare($data)
        );
    }


    public function update(
        ModifierGroup $group,
        array $data
    ): ModifierGroup {

        $group->update(
            $this->prepare($data)
        );

        return $group->refresh();
    }


    protected function prepare(array $data): array
    {

        $selectionType = $data['selection_type'] ?? 'single';


        if ($selectionType === 'single') {

            $data['min_selection'] =
                !empty($data['required'])
                    ? 1
                    : 0;


            $data['max_selection'] = 1;

        }


        if ($selectionType === 'multiple') {


            $data['min_selection'] =
                $data['min_selection'] ?? 0;


            $data['max_selection'] =
                $data['max_selection'] ?? 1;


            if (
                $data['max_selection'] <
                $data['min_selection']
            ) {

                $data['max_selection'] =
                    $data['min_selection'];

            }

        }


        return $data;
    }
}