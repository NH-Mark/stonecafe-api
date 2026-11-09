<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventorySupplierRequest;
use App\Http\Resources\InventorySupplierResource;
use App\Models\InventorySupplier;
use Illuminate\Http\Request;

class InventorySupplierController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min($request->integer('per_page', 10), 100);

        $query = InventorySupplier::query();

        $query->when($request->filled('search'), function ($query) use ($request) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('supplier_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('alternate_phone', 'like', "%{$search}%")
                    ->orWhere('tax_number', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");
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
                    case 'supplier_code':
                    case 'name':
                    case 'email':
                    case 'phone':
                    case 'alternate_phone':
                    case 'tax_number':
                    case 'city':
                    case 'country':
                        $query->where($column, 'like', "%{$value}%");
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

        $suppliers = $query
            ->latest()
            ->paginate($perPage);

        return InventorySupplierResource::collection($suppliers);
    }

    public function show(InventorySupplier $inventorySupplier)
    {
        return new InventorySupplierResource($inventorySupplier);
    }

    public function store(InventorySupplierRequest $request)
    {
        $supplier = InventorySupplier::create($request->validated());

        return new InventorySupplierResource($supplier);
    }

    public function update(
        InventorySupplierRequest $request,
        InventorySupplier $inventorySupplier
    ) {
        $inventorySupplier->update($request->validated());

        return new InventorySupplierResource($inventorySupplier->fresh());
    }

    public function destroy(InventorySupplier $inventorySupplier)
    {
        $inventorySupplier->delete();

        return response()->json([
            'message' => 'Supplier deleted successfully.',
        ]);
    }
}
