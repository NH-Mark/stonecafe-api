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
use Illuminate\Http\Request;

class UserController extends Controller
{

    public function __construct(
        private UserService $userService
    ) {}

    public function index(Request $request)
    {
        $perPage = min(
            $request->integer('per_page', 10),
            100
        );

        $users = User::query()
            ->with([
                'roles',
                'permissions'
            ])
            ->when(
                $request->role_id,
                function ($query, $roleId) {
                    $query->whereHas(
                        'roles',
                        function ($roleQuery) use ($roleId) {
                            $roleQuery->where(
                                'roles.id',
                                $roleId
                            );
                        }
                    );
                }
            )
            ->when(
                $request->search,
                function ($query, $search) {
                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere(
                                'email',
                                'like',
                                "%{$search}%"
                            );
                    });
                }
            )
            ->latest()
            ->paginate($perPage);

        return UserResource::collection($users);
    }


    // public function index()
    // {
    //     $users = User::with('permissions')

    //         ->get();

    //     return UserResource::collection($users);
    // }

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
