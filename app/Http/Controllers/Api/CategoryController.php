<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\MenuCategory;
use App\Services\CategoryService;



class CategoryController extends Controller
{

    public function __construct(
        private CategoryService $categoryService
    )
    {

    }



    public function index()
    {

        $categories =
            MenuCategory::with([
                'parent',
                'children'
            ])
            ->withCount('menuItems')
            ->orderBy(
                'sort_order'
            )
            ->get();

        return CategoryResource::collection(
            $categories
        );

    }

    public function list()
    {

        $categories =
            MenuCategory::with([
                'parent',
                'children'
            ])
            ->withCount('menuItems')
            ->orderBy(
                'sort_order'
            )
            ->where('active',1)
            ->get();

        return CategoryResource::collection(
            $categories
        );

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

}