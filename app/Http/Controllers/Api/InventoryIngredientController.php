<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryIngredientRequest;
use App\Http\Resources\InventoryIngredientResource;
use App\Models\InventoryIngredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryIngredientController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min($request->integer('per_page', 10), 100);

        $query = InventoryIngredient::query()->with([
            'category',
            'measurementUnit',
            'suppliers.supplier',
            'locations.location',
        ]);

        $query->when($request->filled('search'), function ($query) use ($request) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('ingredient_type', 'like', "%{$search}%");
            });
        });

        return InventoryIngredientResource::collection(
            $query->latest()->paginate($perPage)
        );
    }

    public function show(InventoryIngredient $inventoryIngredient)
    {
        $inventoryIngredient->load([
            'category',
            'measurementUnit',
            'suppliers.supplier',
            'locations.location',
        ]);

        return new InventoryIngredientResource($inventoryIngredient);
    }

    public function usage(InventoryIngredient $inventoryIngredient)
    {
        $rows = $inventoryIngredient->menuItemIngredients()
            ->with(['menuItem:id,name'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function ($row) use ($inventoryIngredient) {
                $menuItem = $row->menuItem;
                $quantity = (float) ($row->quantity ?? 0);
                $costPerUnit = (float) ($row->cost_per_unit ?? 0);
                $subtotal = (float) ($row->subtotal ?? ($quantity * $costPerUnit));

                return [
                    'menu_item_id' => $menuItem?->id ?? $row->menu_item_id,
                    'menu_item_name' => $menuItem?->name,
                    'used_qty' => $quantity,
                    'quantity' => $quantity,
                    'unit_name' => $row->unit_name
                        ?? $inventoryIngredient->measurementUnit?->name
                        ?? $inventoryIngredient->buying_unit_name,
                    'cost_per_unit' => $costPerUnit,
                    'cost_in_recipe' => $subtotal,
                    'subtotal' => $subtotal,
                    'menu_item' => $menuItem ? [
                        'id' => $menuItem->id,
                        'name' => $menuItem->name,
                    ] : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    public function store(InventoryIngredientRequest $request)
    {
        $ingredient = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $suppliers = $data['suppliers'] ?? [];
            $locations = $data['locations'] ?? [];

            unset($data['suppliers'], $data['locations']);

            $inventoryIngredient = InventoryIngredient::create($data);

            if (! empty($suppliers)) {
                $inventoryIngredient->suppliers()->createMany(
                    array_map(fn (array $supplier) => [
                        'supplier_id' => $supplier['supplier_id'],
                        'price' => $supplier['price'] ?? 0,
                        'buying_unit' => $supplier['buying_unit'] ?? null,
                        'has_sub_unit' => $supplier['has_sub_unit'] ?? false,
                        'sub_unit_name' => $supplier['sub_unit_name'] ?? null,
                        'unit_to_sub_unit_quantity' => $supplier['unit_to_sub_unit_quantity'] ?? null,
                        'sub_unit_to_base_quantity' => $supplier['sub_unit_to_base_quantity'] ?? null,
                        'net_quantity' => $supplier['net_quantity'] ?? null,
                        'is_default' => $supplier['is_default'] ?? false,
                    ], $suppliers)
                );
            }

            if (! empty($locations)) {
                $inventoryIngredient->locations()->createMany(
                    array_map(fn (array $location) => [
                        'location_id' => $location['location_id'],
                        'opening_balance' => $location['opening_balance'] ?? 0,
                        'reorder_level' => $location['reorder_level'] ?? 0,
                        'track_inventory' => $location['track_inventory'] ?? true,
                    ], $locations)
                );
            }

            return $inventoryIngredient->fresh([
                'category',
                'measurementUnit',
                'suppliers.supplier',
                'locations.location',
            ]);
        });

        return new InventoryIngredientResource($ingredient);
    }

    public function update(InventoryIngredientRequest $request, InventoryIngredient $inventoryIngredient)
    {
        $ingredient = DB::transaction(function () use ($request, $inventoryIngredient) {
            $data = $request->validated();
            $suppliers = $data['suppliers'] ?? null;
            $locations = $data['locations'] ?? null;

            unset($data['suppliers'], $data['locations']);

            $inventoryIngredient->update($data);

            if ($suppliers !== null) {
                $inventoryIngredient->suppliers()->delete();

                if (! empty($suppliers)) {
                    $inventoryIngredient->suppliers()->createMany(
                        array_map(fn (array $supplier) => [
                            'supplier_id' => $supplier['supplier_id'],
                            'price' => $supplier['price'] ?? 0,
                            'buying_unit' => $supplier['buying_unit'] ?? null,
                            'has_sub_unit' => $supplier['has_sub_unit'] ?? false,
                            'sub_unit_name' => $supplier['sub_unit_name'] ?? null,
                            'unit_to_sub_unit_quantity' => $supplier['unit_to_sub_unit_quantity'] ?? null,
                            'sub_unit_to_base_quantity' => $supplier['sub_unit_to_base_quantity'] ?? null,
                            'net_quantity' => $supplier['net_quantity'] ?? null,
                            'is_default' => $supplier['is_default'] ?? false,
                        ], $suppliers)
                    );
                }
            }

            if ($locations !== null) {
                $inventoryIngredient->locations()->delete();

                if (! empty($locations)) {
                    $inventoryIngredient->locations()->createMany(
                        array_map(fn (array $location) => [
                            'location_id' => $location['location_id'],
                            'opening_balance' => $location['opening_balance'] ?? 0,
                            'reorder_level' => $location['reorder_level'] ?? 0,
                            'track_inventory' => $location['track_inventory'] ?? true,
                        ], $locations)
                    );
                }
            }

            return $inventoryIngredient->fresh([
                'category',
                'measurementUnit',
                'suppliers.supplier',
                'locations.location',
            ]);
        });

        return new InventoryIngredientResource($ingredient);
    }

    public function destroy(InventoryIngredient $inventoryIngredient)
    {
        $inventoryIngredient->delete();

        return response()->json([
            'message' => 'Ingredient deleted successfully.',
        ]);
    }
}
