<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{


    public function run()
    {


        $location = Location::create([

            'name' => 'Stone Cafe',

            'code' => 'MAIN',

        ]);



        $user = User::create([

            'name' => 'Super Admin',

            'email' => 'admin@stonecafe.com',

            'password' => Hash::make('password'),

            'location_id' => $location->id

        ]);



        $user->assignRole('super_admin');
    }
}
