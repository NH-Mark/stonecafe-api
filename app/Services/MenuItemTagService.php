<?php

namespace App\Services;

use App\Models\MenuItemTag;

class MenuItemTagService
{
    public function create(array $data): MenuItemTag
    {
        return MenuItemTag::create($data);
    }

    public function update(
        MenuItemTag $menu_item_tag,
        array $data
    ): MenuItemTag {

        $menu_item_tag->update($data);

        return $menu_item_tag->refresh();
    }
}