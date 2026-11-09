<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryMeasurementUnitRequest;
use App\Http\Resources\InventoryMeasurementUnitResource;
use App\Models\InventoryMeasurementUnit;
use Illuminate\Http\Request;

class InventoryMeasurementUnitController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min($request->integer('per_page', 10), 100);

        $query = InventoryMeasurementUnit::query();

        $query->when($request->filled('search'), function ($query) use ($request) {
            $search = trim((string) $request->input('search'));

            $query->where('name', 'like', "%{$search}%");
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

                if ($column === 'name') {
                    $query->where('name', 'like', "%{$value}%");
                }
            }
        }

        $measurementUnits = $query
            ->latest()
            ->paginate($perPage);

        return InventoryMeasurementUnitResource::collection($measurementUnits);
    }

    public function show(InventoryMeasurementUnit $inventoryMeasurementUnit)
    {
        return new InventoryMeasurementUnitResource($inventoryMeasurementUnit);
    }

    public function store(InventoryMeasurementUnitRequest $request)
    {
        $measurementUnit = InventoryMeasurementUnit::create($request->validated());

        return new InventoryMeasurementUnitResource($measurementUnit);
    }

    public function update(
        InventoryMeasurementUnitRequest $request,
        InventoryMeasurementUnit $inventoryMeasurementUnit
    ) {
        $inventoryMeasurementUnit->update($request->validated());

        return new InventoryMeasurementUnitResource($inventoryMeasurementUnit->fresh());
    }

    public function destroy(InventoryMeasurementUnit $inventoryMeasurementUnit)
    {
        $inventoryMeasurementUnit->delete();

        return response()->json([
            'message' => 'Measurement unit deleted successfully.',
        ]);
    }
}
