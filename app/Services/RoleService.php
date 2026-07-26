<?php

namespace App\Services;

use Spatie\Permission\Models\Role;


class RoleService
{

    public function create(array $data): Role
    {

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web'
        ]);


        $role->syncPermissions(
            $data['permissions']
        );

        return $role->load('permissions');
    }

     public function delete(
        Role $role
    ): bool
    {

        return $role->delete();

    }


}