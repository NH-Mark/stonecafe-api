<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuItem\StoreMenuItemRequest;
use App\Http\Requests\MenuItem\UpdateMenuItemRequest;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use App\Services\MenuItemService;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    public function __construct(
        private MenuItemService $menuItemService
    )
    {

    }

    public function show($id)
    {
        $menuItem = MenuItem::with([
            'menu_category',
            'modifierGroups' => function ($query) {
                $query->withCount('modifiers');
            },
            'foodSymbols',
            'menuItemTags'
        ])
        ->withCount('modifierGroups')
        ->findOrFail($id);


        return response()->json([
            'success' => true,
            'data' =>  new MenuItemResource($menuItem),
        ]);
    }

   public function index(Request $request)
    {
        $items = MenuItem::with([
            'menu_category'
        ])
        ->when(
            $request->category_id,
            function ($query, $categoryId) {
                $query->where('menu_category_id', $categoryId);
            }
        )
        ->get();

        return MenuItemResource::collection($items);
    }




    public function store(
        StoreMenuItemRequest $request
    )
    {

        $item =
            $this->menuItemService
                ->create(
                    $request->validated()
                );


        return new MenuItemResource(
            $item
        );

    }





    public function update(
        UpdateMenuItemRequest $request,
        MenuItem $menu_item
    )
    {

        $item =
            $this->menuItemService
                ->update(
                    $menu_item,
                    $request->validated()
                );


        return new MenuItemResource(
            $item
        );

    }


    public function destroy(
        MenuItem $menu_item
    )
    {
        $this->menuItemService
            ->delete(
                $menu_item
            );

        return response()->json([
            'message'
                => $menu_item
        ]);

    }
}
