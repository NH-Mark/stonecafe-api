<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function login(LoginRequest $request)
    {
        return $this->authService->login($request);
    }

    public function logout(Request $request)
    {
        return $this->authService->logout($request);
    }

    public function user(Request $request)
    {
        return new UserResource($request->user());
    }
    public function forgotPassword(Request $request)
    {
        return $this->authService->forgotPassword($request);
    }
    public function resetPassword(Request $request)
    {
        return $this->authService->resetPassword($request);
    }
}
