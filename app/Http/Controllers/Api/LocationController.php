<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Location\LocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use App\Services\LocationService;

class LocationController extends Controller
{

    public function __construct(
        private LocationService $locationService
    ) {}

    public function index()
    {
        $Locations = Location::get();

        return LocationResource::collection($Locations);
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
