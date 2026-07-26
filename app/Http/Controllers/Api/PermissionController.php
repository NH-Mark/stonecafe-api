<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Resources\PermissionResource;
use App\Models\User;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{

    public function __construct(
       
    )
    {

    }
    public function index()
    {
        $permissions = Permission::get();

        return PermissionResource::collection($permissions);
    }

   

}