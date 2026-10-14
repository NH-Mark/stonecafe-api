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

    public function getLocationSelectors(Request $request)
    {
        $user = $request->user();

        // if ($user->hasRole('Super Admin')) {
        //     $locations = Location::where('is_active', true)
        //         ->get(['id', 'name']);
        // } else {
            $locations = $user->locations()
                ->where('locations.status', true)
                ->get(['locations.id', 'locations.name']);
        // }

        return response()->json([
            'data' => $locations,
        ]);
    }

    public function select(Request $request)
    {
        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
        ]);

        $user = $request->user();

        $locationQuery = Location::where(
            'id',
            $validated['location_id']
        )->where('status', true);

        if (! $user->hasRole('Super Admin')) {
            $locationQuery->whereHas('users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            });
        }

        $location = $locationQuery->first();

        if (! $location) {
            return response()->json([
                'message' => 'You do not have access to this location.',
            ], 403);
        }

        return response()->json([
            'message' => 'Location selected successfully.',
            'data' => [
                'id' => $location->id,
                'name' => $location->name,
            ],
        ]);
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

