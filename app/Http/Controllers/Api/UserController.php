<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\UserRequest;
use App\Http\Resources\RoleResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;

class UserController extends Controller
{

    public function __construct(
        private UserService $userService
    )
    {
       
    }

    public function index()
    {
        $users = User::with('permissions')
        
        ->get();

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request)
    {

        $role = $this->userService
            ->create(
                $request->validated()
            );


        return new RoleResource($role);

    }
     public function update(
        UpdateUserRequest $request,
        user $user
    ) {

        $location =
            $this->userService->update(
                $user,
                $request->validated()
            );


        return new UserResource(
            $location
        );
    }




    public function destroy(
        User $user
    ) {

        $this->userService->delete(
            $user
        );


        return response()->json([

            'message' => 'User deleted'

        ]);
    }

   

}