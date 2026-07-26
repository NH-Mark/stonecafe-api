<?php

namespace App\Services;

use App\Models\MenuCategory;


class CategoryService
{


    public function create(array $data): MenuCategory
    {

        return MenuCategory::create($data)
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

        $category->update($data);


        return $category->load([
            'parent',
            'children'
        ]);

    }



    public function delete(
        MenuCategory $category
    ): bool
    {

        return $category->delete();

    }



}