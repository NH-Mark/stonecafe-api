<?php

namespace App\Services;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthService
{
    // public function login(LoginRequest $request): array
    // {
    //     if (! Auth::attempt($request->validated())) {

    //         throw new UnauthorizedHttpException('', 'Invalid email or password');

    //     }

    //     $request->session()->regenerate();

    //     $user = $request->user()->load('location');

    //     return [
    //         'message' => 'Login successful',
    //         'user' => new UserResource($user),
    //     ];
    // }
    public function login(LoginRequest $request): array
    {
        if (! Auth::attempt($request->validated())) {
            throw new UnauthorizedHttpException('', 'Invalid email or password');
        }

        $user = $request->user()->load('location');

        $token = $user->createToken('next-app')->plainTextToken;

        return [
            'message' => 'Login successful',
            'token' => $token,
            'user' => new UserResource($user),
        ];
    }

    // public function logout($request): array
    // {
    //     Auth::logout();
    //     $request->session()->invalidate();
    //     $request->session()->regenerateToken();

    //     return [
    //         'message' => 'Logged out successfully'
    //     ];
    // }

    public function logout($request): array
    {
        $request->user()->currentAccessToken()->delete();

        return [
            'message' => 'Logged out successfully'
        ];
    }

    public function me($request): UserResource
    {
        return new UserResource(
            $request->user()->load('location')
        );
    }

    public function forgotPassword($request): array
    {
        $request->validate([
            'email' => [
                'required',
                'email'
            ]
        ]);


        $status = Password::sendResetLink(
            $request->only('email')
        );


        if ($status !== Password::RESET_LINK_SENT) {
            throw new \Exception(
                __($status)
            );
        }


        return [
            'message' => __($status)
        ];
    }

    public function resetPassword($request): array
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);


        $status = Password::reset(
            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token'
            ),
            function ($user, $password) {

                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );


        if ($status !== Password::PASSWORD_RESET) {
            throw new \Exception(__($status));
        }


        return [
            'message' => 'Password reset successfully'
        ];
    }
}
