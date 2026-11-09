<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryIntegredientCategoryRequest;
use App\Http\Resources\InventoryIntegredientCategoryResource;
use App\Models\InventoryIntegredientCategory;
use Illuminate\Http\Request;

class InventoryIntegredientCategoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min($request->integer('per_page', 10), 100);

        $query = InventoryIntegredientCategory::query();

        $query->when($request->filled('search'), function ($query) use ($request) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        });

        $filters = $request->input('filters', []);

        if (is_string($filters)) {
            $decoded = json_decode($filters, true);
            $filters = is_array($decoded) ? $decoded : [];
        }

        if (is_array($filters)) {
            foreach ($filters as $filter) {
                $column = $filter['id'] ?? null;
                $value = $filter['value'] ?? null;

                if (!$column || $value === null || $value === '') {
                    continue;
                }

                switch ($column) {
                    case 'name':
                        $query->where('name', 'like', "%{$value}%");
                        break;

                    case 'description':
                        $query->where('description', 'like', "%{$value}%");
                        break;

                    case 'is_active':
                        $query->where(
                            'is_active',
                            filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value
                        );
                        break;
                }
            }
        }

        $ingredientCategories = $query
            ->latest()
            ->paginate($perPage);

        return InventoryIntegredientCategoryResource::collection($ingredientCategories);
    }

    public function show(InventoryIntegredientCategory $inventoryIntegredientCategory)
    {
        return new InventoryIntegredientCategoryResource($inventoryIntegredientCategory);
    }

    public function store(InventoryIntegredientCategoryRequest $request)
    {
        $ingredientCategory = InventoryIntegredientCategory::create($request->validated());

        return new InventoryIntegredientCategoryResource($ingredientCategory);
    }

    public function update(
        InventoryIntegredientCategoryRequest $request,
        InventoryIntegredientCategory $inventoryIntegredientCategory
    ) {
        $inventoryIntegredientCategory->update($request->validated());

        return new InventoryIntegredientCategoryResource($inventoryIntegredientCategory->fresh());
    }

    public function destroy(InventoryIntegredientCategory $inventoryIntegredientCategory)
    {
        $inventoryIntegredientCategory->delete();

        return response()->json([
            'message' => 'Ingredient category deleted successfully.',
        ]);
    }
}
