<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderSource;
use Illuminate\Http\Request;
use App\Http\Resources\OrderSourceResource;

class OrderSourceController extends Controller
{

    public function __construct() {}
    
    

    public function index(Request $request)
    {
        $perPage = min(
            $request->integer('per_page', 10),
            100
        );

        $query = OrderSource::query();

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
            ->latest()
            ->paginate($perPage);

        return OrderSourceResource::collection(
            $methods
        );
    }

    public function listOrderSources()
    {
        $sources = OrderSource::where('status',1)->get();
        return OrderSourceResource::collection($sources);
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
        ]);

        $validated['code'] = 
            preg_replace(
                '/\s+/',
                '_',
                trim($validated['name'])
            );


        $source = OrderSource::create($validated);

        return new OrderSourceResource($source);
    }
    public function update(
        Request $request,
        OrderSource $orderSource
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
        ]);


        $orderSource->update($validated);

        return new OrderSourceResource(
            $orderSource->fresh()
        );
    }
    public function destroy(OrderSource $orderSource)
    {
        $orderSource->delete();

        return response()->json([
            'message' => 'Source deleted successfully.',
        ]);
    }
  
}
