<?php

namespace App\Services;

use App\Models\Location;


class LocationService
{


    public function create(array $data): Location
    {

        return Location::create([

            'name' => $data['name'],

            'code' => $data['code'],

            'address' => $data['address'] ?? null,

            'phone' => $data['phone'] ?? null,

            'status' => $data['status'] ?? true,

        ]);

    }



    public function update(
        Location $location,
        array $data
    ): Location
    {

        $location->update([

            'name' => $data['name'],

            'code' => $data['code'],

            'address' => $data['address'] ?? null,

            'phone' => $data['phone'] ?? null,

            'status' => $data['status'] ?? true,

        ]);


        return $location;

    }



    public function delete(
        Location $location
    ): bool
    {

        return $location->delete();

    }


}