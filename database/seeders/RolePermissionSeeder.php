<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


class RolePermissionSeeder extends Seeder
{


    public function run()
    {


        $permissions = [


            'users.view',
            'users.create',
            'users.update',
            'users.delete',


        ];


        foreach ($permissions as $permission) {

            Permission::create([
                'name' => $permission
            ]);
        }



        $roles = [


            'super_admin',

            'admin',

        ];

        foreach ($roles as $role) {
            Role::create([
                'name' => $role
            ]);
        }
        
        Role::findByName('super_admin')
            ->givePermissionTo(
                Permission::all()
            );

    }
}
