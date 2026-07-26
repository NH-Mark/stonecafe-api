<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ChangePasswordRequest;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountService $service
    ) {
    }

    public function updateProfile(
        UpdateProfileRequest $request
    ): UserResource {

        $user = $this->service->updateProfile(
            $request->user(),
            $request->validated()
        );

        return new UserResource($user);
    }

    public function changePassword(
        ChangePasswordRequest $request
    ): JsonResponse {

        $this->service->changePassword(
            $request->user(),
            $request->validated()
        );

        return response()->json([

            'message' => 'Password updated successfully.',

        ]);
    }
}