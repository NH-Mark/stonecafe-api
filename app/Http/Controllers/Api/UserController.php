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
                'permissions',
            ])

            /*
         * Sidebar role filter.
         */
            ->when(
                $request->filled('role_id'),
                function ($query) use ($request) {
                    $query->whereHas(
                        'roles',
                        function ($roleQuery) use ($request) {
                            $roleQuery->where(
                                'roles.id',
                                $request->integer('role_id')
                            );
                        }
                    );
                }
            )

            /*
         * Global search.
         */
            ->when(
                $request->filled('search'),
                function ($query) use ($request) {
                    $search = $request->search;

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where(
                                'name',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'email',
                                'like',
                                "%{$search}%"
                            );
                    });
                }
            );

        /*
     * DataTable column filters.
     */
        $filters = $request->input('filters', []);

        foreach ($filters as $filter) {
            $column = $filter['id'] ?? null;
            $value = $filter['value'] ?? null;

            if (
                !$column ||
                $value === null ||
                $value === ''
            ) {
                continue;
            }

            switch ($column) {

                case 'name':

                    $users->where(
                        'name',
                        'like',
                        "%{$value}%"
                    );

                    break;

                case 'email':

                    $users->where(
                        'email',
                        'like',
                        "%{$value}%"
                    );

                    break;

                case 'phone':

                    $users->where(
                        'phone',
                        'like',
                        "%{$value}%"
                    );

                    break;

                case 'active':

                    $users->where(
                        'active',
                        filter_var(
                            $value,
                            FILTER_VALIDATE_BOOLEAN
                        )
                    );

                    break;

                case 'role':

                    $users->whereHas(
                        'roles',
                        function ($roleQuery) use ($value) {
                            $roleQuery->where(
                                'roles.id',
                                $value
                            );
                        }
                    );

                    break;
            }
        }

        $users = $users
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
