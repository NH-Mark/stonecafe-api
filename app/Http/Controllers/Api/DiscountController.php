<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Resources\DiscountResource;
use App\Models\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{

    public function index(Request $request)
    {
        $perPage = min(
            $request->integer('per_page', 10),
            100
        );

        $query = Discount::query();

        /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

        $query->when(
            $request->filled('search'),
            function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where(
                        'name',
                        'like',
                        "%{$search}%"
                    );
                });
            }
        );

        /*
    |--------------------------------------------------------------------------
    | Column filters
    |--------------------------------------------------------------------------
    */

        $filters = $request->input('filters', []);

        foreach ($filters as $filter) {
            $column = $filter['id'] ?? null;
            $value = $filter['value'] ?? null;

            if (
                !$column ||
                $value === null ||
                $value === ''
            ) {
                continue;
            }

            switch ($column) {
                case 'name':
                    $query->where(
                        'name',
                        'like',
                        "%{$value}%"
                    );
                    break;

                case 'value':
                    $query->where(
                        'value',
                        'like',
                        "%{$value}%"
                    );
                    break;

                case 'type':
                    $query->where(
                        'type',
                        $value
                    );
                    break;

                case 'active':
                    $query->where(
                        'active',
                        filter_var(
                            $value,
                            FILTER_VALIDATE_BOOLEAN
                        )
                    );
                    break;
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

        $discounts = $query
            ->latest()
            ->paginate($perPage);

        return DiscountResource::collection(
            $discounts
        );
    }

    public function listDiscounts()
    {

        return DiscountResource::collection(

            Discount::where('status', true)
                ->orderBy('name')
                ->get()

        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'type' => [
                'required',
                'in:percentage,fixed',
            ],

            'value' => [
                'required',
                'numeric',
                'min:0',
            ],

            'status' => [
                'required',
                'boolean',
            ],
        ]);

        if (
            $validated['type'] === 'percentage' &&
            $validated['value'] > 100
        ) {
            return response()->json([
                'message' => 'Percentage discount cannot exceed 100.',
                'errors' => [
                    'value' => [
                        'Percentage discount cannot exceed 100.'
                    ],
                ],
            ], 422);
        }

        $discount = Discount::create($validated);

        return new DiscountResource($discount);
    }
    public function update(
        Request $request,
        Discount $discount
    ) {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'type' => [
                'required',
                'in:percentage,fixed',
            ],

            'value' => [
                'required',
                'numeric',
                'min:0',
            ],

            'status' => [
                'required',
                'boolean',
            ],
        ]);

        if (
            $validated['type'] === 'percentage' &&
            $validated['value'] > 100
        ) {
            return response()->json([
                'message' => 'Percentage discount cannot exceed 100.',
                'errors' => [
                    'value' => [
                        'Percentage discount cannot exceed 100.'
                    ],
                ],
            ], 422);
        }

        $discount->update($validated);

        return new DiscountResource(
            $discount->fresh()
        );
    }
    public function destroy(Discount $discount)
    {
        $discount->delete();

        return response()->json([
            'message' => 'Discount deleted successfully.',
        ]);
    }
}
