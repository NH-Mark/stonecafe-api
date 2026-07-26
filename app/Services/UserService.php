<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserService
{

    public function create(array $data): User
    {

          $user = User::create([

            'name'=>$data['name'],

            'email'=>$data['email'],

            'password'=>Hash::make(
                $data['password']
            ),

            'location_id'=>$data['location_id'] ?? null

        ]);

        // $user->syncRoles(
        //     $data['roles'] ?? []
        // );

        if (!empty($data['role_id'])) {

            $role = Role::findOrFail(
                $data['role_id']
            );

            $user->assignRole($role);

        }
        
        return $user->load([
            'roles',
            'location'
        ]);
    }

    public function update(
        User $user,
        array $data
    ): User
    {

        $user->update([

            'name' => $data['name'],

            'email' => $data['email'],

            'location_id' => $data['location_id'] ?? null,

        ]);


        if (!empty($data['password'])) {

            $user->update([

                'password' => Hash::make(
                    $data['password']
                )

            ]);

        }


        if (isset($data['role_id'])) {

            $role = Role::findOrFail(
                $data['role_id']
            );
            $user->syncRoles([
                $role
            ]);

        }


        return $user->load([
            'roles',
            'location'
        ]);

    }

    public function delete(
        User $user
    ): bool
    {

        return $user->delete();

    }

}