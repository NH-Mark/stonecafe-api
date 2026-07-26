<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{

    public function __construct(
        private RoleService $roleService
    )
    {

    }
    public function index()
    {
        $roles = Role::with('permissions')
        
        ->get();

        return RoleResource::collection($roles);
    }


    public function store(StoreRoleRequest $request)
    {

        $role = $this->roleService
            ->create(
                $request->validated()
            );


        return new RoleResource($role);

    }

    public function update(
    UpdateRoleRequest $request,
    Role $role
    ) {

        $role->update([
            'name'=>$request->name
        ]);


        $role->syncPermissions(
            $request->permissions
        );


        return new RoleResource(
            $role->load('permissions')
        );
    }

    

    public function destroy(
        Role $role
    ) {

        $this->roleService->delete(
            $role
        );


        return response()->json([

            'message' => 'Role deleted'

        ]);
    }

}