<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\MenuCategory;
use App\Services\CategoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CategoryController extends Controller
{

    public function __construct(
        private CategoryService $categoryService
    )
    {

    }

    public function index(Request $request)
    {
        $locationId = $request->filled('location_id')
            ? $request->integer('location_id')
            : null;

        $categories = MenuCategory::with([
            'parent',
            'children',
            'locations' => function ($query) use ($locationId) {
                if ($locationId !== null) {
                    $query->where('locations.id', $locationId);
                }
            },
        ])
            ->when(
                $request->boolean('active'),
                function (Builder $query) use ($locationId) {
                    if ($locationId === null) {
                        $query->where('active', true);
                        return;
                    }

                    $query->where(function (Builder $activeQuery) use ($locationId) {
                        $activeQuery
                            ->whereHas('locations', function (Builder $locationQuery) use ($locationId) {
                                $locationQuery
                                    ->where('locations.id', $locationId)
                                    ->where('menu_category_location_statuses.active', true);
                            })
                            ->orWhere(function (Builder $fallbackQuery) use ($locationId) {
                                $fallbackQuery
                                    ->where('active', true)
                                    ->whereDoesntHave('locations', function (Builder $locationQuery) use ($locationId) {
                                        $locationQuery->where('locations.id', $locationId);
                                    });
                            });
                    });
                }
            )
            ->withCount([
                'menuItems as menu_items_count' => function (Builder $query) use ($locationId) {
                    $this->applyActiveMenuItemFilter($query, $locationId);
                },
            ])
            ->orderBy('sort_order')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function list(Request $request)
    {

        $locationId = $request->filled('location_id')
            ? $request->integer('location_id')
            : null;

        $categories =
            MenuCategory::with([
                'parent',
                'children',
                'locations' => function ($query) use ($locationId) {
                    if ($locationId !== null) {
                        $query->where('locations.id', $locationId);
                    }
                },
            ])
            ->withCount([
                'menuItems as menu_items_count' => function (Builder $query) use ($locationId) {
                    $this->applyActiveMenuItemFilter($query, $locationId);
                },
            ])
            ->orderBy(
                'sort_order'
            )
            ->where(function (Builder $query) use ($locationId) {
                if ($locationId === null) {
                    $query->where('active', true);
                    return;
                }

                $query->where(function (Builder $activeQuery) use ($locationId) {
                    $activeQuery
                        ->whereHas('locations', function (Builder $locationQuery) use ($locationId) {
                            $locationQuery
                                ->where('locations.id', $locationId)
                                ->where('menu_category_location_statuses.active', true);
                        })
                        ->orWhere(function (Builder $fallbackQuery) use ($locationId) {
                            $fallbackQuery
                                ->where('active', true)
                                ->whereDoesntHave('locations', function (Builder $locationQuery) use ($locationId) {
                                    $locationQuery->where('locations.id', $locationId);
                                });
                        });
                });
            })
            ->get();

        return CategoryResource::collection(
            $categories
        );

    }

    public function show(Request $request, MenuCategory $category)
    {
        $locationId = $request->filled('location_id')
            ? $request->integer('location_id')
            : null;

        $category->load([
            'parent',
            'children',
            'locations' => function ($query) use ($locationId) {
                if ($locationId !== null) {
                    $query->where('locations.id', $locationId);
                }
            },
        ])->loadCount([
            'menuItems as menu_items_count' => function (Builder $query) use ($locationId) {
                $this->applyActiveMenuItemFilter($query, $locationId);
            },
        ]);

        return new CategoryResource($category);
    }




    public function store(
        CategoryRequest $request
    )
    {

        $category =
            $this->categoryService
                ->create(
                    $request->validated()
                );


        return new CategoryResource(
            $category
        );

    }





    public function update(
        CategoryRequest $request,
        MenuCategory $category
    )
    {

        $category =
            $this->categoryService
                ->update(
                    $category,
                    $request->validated()
                );


        return new CategoryResource(
            $category
        );

    }





    public function destroy(
        MenuCategory $category
    )
    {

        $this->categoryService
            ->delete(
                $category
            );


        return response()->json([

            'message'
                => 'Category deleted successfully.'

        ]);

    }

    private function applyActiveMenuItemFilter(Builder $query, ?int $locationId): void
    {
        if ($locationId === null) {
            $query->where('active', true);
            return;
        }

        $query->where(function (Builder $activeQuery) use ($locationId) {
            $activeQuery
                ->whereHas('locations', function (Builder $locationQuery) use ($locationId) {
                    $locationQuery
                        ->where('locations.id', $locationId)
                        ->where('menu_item_location_statuses.active', true);
                })
                ->orWhere(function (Builder $fallbackQuery) use ($locationId) {
                    $fallbackQuery
                        ->where('active', true)
                        ->whereDoesntHave('locations', function (Builder $locationQuery) use ($locationId) {
                            $locationQuery->where('locations.id', $locationId);
                        });
                });
        });
    }

}