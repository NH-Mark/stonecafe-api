<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modifier\StoreModifierRequest;
use App\Http\Requests\Modifier\UpdateModifierRequest;
use App\Http\Resources\ModifierResource;
use App\Models\Modifier;
use App\Services\ModifierService;
use Illuminate\Http\Request;

class ModifierController extends Controller
{
    public function __construct(
        protected ModifierService $service
    ) {}

    public function index(Request $request)
    {
        $perPage = min(
            $request->integer('per_page', 10),
            100
        );

        $modifiers = Modifier::query()
            ->with('group')

            /*
        |--------------------------------------------------------------------------
        | Modifier Group
        |--------------------------------------------------------------------------
        */

            ->when(
                $request->filled('modifier_group_id'),
                function ($query) use ($request) {
                    $query->where(
                        'modifier_group_id',
                        $request->integer(
                            'modifier_group_id'
                        )
                    );
                }
            )

            /*
        |--------------------------------------------------------------------------
        | Global Search
        |--------------------------------------------------------------------------
        */

            ->when(
                $request->filled('search'),
                function ($query) use ($request) {
                    $search =
                        $request->input('search');

                    $query->where(function ($q) use (
                        $search
                    ) {
                        $q->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                            ->orWhere(
                                'name_ar',
                                'like',
                                "%{$search}%"
                            );
                    });
                }
            )

            /*
        |--------------------------------------------------------------------------
        | Column Filters
        |--------------------------------------------------------------------------
        */

            ->when(
                $request->filled('filters'),
                function ($query) use ($request) {
                    $filters =
                        $request->input(
                            'filters',
                            []
                        );

                    foreach ($filters as $filter) {
                        $column =
                            $filter['id'] ?? null;

                        $value =
                            $filter['value'] ?? null;

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

                            case 'name_ar':

                                $query->where(
                                    'name_ar',
                                    'like',
                                    "%{$value}%"
                                );

                                break;

                            case 'price':

                                $query->where(
                                    'price',
                                    $value
                                );

                                break;

                            case 'status':

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
                }
            )

            ->latest()

            ->paginate($perPage);

        return ModifierResource::collection(
            $modifiers
        );
    }


    // public function index()
    // {
    //     return ModifierResource::collection(
    //         Modifier::with('group')->latest()->get()
    //     );
    // }

    public function store(StoreModifierRequest $request)
    {
        return new ModifierResource(
            $this->service->create(
                $request->validated()
            )
        );
    }

    public function update(
        UpdateModifierRequest $request,
        Modifier $modifier
    ) {
        return new ModifierResource(
            $this->service->update(
                $modifier,
                $request->validated()
            )
        );
    }

    public function destroy(
        Modifier $modifier
    ) {
        $modifier->delete();

        return response()->json([
            'message' => 'Modifier deleted.'
        ]);
    }
}
