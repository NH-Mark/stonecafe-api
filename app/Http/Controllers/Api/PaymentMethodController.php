<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PaymentMethodController extends Controller
{

    public function __construct() {}
    
    

    public function index(Request $request)
    {
        $perPage = min(
            $request->integer('per_page', 10),
            100
        );

        $query = PaymentMethod::query();

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

                case 'code':
                    $query->where(
                        'code',
                        'like',
                        "%{$value}%"
                    );
                    break;


                case 'status':
                    $query->where(
                        'status',
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

        $methods = $query
            ->when(
                Schema::hasColumn('payment_methods', 'sort_order'),
                fn($q) => $q->orderBy('sort_order')->orderBy('id'),
                fn($q) => $q->orderBy('id')
            )
            ->paginate($perPage);

        return PaymentMethodResource::collection(
            $methods
        );
    }

    public function listPaymentMethods()
    {
        $query = PaymentMethod::query()->where('status', 1);

        $payment_methods = $query
            ->when(
                Schema::hasColumn('payment_methods', 'sort_order'),
                fn($q) => $q->orderBy('sort_order')->orderBy('id'),
                fn($q) => $q->orderBy('id')
            )
            ->get();

        return PaymentMethodResource::collection($payment_methods);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                'boolean',
            ],
            'sort_order' => [
                'nullable',
                'integer',
            ]
        ]);

        $validated['code'] = strtoupper(
            preg_replace(
                '/\s+/',
                '_',
                trim($validated['name'])
            )
        );


        $payment_method = PaymentMethod::create($validated);

        return new PaymentMethodResource($payment_method);
    }
    public function update(
        Request $request,
        PaymentMethod $payment_method
    ) {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'status' => [
                'required',
                'boolean',
            ],
            'sort_order' => [
                'nullable',
                'integer',
            ]
        ]);


        $payment_method->update($validated);

        return new PaymentMethodResource(
            $payment_method->fresh()
        );
    }
    public function destroy(PaymentMethod $payment_method)
    {
        $payment_method->delete();

        return response()->json([
            'message' => 'payment_method deleted successfully.',
        ]);
    }
  
}
