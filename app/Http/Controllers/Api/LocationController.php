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

        $locations = Location::query()
            ->when(
                $request->search,
                function ($query, $search) {
                    $query->where(
                        'name',
                        'like',
                        "%{$search}%"
                    );
                }
            )
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
