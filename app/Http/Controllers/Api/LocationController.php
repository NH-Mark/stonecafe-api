<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Location\LocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use App\Services\LocationService;
use Illuminate\Http\Request;

class LocationController extends Controller
{

    public function __construct(
        private LocationService $locationService
    ) {}

    public function index(Request $request)
    {
        $perPage = min(
            $request->integer('per_page', 10),
            100
        );

        $query = Location::query();

        /*
     * Global search.
     */
        $query->when(
            $request->filled('search'),
            function ($query) use ($request) {
                $search = $request->search;

                $query->where(
                    'name',
                    'like',
                    "%{$search}%"
                );
            }
        );

        /*
     * DataTable column filters.
     */
        $filters = $request->input(
            'filters',
            []
        );

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

        $locations = $query
            ->latest()
            ->paginate($perPage);

        return LocationResource::collection(
            $locations
        );
    }


    public function store(
        LocationRequest $request
    ) {

        $location =
            $this->locationService->create(
                $request->validated()
            );


        return new LocationResource(
            $location
        );
    }




    public function update(
        LocationRequest $request,
        Location $location
    ) {

        $location =
            $this->locationService->update(
                $location,
                $request->validated()
            );


        return new LocationResource(
            $location
        );
    }




    public function destroy(
        Location $location
    ) {

        $this->locationService->delete(
            $location
        );

        return response()->json([
            'message' => 'Location deleted'
        ]);
    }
}
