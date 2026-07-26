<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modifier\StoreMenuItemTagRequest;
use App\Http\Requests\Modifier\UpdateMenuItemTagRequest;
use App\Models\MenuItemTag;
use App\Services\MenuItemTagService;
use Illuminate\Http\Request;
use App\Http\Resources\MenuItemTagResource;

class MenuItemTagController extends Controller
{
     public function __construct(
        protected MenuItemTagService $service
    ) {}

    public function index()
    {
        return MenuItemTagResource::collection(
            MenuItemTag::get()
        );
    }

    public function store(StoreMenuItemTagRequest $request)
    {
        return new MenuItemTagResource(
            $this->service->create(
                $request->validated()
            )
        );
    }

    public function update(
        UpdateMenuItemTagRequest $request,
        MenuItemTag $menu_item_tag
    ) {
        return new MenuItemTagResource(
            $this->service->update(
                $menu_item_tag,
                $request->validated()
            )
        );
    }

    public function destroy(
        MenuItemTag $menu_item_tag
    ) {
        $menu_item_tag->delete();

        return response()->json([
            'message' => 'tag deleted.'
        ]);
    }
}
