<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuItem\StoreMenuItemRequest;
use App\Http\Requests\MenuItem\UpdateMenuItemRequest;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use App\Services\MenuItemService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    public function __construct(
        private MenuItemService $menuItemService
    ) {}

    public function show(Request $request, $id)
    {
        $locationId = $request->filled('location_id')
            ? $request->integer('location_id')
            : null;

        $relations = [
            'menu_category',
            'modifierGroups' => function ($query) {
                $query->withCount('modifiers');
            },
            'foodSymbols',
            'menuItemTags',
            'menu_item_ingredients.ingredient',
        ];

        $relations['locations'] = function ($query) use ($locationId) {
            if ($locationId !== null) {
                $query->where('locations.id', $locationId);
            }
        };

        $menuItem = MenuItem::with($relations)
            ->withCount('modifierGroups')
            ->findOrFail($id);


        return response()->json([
            'success' => true,
            'data' =>  new MenuItemResource($menuItem),
        ]);
    }
    public function index(Request $request)
    {
        $locationId = $request->filled('location_id')
            ? $request->integer('location_id')
            : null;

        $perPage = min(
            $request->integer('per_page', 10),
            100
        );

        $relations = [
            'menu_category',
            'modifierGroups.modifiers',
            'foodSymbols',
            'menuItemTags',
            'menu_item_ingredients.ingredient',
        ];

        if ($locationId !== null) {
            $relations['locations'] = function ($query) use ($locationId) {
                $query->where('locations.id', $locationId);
            };

            $relations['menu_category.locations'] = function ($query) use ($locationId) {
                $query->where('locations.id', $locationId);
            };
        }

        $items = MenuItem::with($relations)

            /**
             * Category filter.
             */
            ->when(
                $request->filled('category_id'),
                function ($query) use ($request) {
                    $query->where(
                        'menu_category_id',
                        $request->integer('category_id')
                    );
                }
            )

            /**
             * Search.
             */
            ->when(
                $request->filled('search'),
                function ($query) use ($request) {
                    $search = $request->input('search');

                    $query->where(function ($q) use ($search) {
                        $q->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                            ->orWhere(
                                'name_ar',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'barcode',
                                'like',
                                "%{$search}%"
                            );
                    });
                }
            )

            /**
             * Active items only.
             */
            ->when(
                $request->boolean('active'),
                function (Builder $query) use ($locationId) {
                    $query->where(function (Builder $itemQuery) use ($locationId) {
                        if ($locationId === null) {
                            $itemQuery->where('active', true);
                            return;
                        }

                        $itemQuery->whereHas('locations', function (Builder $locationQuery) use ($locationId) {
                            $locationQuery
                                ->where('locations.id', $locationId)
                                ->where('menu_item_location_statuses.active', true);
                        });
                    })->whereHas('menu_category', function (Builder $categoryQuery) use ($locationId) {
                        if ($locationId === null) {
                            $categoryQuery->where('active', true);
                            return;
                        }

                        $categoryQuery->whereHas('locations', function (Builder $locationQuery) use ($locationId) {
                            $locationQuery
                                ->where('locations.id', $locationId)
                                ->where('menu_category_location_statuses.active', true);
                        });
                    });
                }
            )

            ->latest()
            ->paginate($perPage);

        return MenuItemResource::collection(
            $items
        );
    }


    public function listMenu(Request $request)
    {
        $locationId = $request->filled('location_id')
            ? $request->integer('location_id')
            : null;

        $relations = [
            'menu_category',
            'modifierGroups.modifiers',
            'foodSymbols',
            'menuItemTags',
            'menu_item_ingredients.ingredient',
        ];

        if ($locationId !== null) {
            $relations['locations'] = function ($query) use ($locationId) {
                $query->where('locations.id', $locationId);
            };

            $relations['menu_category.locations'] = function ($query) use ($locationId) {
                $query->where('locations.id', $locationId);
            };
        }

        $items = MenuItem::with($relations)
            ->where(function (Builder $query) use ($locationId) {
                if ($locationId === null) {
                    $query->where('active', true);
                    return;
                }

                $query->whereHas('locations', function (Builder $locationQuery) use ($locationId) {
                    $locationQuery
                        ->where('locations.id', $locationId)
                        ->where('menu_item_location_statuses.active', true);
                });
            })
            ->whereHas('menu_category', function (Builder $query) use ($locationId) {
                if ($locationId === null) {
                    $query->where('active', true);
                    return;
                }

                $query->whereHas('locations', function (Builder $locationQuery) use ($locationId) {
                    $locationQuery
                        ->where('locations.id', $locationId)
                        ->where('menu_category_location_statuses.active', true);
                });
            })
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
    ) {

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
    ) {

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
    ) {
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
